<?php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Add New Instructor";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Define upload directory
$upload_dir = "../uploads/instructors/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $expertise = $_POST['expertise'];
    $bio = $_POST['bio'];
    $experience = $_POST['experience'];
    $qualification = $_POST['qualification'];
    $profile_image = "";
    
    // Validate input
    if (empty($name) || empty($email) || empty($expertise)) {
        $error = "Name, email, and expertise are required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT id FROM instructors WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                    $error = "Only JPG, JPEG, PNG, and GIF images are allowed!";
                } elseif ($_FILES['profile_image']['size'] > $max_size) {
                    $error = "Image size must be less than 5MB!";
                } else {
                    $file_name = time() . '_' . $_FILES['profile_image']['name'];
                    $file_tmp = $_FILES['profile_image']['tmp_name'];
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $profile_image = "Admin/uploads/instructors/" . $file_name;
                    } else {
                        $error = "Failed to upload profile image!";
                    }
                }
            }
            
            // If no errors, proceed with database insertion
            if (!isset($error)) {
                $sql = "INSERT INTO instructors (name, email, expertise, bio, experience, qualification, profile_image) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssiss", $name, $email, $expertise, $bio, $experience, $qualification, $profile_image);
                
                if ($stmt->execute()) {
                    header("Location: list.php?success=1");
                    exit();
                } else {
                    $error = "Error: " . $conn->error;
                }
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Instructor</h6>
                    <a href="list.php" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="instructorForm" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                <div class="invalid-feedback">
                                    Please provide a valid name.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <div class="invalid-feedback">
                                    Please provide a valid email.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="expertise" class="form-label">Area of Expertise <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="expertise" name="expertise" required 
                                   value="<?php echo isset($_POST['expertise']) ? htmlspecialchars($_POST['expertise']) : ''; ?>"
                                   placeholder="e.g., Physics, Computer Science, Biology, etc.">
                            <div class="invalid-feedback">
                                Please provide area of expertise.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Biography (Optional)</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" 
                                      placeholder="Brief description about the instructor..."><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="experience" class="form-label">Years of Experience (Optional)</label>
                                <input type="number" class="form-control" id="experience" name="experience" min="0" 
                                       value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="qualification" class="form-label">Highest Qualification (Optional)</label>
                                <input type="text" class="form-control" id="qualification" name="qualification" 
                                       value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>"
                                       placeholder="e.g., PhD, Master's, Bachelor's">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="profile_image" class="form-label">Profile Image (Optional)</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <small class="form-text text-muted">Allowed formats: JPG, JPEG, PNG, GIF. Maximum size: 5MB.</small>
                            
                            <?php if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0): ?>
                                <div class="mt-2">
                                    <img src="<?php echo $_FILES['profile_image']['tmp_name']; ?>" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="list.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Instructor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var form = document.getElementById('instructorForm');
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        }, false);
    })();
    
    // Preview image before upload
    document.getElementById('profile_image').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.createElement('div');
                preview.className = 'mt-2';
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="img-thumbnail" style="max-height: 150px;">';
                
                // Remove any existing preview
                var existingPreview = document.querySelector('.img-thumbnail');
                if (existingPreview) {
                    existingPreview.parentElement.remove();
                }
                
                // Add new preview
                document.getElementById('profile_image').parentNode.appendChild(preview);
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>