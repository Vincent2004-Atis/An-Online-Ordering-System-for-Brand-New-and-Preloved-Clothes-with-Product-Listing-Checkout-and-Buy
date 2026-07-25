<?php
/**
 * Security Helper — Marguax_Collectionoration Ordering System
 * Include this file on EVERY page: require_once '../includes/security.php';
 *
 * Provides:
 *  - Security headers (XSS, clickjacking, content sniffing protection)
 *  - CSRF token generation and validation
 *  - Session timeout (30 minutes idle)
 *  - Input sanitization helpers
 *  - Rate limiting for login
 *  - Safe file upload validation
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 1800); // 30 min
    session_start();
}

// ── Security Headers ────────────────────────────────────────────────────────
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;");

// ── Session Timeout (30 minutes idle) ───────────────────────────────────────
if (isset($_SESSION['user_id'])) {
    $timeout = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        // Session expired — destroy and redirect
        session_unset();
        session_destroy();
        header('Location: /Marguax_Collection/auth/login.php?reason=timeout');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// ── CSRF Token ──────────────────────────────────────────────────────────────
/**
 * Generate a CSRF token and store it in the session.
 * Call csrf_field() inside every <form> to output the hidden input.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input field — place inside every <form>
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validate CSRF token from POST request.
 * Call this at the top of every POST handler.
 */
function csrf_verify(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('403 Forbidden — Invalid CSRF token. <a href="javascript:history.back()">Go back</a>');
    }
}

// ── Rate Limiting (Login brute force protection) ─────────────────────────────
/**
 * Check if IP is rate limited. Returns true if blocked.
 * Uses session-based rate limiting (works without Redis/DB).
 */
function rate_limit_check(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool {
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $sessKey  = 'rl_' . $key . '_' . md5($ip);

    if (!isset($_SESSION[$sessKey])) {
        $_SESSION[$sessKey] = ['attempts' => 0, 'first_attempt' => time()];
    }

    $data    = &$_SESSION[$sessKey];
    $elapsed = time() - $data['first_attempt'];

    // Reset window if decay period passed
    if ($elapsed > $decaySeconds) {
        $data = ['attempts' => 0, 'first_attempt' => time()];
    }

    return $data['attempts'] >= $maxAttempts;
}

/**
 * Increment rate limit counter for a key.
 */
function rate_limit_increment(string $key): void {
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $sessKey = 'rl_' . $key . '_' . md5($ip);
    if (isset($_SESSION[$sessKey])) {
        $_SESSION[$sessKey]['attempts']++;
    }
}

/**
 * Clear rate limit after successful action.
 */
function rate_limit_clear(string $key): void {
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $sessKey = 'rl_' . $key . '_' . md5($ip);
    unset($_SESSION[$sessKey]);
}

// ── Input Sanitization ───────────────────────────────────────────────────────
/**
 * Sanitize and trim a string. Returns empty string if null.
 */
function clean(string $val, int $maxLength = 500): string {
    return mb_substr(trim($val), 0, $maxLength);
}

/**
 * Sanitize output to prevent XSS.
 */
function e(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Philippine mobile number format (09XXXXXXXXX or +639XXXXXXXXX).
 */
function valid_phone(string $phone): bool {
    return (bool)preg_match('/^(09|\+639)\d{9}$/', $phone);
}

// ── File Upload Security ──────────────────────────────────────────────────────
/**
 * Validate an uploaded image file.
 * Checks MIME type, extension, and file size.
 * Returns ['ok'=>true] or ['ok'=>false, 'error'=>'message']
 */
function validate_image(array $file, int $maxMB = 2): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload error (code ' . $file['error'] . ').'];
    }

    // Check file size
    if ($file['size'] > $maxMB * 1024 * 1024) {
        return ['ok' => false, 'error' => "Image must be under {$maxMB}MB."];
    }

    // Check MIME type using finfo (not just extension — prevents disguised files)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mimeType, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, GIF, and WEBP images are allowed.'];
    }

    // Check extension matches MIME
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeToExt = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/gif'  => ['gif'],
        'image/webp' => ['webp'],
    ];

    if (!in_array($ext, $mimeToExt[$mimeType] ?? [], true)) {
        return ['ok' => false, 'error' => 'File extension does not match image type.'];
    }

    return ['ok' => true, 'mime' => $mimeType, 'ext' => $ext];
}

/**
 * Generate a safe random filename for uploaded files.
 */
function safe_filename(string $ext): string {
    return bin2hex(random_bytes(16)) . '.' . $ext;
}
