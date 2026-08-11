<?php
/**
 * Mailer Helper — Margaux Collections
 *
 * Requires PHPMailer. Install via Composer:
 *   composer require phpmailer/phpmailer
 *
 * Or manually download PHPMailer and place in:
 *   /Margaux_Collections/vendor/phpmailer/phpmailer/src/
 *
 * Then update SMTP settings below with your Gmail (or any SMTP) credentials.
 *
 * Gmail setup:
 *   1. Enable 2FA on your Google account
 *   2. Go to myaccount.google.com → Security → App Passwords
 *   3. Create an App Password for "Mail"
 *   4. Paste it as SMTP_PASS below (NOT your Gmail login password)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Adjust path if your vendor folder is elsewhere
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    // Fallback: manual PHPMailer include (if not using Composer)
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
} else {
    require_once $autoloadPath;
}

// ── Local-only credentials (XAMPP) ──────────────────────────────────────────
// If config/local_env.php exists, load it — this is how your own computer
// gets the real SMTP password without it ever being committed to GitHub.
// On Railway this file won't exist, so this block simply does nothing there,
// and Railway's "Variables" tab is used instead.
$localEnv = __DIR__ . '/../config/local_env.php';
if (file_exists($localEnv)) {
    require_once $localEnv;
}

// ── SMTP Configuration ───────────────────────────────────────────────────────
// Credentials now come from environment variables, NOT hardcoded here, so
// they're never visible in the code or on GitHub. On Railway, set these
// under your service's "Variables" tab. On XAMPP, they come from
// config/local_env.php above.
define('SMTP_HOST',     getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT',     getenv('SMTP_PORT') ?: 587);
define('SMTP_USER',     getenv('SMTP_USER') ?: 'atisvincentcarl1@gmail.com');
define('SMTP_PASS',     getenv('SMTP_PASS') ?: '');   // ← set SMTP_PASS as an env var; do NOT put the real password here
define('SMTP_FROM',     getenv('SMTP_FROM') ?: 'atisvincentcarl1@gmail.com');
define('SMTP_FROM_NAME','Margaux Collections');

/**
 * Send an email using PHPMailer.
 *
 * @param string $toEmail   Recipient email
 * @param string $toName    Recipient name
 * @param string $subject   Email subject
 * @param string $htmlBody  HTML email body
 * @return bool             True on success, false on failure
 */
function send_mail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // XAMPP's local SSL setup is often incomplete, which makes PHPMailer's
        // certificate check fail on localhost even though the connection is
        // fine. This bypass ONLY activates when running locally — on Railway
        // (or any real host), $_SERVER['SERVER_NAME'] won't be localhost/127.0.0.1,
        // so the real, secure SSL verification is used automatically.
        $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
        if ($isLocal) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
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
    // Remove old unused OTPs for this identifier+type
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

    // Mark as used
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
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#2a0d14,#1a4070);padding:32px 40px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#fff;letter-spacing:.5px;">Margaux Collections</div>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:40px 40px 32px;">
            <p style="margin:0 0 8px;font-size:18px;font-weight:700;color:#0f172a;">Your verification code</p>
            <p style="margin:0 0 28px;font-size:14px;color:#64748b;line-height:1.6;">
              Use the code below to {$purposeLabel}. It expires in <strong>{$minutes} minutes</strong>.
            </p>
            <!-- OTP Box -->
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
        <!-- Footer -->
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