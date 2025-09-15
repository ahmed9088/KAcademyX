<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "../includes/header.php";

include "../includes/sidebar.php"; 
include "../includes/footer.php"; 

// Include database connection
include "../db.php";

// Handle success and error messages
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: list.php?error=Invalid instructor ID");
    exit();
}

// Validate ID is a number
$instructor_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($instructor_id === false) {
    header("Location: list.php?error=Invalid instructor ID");
    exit();
}

// Fetch instructor details
$query = "SELECT * FROM instructors WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list.php?error=Instructor not found");
    exit();
}

$instructor = $result->fetch_assoc();


// Set page title
$pageTitle = "View Instructor Details";
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Instructor Details</h6>
                    <div>
                        <a href="list.php" class="btn btn-secondary btn-sm me-2">
                            <i class="bi bi-arrow-left me-1"></i> Back to List
                        </a>
                        <a href="edit.php?id=<?php echo $instructor['id']; ?>" class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="position-relative">
                                <?php if (!empty($instructor['profile_image'])): ?>
                                    <img src="<?php echo '../' . $instructor['profile_image']; ?>" alt="Profile" class="rounded-circle img-thumbnail shadow" style="width: 200px; height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto shadow" style="width: 200px; height: 200px;">
                                        <i class="bi bi-person-fill text-white" style="font-size: 80px;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="position-absolute bottom-0 end-0 mb-3 me-3">
                                    <span class="badge bg-<?php echo !empty($instructor['profile_image']) ? 'success' : 'secondary'; ?> rounded-pill p-2">
                                        <i class="bi bi-<?php echo !empty($instructor['profile_image']) ? 'check-circle' : 'x-circle'; ?>"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <h4 class="mt-3"><?php echo htmlspecialchars($instructor['name']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($instructor['expertise']); ?></p>
                            
                            <div class="mt-4 d-flex justify-content-center">
                                <a href="edit.php?id=<?php echo $instructor['id']; ?>" class="btn btn-warning btn-sm me-2">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                <button type="button" class="btn btn-danger btn-sm delete-instructor" 
                                        data-id="<?php echo $instructor['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($instructor['name']); ?>">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </button>
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6 class="text-center">Quick Stats</h6>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Member Since:</span>
                                    <span class="fw-bold"><?php echo date('M Y', strtotime($instructor['created_at'])); ?></span>
                                </div>
                                <?php if (!empty($instructor['experience'])): ?>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Experience:</span>
                                    <span class="fw-bold"><?php echo $instructor['experience']; ?> years</span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($instructor['qualification'])): ?>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Qualification:</span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($instructor['qualification']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="card mb-3 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Personal Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-3 fw-bold text-muted">Full Name:</div>
                                        <div class="col-sm-9"><?php echo htmlspecialchars($instructor['name']); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3 fw-bold text-muted">Email:</div>
                                        <div class="col-sm-9">
                                            <a href="mailto:<?php echo htmlspecialchars($instructor['email']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($instructor['email']); ?>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3 fw-bold text-muted">Expertise:</div>
                                        <div class="col-sm-9">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($instructor['expertise']); ?></span>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3 fw-bold text-muted">Experience:</div>
                                        <div class="col-sm-9">
                                            <?php if (!empty($instructor['experience'])): ?>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo min($instructor['experience'] * 10, 100); ?>%"
                                                         aria-valuenow="<?php echo $instructor['experience']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $instructor['experience']; ?> years
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-3 fw-bold text-muted">Qualification:</div>
                                        <div class="col-sm-9">
                                            <?php if (!empty($instructor['qualification'])): ?>
                                                <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($instructor['qualification']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($instructor['bio'])): ?>
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Biography</h5>
                                </div>
                                <div class="card-body">
                                    <div class="p-3 bg-light rounded">
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($instructor['bio'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card mt-3 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Account Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-3 fw-bold text-muted">Member Since:</div>
                                        <div class="col-sm-9"><?php echo date('F j, Y', strtotime($instructor['created_at'])); ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-3 fw-bold text-muted">Instructor ID:</div>
                                        <div class="col-sm-9">
                                            <span class="badge bg-secondary">#<?php echo $instructor['id']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Are you sure you want to delete <strong id="instructorName"></strong>? This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <!-- Message will be inserted here -->
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle delete confirmation
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const instructorNameSpan = document.getElementById('instructorName');
        
        document.querySelectorAll('.delete-instructor').forEach(button => {
            button.addEventListener('click', function() {
                const instructorId = this.getAttribute('data-id');
                const instructorName = this.getAttribute('data-name');
                
                instructorNameSpan.textContent = instructorName;
                confirmDeleteBtn.href = `list.php?delete=${instructorId}`;
                
                deleteModal.show();
            });
        });
        
        // Show toast notification if there's a success message
        <?php if (!empty($success_message)): ?>
            const toast = new bootstrap.Toast(document.getElementById('liveToast'));
            document.querySelector('#liveToast .toast-body').textContent = '<?php echo addslashes($success_message); ?>';
            toast.show();
        <?php endif; ?>
    });
</script>
</body>
</html>