<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// ... (The POST and GET handling logic for create/update/delete remains the same) ...
// Handle form submission for CREATING a new employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_employee'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($name) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO employees (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Employee account created successfully!";
        } else {
            $_SESSION['message'] = "Error: Email might already be in use.";
        }
    } else {
        $_SESSION['message'] = "Error: Please fill all fields with valid data.";
    }
    header('Location: manage_employees.php');
    exit;
}

// Handle form submission for UPDATING an employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_employee'])) {
    $id = intval($_POST['employee_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // New password, can be empty

    if (!empty($name) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE employees SET name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $hashed_password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE employees SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = "Employee details updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating employee: " . $stmt->error;
        }
    } else {
        $_SESSION['message'] = "Error: Please provide a valid name and email.";
    }
    header('Location: manage_employees.php');
    exit;
}


// Handle DELETE action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $_SESSION['message'] = "Employee deleted successfully.";
    header('Location: manage_employees.php');
    exit;
}


// *** NEW: Updated query to count projects for each employee ***
$employees_result = $conn->query("
    SELECT e.id, e.name, e.email, e.created_at, COUNT(p.id) AS project_count
    FROM employees e
    LEFT JOIN projects p ON e.id = p.submitted_by_id
    GROUP BY e.id, e.name, e.email, e.created_at
    ORDER BY e.name ASC
");
?>
<!-- Style block to fix text visibility in the modal -->
<style>
    #editEmployeeModal .modal-body .form-label,
    #editEmployeeModal .modal-title {
        color: #212529 !important; /* Force dark text color for readability */
    }
</style>

<div class="container-fluid">
    <h1 class="mt-4">Manage Employees</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Create Employee Form -->
    <div class="card mb-4">
       <!-- ... (create form remains the same) ... -->
        <div class="card-header"><h4>Create New Employee Account</h4></div>
        <div class="card-body">
            <form action="manage_employees.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-4"><input type="text" class="form-control" name="name" placeholder="Full Name" required></div>
                    <div class="col-md-4"><input type="email" class="form-control" name="email" placeholder="Email Address" required></div>
                    <div class="col-md-4"><input type="password" class="form-control" name="password" placeholder="Set Temporary Password" required></div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="create_employee" class="btn btn-primary">Create Employee</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Existing Employees Table -->
    <div class="card">
        <div class="card-header"><h4>Existing Employees</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <!-- *** NEW: Added Project Count column *** -->
                        <tr><th>Name</th><th>Email</th><th>Project Count</th><th>Date Joined</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($employees_result->num_rows > 0): ?>
                            <?php while($employee = $employees_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <!-- *** NEW: Display the project count *** -->
                                    <td><?php echo $employee['project_count']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($employee['created_at'])); ?></td>
                                    <td>
                                        <!-- *** NEW: View Projects button *** -->
                                        <a href="view_employee_projects.php?id=<?php echo $employee['id']; ?>" class="btn btn-success btn-sm">View Projects</a>
                                        
                                        <button type="button" class="btn btn-primary btn-sm edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editEmployeeModal"
                                                data-id="<?php echo $employee['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                                data-email="<?php echo htmlspecialchars($employee['email']); ?>">
                                            Edit
                                        </button>
                                        <a href="manage_employees.php?action=delete&id=<?php echo $employee['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No employees created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<!-- ... (modal and script remain the same) ... -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="manage_employees.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="employee_id" id="edit_employee_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password (Optional)</label>
                        <input type="password" class="form-control" name="password" id="edit_password" placeholder="Leave blank to keep current password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_employee" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editEmployeeModal = document.getElementById('editEmployeeModal');
    editEmployeeModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var employeeId = button.getAttribute('data-id');
        var employeeName = button.getAttribute('data-name');
        var employeeEmail = button.getAttribute('data-email');
        var modalTitle = editEmployeeModal.querySelector('.modal-title');
        var inputId = editEmployeeModal.querySelector('#edit_employee_id');
        var inputName = editEmployeeModal.querySelector('#edit_name');
        var inputEmail = editEmployeeModal.querySelector('#edit_email');
        var inputPassword = editEmployeeModal.querySelector('#edit_password');
        modalTitle.textContent = 'Edit Employee: ' + employeeName;
        inputId.value = employeeId;
        inputName.value = employeeName;
        inputEmail.value = employeeEmail;
        inputPassword.value = '';
    });
});
</script>

<?php
$conn->close();
require_once 'partials/footer.php';
?>

