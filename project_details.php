<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/db.php';


// Helper function to display stars. You can place this in a new file like 'includes/functions.php' if you prefer.
function display_stars($rating)
{
    $stars_html = '';
    $full_stars = floor($rating);
    $half_star = ceil($rating) - $full_stars;
    $empty_stars = 5 - $full_stars - $half_star;
    for ($i = 0; $i < $full_stars; $i++) {
        $stars_html .= '<i class="fas fa-star"></i>';
    }
    if ($half_star) {
        $stars_html .= '<i class="fas fa-star-half-alt"></i>';
    }
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars_html .= '<i class="far fa-star"></i>';
    }
    return $stars_html;
}

$razorpay_key_id = 'rzp_test_RIEUMnyKzy3z67'; // Replace with your key   

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}
$project_id = $_GET['id'];

$stmt = $conn->prepare("SELECT id, title, subject, description, price, cover_image FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $project = $result->fetch_assoc();
} else {
    header('Location: index.php');
    exit();
}
$stmt->close();

// --- LOGIC FOR REVIEWS ---
$user_can_review = false;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $order_stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND project_id = ? AND status = 'success'");
    $order_stmt->bind_param("ii", $userId, $project_id);
    $order_stmt->execute();
    if ($order_stmt->get_result()->num_rows > 0) {
        $user_can_review = true;
    }
    $order_stmt->close();
}

// NEW: Fetch review statistics (average rating and count)
$review_stats_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE project_id = ?");
$review_stats_stmt->bind_param("i", $project_id);
$review_stats_stmt->execute();
$review_stats = $review_stats_stmt->get_result()->fetch_assoc();
$avg_rating = $review_stats['avg_rating'] ?? 0;
$total_reviews = $review_stats['total_reviews'] ?? 0;
$review_stats_stmt->close();

