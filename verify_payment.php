<?php
session_start();
require('vendor/autoload.php');
require('includes/db.php');

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// --- YOUR RAZORPAY KEYS ---
$keyId = 'rzp_test_RIEUMnyKzy3z67'; // <-- IMPORTANT: REPLACE WITH YOUR KEY ID
$keySecret = 'cM0VpZAfkum9wkEuE7TIyldZ'; // <-- IMPORTANT: REPLACE WITH YOUR KEY SECRET

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Check if all required data is present
if (empty($data['razorpay_payment_id']) || empty($data['razorpay_order_id']) || empty($data['razorpay_signature'])) {
    echo json_encode(['status' => 'error', 'message' => 'Required payment data is missing.']);
    exit;
}

$success = true;
$error = "Payment Failed";

try {
    // This is the most important step: Signature Verification
    $api = new Api($keyId, $keySecret);
    $attributes = [
        'razorpay_order_id' => $data['razorpay_order_id'],
        'razorpay_payment_id' => $data['razorpay_payment_id'],
        'razorpay_signature' => $data['razorpay_signature']
    ];
    $api->utility->verifyPaymentSignature($attributes);
} catch(SignatureVerificationError $e) {
    $success = false;
    $error = 'Razorpay Error : ' . $e->getMessage();
}

if ($success === true) {
    // Payment Signature is correct. Now save the order details.
    $projectId = $data['project_id'];
    $amount = $data['amount'];
    $paymentId = $data['razorpay_payment_id'];
    $status = 'success';
    
    // NEW: Get the coupon code sent from the frontend
    $couponCode = isset($data['coupon_code']) ? strtoupper(trim($data['coupon_code'])) : null;

    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Guest User";
    // Assuming you have 'email' in the session for logged-in users
    $userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : "guest@example.com"; 

    // MODIFIED: Added coupon_code to the insert statement
    $stmt = $conn->prepare("INSERT INTO orders (project_id, user_id, user_name, user_email, amount, coupon_code, razorpay_payment_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    // MODIFIED: Added "s" for the coupon_code string and the variable itself
    $stmt->bind_param("iisdssss", $projectId, $userId, $userName, $userEmail, $amount, $couponCode, $paymentId, $status);
    $stmt->execute();
    $stmt->close();
    
    // ===================================================================
    // NEW: INCREMENT THE COUPON USAGE COUNT
    // ===================================================================
    if (!empty($couponCode)) {
        $update_coupon_stmt = $conn->prepare("UPDATE coupons SET times_used = times_used + 1 WHERE code = ?");
        $update_coupon_stmt->bind_param("s", $couponCode);
        $update_coupon_stmt->execute();
        $update_coupon_stmt->close();
    }
    
    // Return a success response WITH the payment ID
    echo json_encode([
        'status' => 'success', 
        'message' => 'Payment successful and order saved.',
        'payment_id' => $paymentId
    ]);
} else {
    // Payment Signature failed
    echo json_encode(['status' => 'error', 'message' => $error]);
}

$conn->close();
?>