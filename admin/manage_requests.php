<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// --- HANDLE STATUS UPDATES ---
// This block checks if an action (like 'mark_viewed') is passed in the URL
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $new_status = '';

    // A simple way to map actions to statuses
    switch ($action) {
        case 'mark_viewed':
            $new_status = 'viewed';
            break;
        case 'mark_completed':
            $new_status = 'completed';
            break;
        case 'mark_rejected':
            $new_status = 'rejected';
            break;
    }

    if (!empty($new_status)) {
        $stmt = $conn->prepare("UPDATE custom_project_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Request status updated successfully!";
        header('Location: manage_requests.php');
        exit;
    }
}

// Fetch all custom project requests, newest first
$requests_result = $conn->query("SELECT * FROM custom_project_requests ORDER BY created_at DESC");

?>

<div class="container-fluid">
    <h1 class="mt-4">Manage Custom Project Requests</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-header">
            <h4>All Incoming Requests</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests_result->num_rows > 0): ?>
                            <?php while($request = $requests_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M Y, h:i A', strtotime($request['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($request['name']); ?></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($request['email']); ?>"><?php echo htmlspecialchars($request['email']); ?></a></td>
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
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestModal" 
                                            data-name="<?php echo htmlspecialchars($request['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($request['email']); ?>"
                                            data-subject="<?php echo htmlspecialchars($request['subject']); ?>"
                                            data-description="<?php echo nl2br(htmlspecialchars($request['description'])); ?>"
                                            data-deadline="<?php echo !empty($request['deadline']) ? date('d M Y', strtotime($request['deadline'])) : 'N/A'; ?>"
                                            data-budget="<?php echo !empty($request['budget']) ? htmlspecialchars($request['budget']) : 'N/A'; ?>">
                                            View Details
                                        </button>
                                        <?php if ($request['status'] == 'new'): ?>
                                            <a href="manage_requests.php?action=mark_viewed&id=<?php echo $request['id']; ?>" class="btn btn-info btn-sm">Mark as Viewed</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No custom project requests yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestModalLabel">Project Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Name:</strong> <span id="modal-name"></span></p>
                <p><strong>Email:</strong> <span id="modal-email"></span></p>
                <p><strong>Subject:</strong> <span id="modal-subject"></span></p>
                <p><strong>Deadline:</strong> <span id="modal-deadline"></span></p>
                <p><strong>Budget:</strong> <span id="modal-budget"></span></p>
                <hr>
                <h6>Full Description:</h6>
                <p id="modal-description"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var requestModal = document.getElementById('requestModal');
    requestModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        var button = event.relatedTarget;

        // Extract info from data-* attributes
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');
        var subject = button.getAttribute('data-subject');
        var description = button.getAttribute('data-description');
        var deadline = button.getAttribute('data-deadline');
        var budget = button.getAttribute('data-budget');

        // Update the modal's content.
        var modalTitle = requestModal.querySelector('.modal-title');
        modalTitle.textContent = 'Request from: ' + name;
        
        requestModal.querySelector('#modal-name').textContent = name;
        requestModal.querySelector('#modal-email').textContent = email;
        requestModal.querySelector('#modal-subject').textContent = subject;
        requestModal.querySelector('#modal-deadline').textContent = deadline;
        requestModal.querySelector('#modal-budget').textContent = budget;
        requestModal.querySelector('#modal-description').innerHTML = description;
    });
});
</script>

<?php $conn->close(); ?>