// NEW: Fetch all individual reviews
$reviews = [];
$reviews_stmt = $conn->prepare("SELECT r.rating, r.review_text, r.created_at, u.name AS user_name FROM reviews AS r JOIN users AS u ON r.user_id = u.id WHERE r.project_id = ? ORDER BY r.created_at DESC");
$reviews_stmt->bind_param("i", $project_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
while ($row = $reviews_result->fetch_assoc()) {
    $reviews[] = $row;
}
$reviews_stmt->close();
// --- END OF REVIEW LOGIC ---

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div id="payment-message" class="mb-3"></div>
        <div class="card">
            <div class="card-header">

                <?php if (!empty($project['cover_image'])): ?>
                    <img src="uploads/covers/<?php echo htmlspecialchars($project['cover_image']); ?>"
                        class="card-img-top mb-3" alt="<?php echo htmlspecialchars($project['title']); ?>"
                        style="border-radius: 5px;">
                <?php endif; ?>

                <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                <div class="mb-3">
                    <span class="star-rating-display" style="color: #f5b301; font-size: 1.2rem;">
                        <?php echo display_stars($avg_rating); ?>
                    </span>
                    <span class="text-muted align-middle">
                        <?php echo number_format($avg_rating, 1); ?> (based on <?php echo $total_reviews; ?> reviews)
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-subtitle mb-3 text-muted">Subject: <?php echo htmlspecialchars($project['subject']); ?>
                </h5>
                <p class="card-text"><?php echo $project['description']; ?></p>
                <hr>
                <div id="price-display" class="mb-3">
                    <h4 class="card-text">Price: ₹<?php echo htmlspecialchars($project['price']); ?></h4>
                </div>
                <div id="discount-details" class="mb-3"></div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="input-group mb-3">
                        <input type="text" id="coupon-code" class="form-control" placeholder="Have a coupon?">
                        <button class="btn btn-outline-secondary" type="button" id="apply-coupon-btn">Apply</button>
                    </div>
                    <button id="buy-now-btn" class="btn btn-success btn-lg w-100 mt-2">Buy Now</button>
                <?php else: ?>
                    <div class="alert alert-warning mt-4">
                        <h4><a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Login</a> or <a
                                href="signup.php">Create an Account</a> to Purchase</h4>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($user_can_review): ?>
                <hr>
                <div class="mt-4 px-4 pb-4">
                    <h4>Leave a Review</h4>
                    <div id="review-message"></div>
                    <form id="review-form">
                        <div class="mb-3">
                            <label class="form-label">Your Rating</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" /><label for="star5"
                                    title="5 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4"
                                    title="4 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3"
                                    title="3 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2"
                                    title="2 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1"
                                    title="1 star"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="review_text" class="form-label">Your Review (Optional)</label>
                            <textarea class="form-control" id="review_text" name="review_text" rows="3"></textarea>
                        </div>
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </form>
                </div>
            <?php endif; ?>
            <div class="card-footer">
                <a href="index.php" class="btn btn-secondary">← Back to Projects</a>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="assets/js/main.js"></script>
<script>
    // Only run the script if the buy now button exists on the page (i.e., user is logged in)
    if (document.getElementById('buy-now-btn')) {

        const originalPrice = <?php echo $project['price']; ?>;
        let finalPrice = originalPrice;
        let appliedCoupon = '';

        // --- Logic for Applying Coupon ---
        document.getElementById('apply-coupon-btn').addEventListener('click', function () {
            const couponCode = document.getElementById('coupon-code').value;
            const discountDetailsDiv = document.getElementById('discount-details');
            if (!couponCode) {
                discountDetailsDiv.innerHTML = '<div class="text-danger">Please enter a coupon code.</div>';
                return;
            }

            fetch('apply_coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ coupon_code: couponCode, price: originalPrice })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        finalPrice = data.new_price;
                        appliedCoupon = couponCode;
                        document.getElementById('price-display').innerHTML =
                            `<h4 class="card-text text-decoration-line-through text-muted">Price: ₹${originalPrice.toFixed(2)}</h4>
                         <h4 class="card-text">Discounted Price: ₹${finalPrice.toFixed(2)}</h4>`;
                        discountDetailsDiv.innerHTML = `<div class="text-success">${data.message}</div>`;
                    } else {
                        finalPrice = originalPrice;
                        appliedCoupon = '';
                        document.getElementById('price-display').innerHTML = `<h4 class="card-text">Price: ₹${originalPrice.toFixed(2)}</h4>`;
                        discountDetailsDiv.innerHTML = `<div class="text-danger">${data.message}</div>`;
                    }
                });
        });

        // --- Logic for "Buy Now" Button ---
        document.getElementById('buy-now-btn').addEventListener('click', function (e) {
            e.preventDefault();
            this.innerText = 'Processing...';
            this.disabled = true;

            fetch('create_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    project_id: <?php echo $project['id']; ?>,
                    coupon_code: appliedCoupon
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error creating order: ' + data.error);
                        document.getElementById('buy-now-btn').innerText = 'Buy Now';
                        document.getElementById('buy-now-btn').disabled = false;
                        return;
                    }

                    var options = {
                        "key": "<?php echo $razorpay_key_id; ?>",
                        "amount": data.amount,
                        "currency": "INR",
                        "name": "ProjectStore",
                        "description": "Purchase: <?php echo htmlspecialchars(addslashes($project['title'])); ?>",
                        "order_id": data.order_id,
                        "handler": function (response) {
                            document.getElementById('payment-message').innerHTML = '<div class="alert alert-info">Verifying payment...</div>';
                            fetch('verify_payment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature,
                                    project_id: <?php echo $project['id']; ?>,
                                    amount: finalPrice,
                                    coupon_code: appliedCoupon
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        var successMessage = `<div class="alert alert-success"><h4>Payment Successful!</h4><p>Thank you for your purchase.</p><a href="download.php?payment_id=${data.payment_id}" class="btn btn-primary">Download Now</a></div>`;
                                        document.getElementById('payment-message').innerHTML = successMessage;
                                        document.getElementById('buy-now-btn').style.display = 'none';
                                        document.querySelector('.input-group').style.display = 'none';
                                    } else {
                                        document.getElementById('payment-message').innerHTML = `<div class="alert alert-danger">Payment verification failed: ${data.message}</div>`;
                                        document.getElementById('buy-now-btn').innerText = 'Buy Now';
                                        document.getElementById('buy-now-btn').disabled = false;
                                    }
                                });
                        },
                        "prefill": {
                            "name": "<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Test User'; ?>",
                            "email": "<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'test.user@example.com'; ?>"
                        },
                        "modal": {
                            "ondismiss": function () {
                                document.getElementById('buy-now-btn').innerText = 'Buy Now';
                                document.getElementById('buy-now-btn').disabled = false;
                            }
                        }
                    };
                    var rzp1 = new Razorpay(options);
                    rzp1.on('payment.failed', function (response) {
                        alert("Payment Failed: " + response.error.description);
                        document.getElementById('buy-now-btn').innerText = 'Buy Now';
                        document.getElementById('buy-now-btn').disabled = false;
                    });
                    rzp1.open();
                });
        });
    }
</script>


<div class="row mt-5">
    <div class="col-md-8 offset-md-2">
        <h3>Customer Reviews</h3>
        <hr>
        <?php if (empty($reviews)): ?>
            <div class="alert alert-info">No reviews yet. Be the first to leave one!</div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($review['user_name']); ?></h5>
                        <div class="mb-2">
                            <span class="star-rating-display" style="color: #f5b301;">
                                <?php echo display_stars($review['rating']); ?>
                            </span>
                        </div>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                        <small class="text-muted">Reviewed on
                            <?php echo date('d M Y', strtotime($review['created_at'])); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// THIS IS THE ONLY LINE THAT NEEDED TO BE REMOVED FROM THE END OF THE FILE
$conn->close();
require_once 'includes/footer.php';
?>