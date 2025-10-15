<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$errors = [];
$success_message = '';
$form_data = ['name' => '', 'email' => '', 'subject' => '', 'description' => '', 'deadline' => '', 'budget' => ''];

// Pre-fill form if user is logged in
if (isset($_SESSION['user_id'])) {
    $form_data['name'] = $_SESSION['user_name'];
    $form_data['email'] = $_SESSION['user_email'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and store form data
    $form_data['name'] = trim($_POST['name']);
    $form_data['email'] = trim($_POST['email']);
    $form_data['subject'] = trim($_POST['subject']);
    $form_data['description'] = trim($_POST['description']);
    $form_data['deadline'] = trim($_POST['deadline']);
    $form_data['budget'] = trim($_POST['budget']);
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Validation
    if (empty($form_data['name']))
        $errors[] = 'Your name is required.';
    if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email is required.';
    if (empty($form_data['subject']))
        $errors[] = 'Project subject is required.';
    if (empty($form_data['description']))
        $errors[] = 'A detailed project description is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO custom_project_requests (user_id, name, email, subject, description, deadline, budget) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // Set deadline to null if empty
        $deadline = !empty($form_data['deadline']) ? $form_data['deadline'] : null;
        $stmt->bind_param("issssss", $userId, $form_data['name'], $form_data['email'], $form_data['subject'], $form_data['description'], $deadline, $form_data['budget']);

        if ($stmt->execute()) {
            // Step 1: Store the success message in the session
            $_SESSION['success_message'] = "Your request has been submitted successfully! We will contact you via email shortly.";

            // Step 2: Redirect the browser to the same page (via a GET request)
            header('Location: request_project.php');
            exit(); // Crucial: exit() stops the script from running further
        } else {
            $errors[] = 'There was an error submitting your request. Please try again.';
        }
        $stmt->close();
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h1 class="text-center mt-4">Request a Custom Project</h1>
        <p class="text-center text-muted mb-4">Have a specific requirement? Fill out the form below and our team will
            get back to you with a quote.</p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
            <?php unset($_SESSION['success_message']); // Unset the message so it doesn't show again on the next refresh ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-4">
                <form action="request_project.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?php echo htmlspecialchars($form_data['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Your Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Project Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject"
                            placeholder="e.g., Mechanical Engineering, MBA Marketing"
                            value="<?php echo htmlspecialchars($form_data['subject']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Detailed Project Description</label>
                        <textarea class="form-control" id="description" name="description" rows="6"
                            placeholder="Please provide as much detail as possible, including requirements, features, and technologies."
                            required><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">Deadline (Optional)</label>
                            <input type="date" class="form-control" id="deadline" name="deadline"
                                value="<?php echo htmlspecialchars($form_data['deadline']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="budget" class="form-label">Budget (Optional)</label>
                            <input type="text" class="form-control" id="budget" name="budget"
                                placeholder="e.g., ₹5,000 - ₹10,000"
                                value="<?php echo htmlspecialchars($form_data['budget']); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>