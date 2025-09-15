<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include "../db.php";

// Include header after processing redirects
include "../includes/header.php";

include "../includes/sidebar.php"; 
include "../includes/footer.php"; 

// Handle delete action with proper security
if (isset($_GET['delete'])) {
    // Validate ID is a number
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($id === false) {
        // Invalid ID
        header("Location: list.php?error=Invalid instructor ID");
        exit();
    }
    
    // Check if instructor exists
    $check_query = "SELECT profile_image FROM instructors WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Instructor not found
        header("Location: list.php?error=Instructor not found");
        exit();
    }
    
    $instructor = $result->fetch_assoc();
    $profile_image = $instructor['profile_image'];
    
    // Delete the instructor
    $delete_query = "DELETE FROM instructors WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete the profile image if it exists
        if (!empty($profile_image)) {
            $image_path = "../" . $profile_image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        header("Location: list.php?success=Instructor deleted successfully");
        exit();
    } else {
        header("Location: list.php?error=Failed to delete instructor");
        exit();
    }
}

// Handle success and error messages
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Set page title
$pageTitle = "Manage Instructors";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - KAcademyX</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4154f1;
            --secondary-color: #717ff5;
            --accent-color: #ff6b6b;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        body {
            background-color: #f5f7fb;
            color: #344767;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 1.2rem 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(65, 84, 241, 0.4);
        }
        
        .btn-sm {
            border-radius: 6px;
            padding: 0.25rem 0.75rem;
        }
        
        .table th {
            font-weight: 600;
            color: #344767;
            border-top: none;
            padding: 1rem 0.75rem;
            background-color: #f8f9fa;
        }
        
        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(65, 84, 241, 0.03);
        }
        
        .action-buttons .btn {
            margin-right: 0.4rem;
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .page-title {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        
        .profile-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            border-bottom: 1px solid #e9ecef;
            padding: 1.25rem 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
        }
        
        .dataTables_wrapper {
            padding: 0;
        }
        
        .dataTables_filter input {
            border-radius: 6px;
            border: 1px solid #d2d6da;
            padding: 0.375rem 0.75rem;
        }
        
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #c2c7d0;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header .btn {
                margin-top: 1rem;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: column;
            }
            
            .action-buttons .btn {
                margin-bottom: 0.5rem;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 page-title">Instructor Management</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Instructors</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Notifications -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">Instructors List</h6>
                        <a href="add.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Add New Instructor
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        $result = $conn->query("SELECT * FROM instructors ORDER BY created_at DESC");
                        if ($result->num_rows > 0):
                        ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="instructorsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Profile</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Expertise</th>
                                        <th>Experience</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td>{$row['id']}</td>
                                                <td>";
                                        
                                        // Display profile image if available
                                        if (!empty($row['profile_image'])) {
                                            echo "<img src='../{$row['profile_image']}' alt='Profile' class='profile-img'>";
                                        } else {
                                            echo "<div class='rounded-circle bg-secondary d-flex align-items-center justify-content-center' style='width: 45px; height: 45px;'>
                                                    <i class='bi bi-person-fill text-white'></i>
                                                  </div>";
                                        }
                                        
                                        echo "</td>
                                                <td>" . htmlspecialchars($row['name']) . "</td>
                                                <td>" . htmlspecialchars($row['email']) . "</td>
                                                <td><span class='badge bg-primary status-badge'>" . htmlspecialchars($row['expertise']) . "</span></td>
                                                <td>" . ($row['experience'] ? $row['experience'] . ' years' : 'N/A') . "</td>
                                                <td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>
                                                <td class='action-buttons'>
                                                    <a href='view.php?id={$row['id']}' class='btn btn-info btn-sm' title='View'>
                                                        <i class='bi bi-eye'></i>
                                                    </a>
                                                    <a href='edit.php?id={$row['id']}' class='btn btn-warning btn-sm' title='Edit'>
                                                        <i class='bi bi-pencil'></i>
                                                    </a>
                                                    <button type='button' class='btn btn-danger btn-sm delete-btn' 
                                                            data-id='{$row['id']}' 
                                                            data-name='" . htmlspecialchars($row['name']) . "' 
                                                            title='Delete'>
                                                        <i class='bi bi-trash'></i>
                                                    </button>
                                                </td>
                                              </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-person-x"></i>
                            <h5>No instructors found</h5>
                            <p class="text-muted">Start by adding a new instructor to the system.</p>
                            <a href="add.php" class="btn btn-primary mt-3">
                                <i class="bi bi-plus-circle me-1"></i> Add New Instructor
                            </a>
                        </div>
                        <?php endif; ?>
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
                    <p>Are you sure you want to delete <strong id="instructorName"></strong>? This action cannot be undone.</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> All associated data will be permanently removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete Instructor</a>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            $('#instructorsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                order: [[0, "desc"]],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search instructors...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });
            
            // Handle delete confirmation
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const instructorNameSpan = document.getElementById('instructorName');
            
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const instructorId = this.getAttribute('data-id');
                    const instructorName = this.getAttribute('data-name');
                    
                    instructorNameSpan.textContent = instructorName;
                    confirmDeleteBtn.href = `list.php?delete=${instructorId}`;
                    
                    deleteModal.show();
                });
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
