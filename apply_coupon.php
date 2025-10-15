<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['coupon_code']) || !isset($data['price'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data sent.']);
    exit;
}

$couponCode = strtoupper(trim($data['coupon_code']));
$originalPrice = floatval($data['price']);

// --- MODIFIED QUERY ---
// We now also check that the coupon has not expired.
// It's valid if 'valid_until' is NULL (never expires) OR the date is in the future.
$stmt = $conn->prepare(
    "SELECT discount_percentage FROM coupons 
     WHERE code = ? AND is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE())"
);
$stmt->bind_param("s", $couponCode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // --- Coupon is valid ---
    $coupon = $result->fetch_assoc();
    $discountPercentage = floatval($coupon['discount_percentage']);
    $discountAmount = $originalPrice * ($discountPercentage / 100);
    $newPrice = $originalPrice - $discountAmount;

    $response = [
        'status' => 'success',
        'new_price' => round($newPrice, 2),
        'discount_amount' => round($discountAmount, 2),
        'message' => htmlspecialchars($discountPercentage) . '% discount applied successfully!'
    ];
} else {
    // --- Coupon is invalid, inactive, or expired ---
    $response = [
        'status' => 'error',
        'message' => 'Invalid or expired coupon code.'
    ];
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>