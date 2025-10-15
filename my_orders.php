<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// 1. Check if the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// 2. --- QUERY 1: Fetch Completed Orders (Existing Query) ---
$orders_stmt = $conn->prepare(
    "SELECT 
        o.order_date, 
        o.amount, 
        o.razorpay_payment_id, 
        o.status,
        p.id AS project_id,
        p.title AS project_title,
        r.id AS review_id
    FROM orders AS o
    JOIN projects AS p ON o.project_id = p.id
    LEFT JOIN reviews AS r ON p.id = r.project_id AND o.user_id = r.user_id
    WHERE o.user_id = ? 
    ORDER BY o.order_date DESC"
);
$orders_stmt->bind_param("i", $userId);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();


// 3. --- NEW QUERY: Fetch Custom Project Requests ---
$requests_stmt = $conn->prepare(
    "SELECT 
        created_at,
        subject,
        status
    FROM custom_project_requests
    WHERE user_id = ? 
    ORDER BY created_at DESC"
);
$requests_stmt->bind_param("i", $userId);
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();

?>

<div class="container">
    <h1 class="mt-4 mb-4">My Activity</h1>

    <h3 class="mb-3">My Orders</h3>
    <div class="card mb-5">
        <div class="card-body">
            <?php if ($orders_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Order Date</th>
                                <th>Project Title</th>
                                <th>Amount</th>
                                <th>Download</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['project_title']); ?></td>
                                    <td>₹<?php echo htmlspecialchars($order['amount']); ?></td>
                                    <td>
                                        <a href="download.php?payment_id=<?php echo htmlspecialchars($order['razorpay_payment_id']); ?>" class="btn btn-primary btn-sm">Download</a>
                                    </td>
                                    <td>
                                        <?php if ($order['review_id']): ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Reviewed</span>
                                        <?php else: ?>
                                            <a href="project_details.php?id=<?php echo $order['project_id']; ?>#review-form" class="btn btn-outline-primary btn-sm">Leave a Review</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center mb-0">
                    You have not purchased any projects yet.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <h3 class="mb-3">My Custom Requests</h3>
    <div class="card">
        <div class="card-body">
            <?php if ($requests_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Request Date</th>
                                <th>Subject</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $requests_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M Y, h:i A', strtotime($request['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                    <td>
                                        <?php 
                                            $status = htmlspecialchars($request['status']);
                                            $badge_class = 'bg-secondary';
                                            if ($status == 'new') $badge_class = 'bg-primary';
                                            if ($status == 'viewed') $badge_class = 'bg-info text-dark';
                                            if ($status == 'completed') $badge_class = 'bg-success';
                                            if ($status == 'rejected') $badge_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center mb-0">
                    You have not made any custom project requests.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$orders_stmt->close();
$requests_stmt->close();
$conn->close();
require_once 'includes/footer.php';
?>