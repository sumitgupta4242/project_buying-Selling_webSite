<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// --- BACKEND LOGIC TO FETCH STATS ---

// Sales & User Stats
$result_revenue = $conn->query("SELECT SUM(amount) as total_revenue FROM orders WHERE status = 'success'");
$total_revenue = $result_revenue->fetch_assoc()['total_revenue'] ?? 0;

$result_today_revenue = $conn->query("SELECT SUM(amount) as todays_revenue FROM orders WHERE status = 'success' AND DATE(order_date) = CURDATE()");
$todays_revenue = $result_today_revenue->fetch_assoc()['todays_revenue'] ?? 0;

$result_orders = $conn->query("SELECT COUNT(id) as total_orders FROM orders WHERE status = 'success'");
$total_orders = $result_orders->fetch_assoc()['total_orders'] ?? 0;

$result_users = $conn->query("SELECT COUNT(id) as total_users FROM users");
$total_users = $result_users->fetch_assoc()['total_users'] ?? 0;

// --- NEW: Fetch Custom Request Stats ---
$result_new_requests = $conn->query("SELECT COUNT(id) as new_requests FROM custom_project_requests WHERE status = 'new'");
$new_requests = $result_new_requests->fetch_assoc()['new_requests'] ?? 0;

// Recent Orders (Existing)
$recent_orders_query = 
    "SELECT o.order_date, o.user_name, o.amount, p.title AS project_title
    FROM orders AS o
    JOIN projects AS p ON o.project_id = p.id
    WHERE o.status = 'success' 
    ORDER BY o.order_date DESC
    LIMIT 5";
$result_recent_orders = $conn->query($recent_orders_query);

// --- NEW: Fetch Recent Custom Requests ---
$recent_requests_query = "SELECT name, subject, created_at FROM custom_project_requests ORDER BY created_at DESC LIMIT 5";
$result_recent_requests = $conn->query($recent_requests_query);

?>

<div class="container-fluid">
    <h1 class="mt-4">Dashboard</h1>
    <p class="text-muted">Welcome to your business overview as of <?php echo date('l, F j, Y'); ?>.</p>

    <div class="row mt-4">
        <div class="col-lg col-md-6 mb-4">
            <div class="card stat-card h-100"><div class="card-body"><h5>Total Revenue</h5><h2 class="stat-number">₹<?php echo number_format($total_revenue, 2); ?></h2></div></div>
        </div>
        <div class="col-lg col-md-6 mb-4">
            <div class="card stat-card h-100"><div class="card-body"><h5>Today's Revenue</h5><h2 class="stat-number">₹<?php echo number_format($todays_revenue, 2); ?></h2></div></div>
        </div>
        <div class="col-lg col-md-6 mb-4">
            <div class="card stat-card h-100"><div class="card-body"><h5>Successful Orders</h5><h2 class="stat-number"><?php echo $total_orders; ?></h2></div></div>
        </div>
        <div class="col-lg col-md-6 mb-4">
            <div class="card stat-card h-100"><div class="card-body"><h5>Registered Users</h5><h2 class="stat-number"><?php echo $total_users; ?></h2></div></div>
        </div>
        <div class="col-lg col-md-6 mb-4">
    <?php
        // This PHP checks if there are new requests. If so, it adds the blue background classes.
        $requests_card_class = ($new_requests > 0) ? 'bg-primary text-white' : '';
    ?>
    <div class="card stat-card h-100 <?php echo $requests_card_class; ?>">
        <div class="card-body">
            <h5>New Project Requests</h5>
            <h2 class="stat-number"><?php echo $new_requests; ?></h2>
        </div>
    </div>
</div>
    </div> <div class="row mt-4">
        
        <div class="col-lg-7 mb-4">
            <div class="card h-100" style="border-radius: 10px; border:none; box-shadow: var(--shadow);">
                <div class="card-header" style="background-color: #fff; border-bottom: 1px solid var(--border-color);">
                    <h4 class="mb-0">Recent Orders</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>Date</th><th>Customer</th><th>Project Title</th><th>Amount</th></tr></thead>
                            <tbody>
                                <?php if ($result_recent_orders->num_rows > 0): ?>
                                    <?php while($order = $result_recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['project_title']); ?></td>
                                            <td>₹<?php echo number_format($order['amount'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">No recent orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card h-100" style="border-radius: 10px; border:none; box-shadow: var(--shadow);">
                <div class="card-header" style="background-color: #fff; border-bottom: 1px solid var(--border-color);">
                    <h4 class="mb-0">Recent Custom Requests</h4>
                </div>
                <div class="card-body">
                    <?php if ($result_recent_requests->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($request = $result_recent_requests->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($request['subject']); ?></h6>
                                        <small class="text-muted"><?php echo date('d M Y', strtotime($request['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1">From: <?php echo htmlspecialchars($request['name']); ?></p>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                        <div class="text-center mt-3">
                            <a href="manage_requests.php" class="btn btn-outline-primary">View All Requests</a>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted mt-3">No new custom requests.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
    
</div>

<?php
$conn->close();
require_once 'partials/footer.php';
?>