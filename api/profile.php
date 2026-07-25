<?php
/**
 * API — Profile
 * Marguax Collection Ordering System
 *
 * GET  /api/profile.php                    → get current user profile
 * POST /api/profile.php { action: 'update', name, email, contact_number, address }
 *                                          → update profile
 * POST /api/profile.php { action: 'change_password', current_password, new_password }
 *                                          → change password
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../middleware/auth.php';

if (!isLoggedIn()) {
    apiUnauthorized();
}

$db     = getDB();
$userId = (int)$_SESSION['user_id'];

// ── GET: fetch profile ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT user_id, name, email, contact_number, address, role, created_at FROM users WHERE user_id=?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    // Fetch payment accounts
    $stmt = $db->prepare("SELECT * FROM user_payment_accounts WHERE user_id=? ORDER BY is_default DESC");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'success'  => true,
        'user'     => $user,
        'accounts' => $accounts
    ]);
    exit;
}

// ── POST: actions ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    // Update profile info
    if ($action === 'update') {
        $name    = trim($input['name'] ?? '');
        $email   = trim($input['email'] ?? '');
        $contact = trim($input['contact_number'] ?? '');
        $address = trim($input['address'] ?? '');

        if (empty($name))  { echo json_encode(['success'=>false,'message'=>'Name is required.']); exit; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success'=>false,'message'=>'Valid email is required.']); exit; }

        // Check email not taken by another user
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email=? AND user_id!=?");
        $stmt->bind_param('si', $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already in use.']);
            $stmt->close(); exit;
        }
        $stmt->close();

        $stmt = $db->prepare("UPDATE users SET name=?, email=?, contact_number=?, address=? WHERE user_id=?");
        $stmt->bind_param('ssssi', $name, $email, $contact, $address, $userId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) { $_SESSION['name'] = $name; }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Profile updated successfully.' : 'Update failed.'
        ]);
        exit;
    }

    // Change password
    if ($action === 'change_password') {
        $current = $input['current_password'] ?? '';
        $new     = $input['new_password'] ?? '';
        $confirm = $input['confirm_password'] ?? '';

        if (empty($current) || empty($new)) {
            echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
            exit;
        }
        if (strlen($new) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
            exit;
        }
        if ($new !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }

        $stmt = $db->prepare("SELECT password FROM users WHERE user_id=?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($current, $row['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param('si', $hash, $userId);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Password changed successfully.' : 'Failed to change password.'
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
