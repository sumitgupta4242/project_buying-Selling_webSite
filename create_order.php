<?php
require('vendor/autoload.php');
require('includes/db.php');

use Razorpay\Api\Api;

// --- YOUR RAZORPAY KEYS ---
$keyId = 'rzp_test_RIEUMnyKzy3z67';
$keySecret = 'cM0VpZAfkum9wkEuE7TIyldZ';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$projectId = $data['project_id'];
$couponCode = isset($data['coupon_code']) ? strtoupper(trim($data['coupon_code'])) : null;

if (empty($projectId)) {
    echo json_encode(['error' => 'Project ID is required.']);
    exit;
}

// 1. Fetch the original price from the database
$stmt = $conn->prepare("SELECT price FROM projects WHERE id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    echo json_encode(['error' => 'Invalid Project ID.']);
    exit;
}
$project = $result->fetch_assoc();
$originalPrice = floatval($project['price']);
$finalPrice = $originalPrice;

// 2. If a coupon code was provided, validate it and apply the discount
// 2. If a coupon code was provided, validate it and apply the discount
if (!empty($couponCode)) {
    // --- MODIFIED QUERY ---
    // Now checks if the coupon is active AND not expired.
    $stmt = $conn->prepare(
        "SELECT discount_percentage FROM coupons 
         WHERE code = ? AND is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE())"
    );
    $stmt->bind_param("s", $couponCode);
    $stmt->execute();
    $couponResult = $stmt->get_result();
    
    if ($couponResult->num_rows === 1) {
        $coupon = $couponResult->fetch_assoc();
        $discountPercentage = floatval($coupon['discount_percentage']);
        $discountAmount = $originalPrice * ($discountPercentage / 100);
        $finalPrice = $originalPrice - $discountAmount;
    } else {
        // If the coupon is invalid or expired, reset the coupon code so we don't try to use it later
        $couponCode = null; 
    }
}

$amountInPaise = round($finalPrice * 100);

// 3. Create the Razorpay order with the final, server-verified price
$api = new Api($keyId, $keySecret);
$orderData = [
    'receipt'         => 'rcptid_' . time(),
    'amount'          => $amountInPaise,
    'currency'        => 'INR',
    'payment_capture' => 1
];

try {
    $razorpayOrder = $api->order->create($orderData);
    echo json_encode(['order_id' => $razorpayOrder['id'], 'amount' => $amountInPaise]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>