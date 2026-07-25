<?php
/**
 * notify_helper.php
 * Call createNotification() whenever an order status changes.
 * Call createPaymentNotification() specifically when a GCash payment
 * gets marked as verified/paid by an admin (separate from order status flow).
 */

function createNotification(mysqli $db, int $userId, int $orderId, string $orderStatus): void {
    $messages = [
        'processing' => [
            'title'   => '📦 Your order is on its way!',
            'message' => "Your Order #$orderId is now being processed and will be delivered to your address soon. Please prepare your payment upon delivery.",
        ],
        'completed' => [
            'title'   => '✅ Order Completed!',
            'message' => "Your Order #$orderId has been completed. Thank you for shopping with Marguax_Collectionoration!",
        ],
        'pending' => [
            'title'   => '🕐 Order is Pending',
            'message' => "Your Order #$orderId is pending. We will process it shortly.",
        ],
    ];

    if (!isset($messages[$orderStatus])) return;

    $title   = $messages[$orderStatus]['title'];
    $message = $messages[$orderStatus]['message'];

    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, order_id, title, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('iiss', $userId, $orderId, $title, $message);
    $stmt->execute();
    $stmt->close();
}

/**
 * Fired only when an order's payment_status transitions INTO 'paid'.
 * Kept separate from createNotification() because this is about the
 * *payment* being verified by an admin, not the order fulfillment status —
 * the two can change independently (e.g. order can still be "processing"
 * while payment is now confirmed).
 */
function createPaymentNotification(mysqli $db, int $userId, int $orderId): void {
    $title   = '💳 Payment Verified!';
    $message = "We've confirmed your GCash payment for Order #$orderId. Your order is now being processed.";

    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, order_id, title, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('iiss', $userId, $orderId, $title, $message);
    $stmt->execute();
    $stmt->close();
}