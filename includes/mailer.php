<?php
/**
 * Mailer Helper — Margaux Collections
 *
 * Sends email via the Resend HTTPS API instead of raw SMTP, since Railway
 * blocks outbound SMTP ports (587/465). Resend uses HTTPS (port 443),
 * which is not blocked.
 *
 * Requires RESEND_API_KEY to be set as an environment variable
 * (Railway → Variables tab).
 */

define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: '');
define('SMTP_FROM',       getenv('SMTP_FROM') ?: 'onboarding@resend.dev');
define('SMTP_FROM_NAME', 'Margaux Collections');

/**
 * Send an email using the Resend API.
 *
 * @param string $toEmail   Recipient email
 * @param string $toName    Recipient name (unused by Resend's API directly, kept for compatibility)
 * @param string $subject   Email subject
 * @param string $htmlBody  HTML email body
 * @return bool             True on success, false on failure
 */
function send_mail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    if (empty(RESEND_API_KEY)) {
        error_log('Resend error: RESEND_API_KEY is not set.');
        return false;
    }

    $payload = json_encode([
        'from'    => SMTP_FROM_NAME . ' <' . SMTP_FROM . '>',
        'to'      => [$toEmail],
        'subject' => $subject,
        'html'    => $htmlBody,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('Resend cURL error: ' . $curlError);
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }

    error_log('Resend API error: HTTP ' . $httpCode . ' — ' . $response);
    return false;
}

/**
 * Generate a cryptographically secure 6-digit OTP.
 */
function generate_otp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Store an OTP in the database (hashed, with expiry).
 * Deletes any existing unused OTPs for the same identifier+type first.
 *
 * @param mysqli $db
 * @param string $identifier  Email address
 * @param string $type        'login' or 'register'
 * @param string $otp         Plain 6-digit OTP (we store its SHA-256 hash)
 * @param int    $ttlSeconds  Time-to-live in seconds (default 300 = 5 min)
 */
function store_otp(mysqli $db, string $identifier, string $type, string $otp, int $ttlSeconds = 300): void {
    $stmt = $db->prepare("DELETE FROM otp_tokens WHERE identifier=? AND type=? AND used=0");
    $stmt->bind_param('ss', $identifier, $type);
    $stmt->execute();
    $stmt->close();

    $hash    = hash('sha256', $otp);
    $expires = date('Y-m-d H:i:s', time() + $ttlSeconds);

    $stmt = $db->prepare("INSERT INTO otp_tokens (identifier, type, token_hash, expires_at) VALUES (?,?,?,?)");
    $stmt->bind_param('ssss', $identifier, $type, $hash, $expires);
    $stmt->execute();
    $stmt->close();
}

/**
 * Verify an OTP from the database.
 * Marks it as used if valid.
 *
 * @return bool True if valid and not expired, false otherwise
 */
function verify_otp_db(mysqli $db, string $identifier, string $type, string $otp): bool {
    $hash = hash('sha256', $otp);
    $now  = date('Y-m-d H:i:s');

    $stmt = $db->prepare(
        "SELECT id FROM otp_tokens
         WHERE identifier=? AND type=? AND token_hash=? AND used=0 AND expires_at > ?
         LIMIT 1"
    );
    $stmt->bind_param('ssss', $identifier, $type, $hash, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();

    if (!$row) return false;

    $stmt = $db->prepare("UPDATE otp_tokens SET used=1 WHERE id=?");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $stmt->close();

    return true;
}

/**
 * Build the branded OTP email HTML body.
 */
function otp_email_html(string $otp, string $purpose, int $minutes = 5): string {
    $purposeLabel = $purpose === 'login' ? 'sign in to your account' : 'verify your email address';
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#2a0d14,#1a4070);padding:32px 40px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#fff;letter-spacing:.5px;">Margaux Collections</div>
          </td>
        </tr>
        <tr>
          <td style="padding:40px 40px 32px;">
            <p style="margin:0 0 8px;font-size:18px;font-weight:700;color:#0f172a;">Your verification code</p>
            <p style="margin:0 0 28px;font-size:14px;color:#64748b;line-height:1.6;">
              Use the code below to {$purposeLabel}. It expires in <strong>{$minutes} minutes</strong>.
            </p>
            <div style="background:#f8fafc;border:2px dashed #cbd5e1;border-radius:12px;padding:24px;text-align:center;margin-bottom:28px;">
              <div style="font-size:42px;font-weight:800;letter-spacing:16px;color:#2a0d14;font-family:'Courier New',monospace;">{$otp}</div>
            </div>
            <p style="margin:0 0 8px;font-size:13px;color:#94a3b8;line-height:1.6;">
              If you didn't request this code, you can safely ignore this email. Someone may have entered your email address by mistake.
            </p>
            <p style="margin:0;font-size:13px;color:#94a3b8;">
              Do <strong>not</strong> share this code with anyone.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f8fafc;padding:20px 40px;border-top:1px solid #e2e8f0;text-align:center;">
            <p style="margin:0;font-size:12px;color:#94a3b8;">&copy; Margaux Collections. All rights reserved.</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
