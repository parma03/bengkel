<?php
// File: webhook.php
// Webhook handler for Midtrans payment notifications

require_once 'config/midtrans.php';
require_once 'db/koneksi.php';

// Get notification from Midtrans
$notif = new \Midtrans\Notification();

$transaction = $notif->transaction_status;
$type = $notif->payment_type;
$order_id = $notif->order_id;
$fraud = $notif->fraud_status;

// Extract transaction ID from order_id
$order_parts = explode('-', $order_id);
$transaction_id = $order_parts[1] ?? null;

if (!$transaction_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit;
}

try {
    // Get transaction from database
    $stmt = $pdo->prepare("SELECT * FROM tb_transaksi WHERE id_transaksi = ? AND order_id = ?");
    $stmt->execute([$transaction_id, $order_id]);
    $db_transaction = $stmt->fetch();

    if (!$db_transaction) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
        exit;
    }

    // Update transaction status based on Midtrans notification
    $new_status = '';

    if ($transaction == 'settlement') {
        $new_status = 'paid';
    } elseif ($transaction == 'pending') {
        $new_status = 'pending';
    } elseif ($transaction == 'deny' || $transaction == 'cancel' || $transaction == 'expire') {
        $new_status = 'failed';
    } elseif ($transaction == 'failure') {
        $new_status = 'failed';
    }

    if ($new_status) {
        $stmt = $pdo->prepare("UPDATE tb_transaksi SET status_pembayaran = ?, updated_at = NOW() WHERE id_transaksi = ?");
        $stmt->execute([$new_status, $transaction_id]);

        // Log the notification (optional)
        error_log("Midtrans notification: Order ID: $order_id, Status: $transaction, New Status: $new_status");
    }

    // Send response to Midtrans
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    error_log("Midtrans webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
