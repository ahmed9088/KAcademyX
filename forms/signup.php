<?php
include "db.php";
session_start();
// Check if user is already logged in
if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}
// Initialize variables
$name = $email = $username = $password = $confirm_password = $avatar = "";
$name_err = $email_err = $username_err = $password_err = $confirm_password_err = $avatar_err = $signup_err = "";
// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your full name.";
    } elseif (strlen(trim($_POST["name"])) < 3) {
        $name_err = "Name must have at least 3 characters.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", trim($_POST["name"]))) {
        $name_err = "Name can only contain letters and spaces.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email format.";
    } else {
        // Check if email already exists
        $email = trim($_POST["email"]);
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $email_err = "This email is already in use.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (strlen(trim($_POST["username"])) < 3) {
        $username_err = "Username must have at least 3 characters.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Check if username already exists
        $username = trim($_POST["username"]);
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $username_err = "This username is already taken.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 8) {
        $password_err = "Password must have at least 8 characters.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/", trim($_POST["password"]))) {
        $password_err = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // Validate avatar
    if (empty($_POST["avatar"])) {
        $avatar_err = "Please select an avatar.";
    } else {
        $avatar = $_POST["avatar"];
        // Validate avatar is one of the allowed options
        $allowed_avatars = ['avatar1', 'avatar2', 'avatar3', 'avatar4', 'avatar5', 'avatar6'];
        if (!in_array($avatar, $allowed_avatars)) {
            $avatar_err = "Please select a valid avatar.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($name_err) && empty($email_err) && empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($avatar_err)) {
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Prepare an insert statement for users table
            $sql = "INSERT INTO users (username, name, email, password, avatar) VALUES (?, ?, ?, ?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("sssss", $param_username, $param_name, $param_email, $param_password, $param_avatar);
                
                // Set parameters
                $param_username = $username;
                $param_name = $name;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                $param_avatar = 'avatars/' . $avatar . '.png'; // Add path and file extension
                
                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Insert into students table
                    $sql = "INSERT INTO students (user_id, username, name, email, avatar) VALUES (?, ?, ?, ?, ?)";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("issss", $user_id, $param_username, $param_name, $param_email, $param_avatar);
                        
                        if ($stmt->execute()) {
                            // Commit transaction
                            $conn->commit();
                            
                            // Redirect to login page
                            header("location: login.php?signup=success");
                        } else {
                            throw new Exception("Error inserting into students table.");
                        }
                    } else {
                        throw new Exception("Error preparing students insert statement.");
                    }
                } else {
                    throw new Exception("Error inserting into users table.");
                }
                // Close statement
                $stmt->close();
            } else {
                throw new Exception("Error preparing users insert statement.");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $signup_err = "Registration failed: " . $e->getMessage();
        }
    }
    
    // Close connection
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - KAcademyX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
        }
        .signup-image {
            flex: 1;
            background: linear-gradient(135deg, #4154f1 0%, #7b68ee 100%);
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 500px;
        }
        .signup-image h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .signup-image p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        .signup-form {
            flex: 1;
            padding: 40px;
        }
        .signup-form h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #555;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #4154f1;
            box-shadow: 0 0 0 0.2rem rgba(65, 84, 241, 0.25);
        }
        .form-group i {
            position: absolute;
            right: 15px;
            top: 42px;
            color: #999;
        }
        .btn-signup {
            background: linear-gradient(45deg, #4154f1, #7b68ee);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(65, 84, 241, 0.4);
            background: linear-gradient(45deg, #3141c5, #6a5acd);
        }
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: none;
        }
        .alert-danger {
            background-color: #fff5f5;
            color: #e53e3e;
        }
        .alert-success {
            background-color: #f0fff4;
            color: #38a169;
        }
        .form-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        .login-link {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }
        .login-link a {
            color: #4154f1;
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #4154f1;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .back-link:hover {
            color: #3141c5;
        }
        .password-strength {
            height: 5px;
            margin-top: 8px;
            border-radius: 5px;
            background-color: #e0e0e0;
            overflow: hidden;
        }
        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .weak {
            width: 33%;
            background-color: #e53e3e;
        }
        .medium {
            width: 66%;
            background-color: #dd6b20;
        }
        .strong {
            width: 100%;
            background-color: #38a169;
        }
        /* Avatar Selection Styles */
        .avatar-selection {
            margin-bottom: 25px;
        }
        .avatar-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .avatar-option {
            position: relative;
            cursor: pointer;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .avatar-option:hover {
            transform: scale(1.05);
            border-color: #4154f1;
        }
        .avatar-option.selected {
            border-color: #4154f1;
            box-shadow: 0 0 0 3px rgba(65, 84, 241, 0.3);
        }
        .avatar-option img {
            width: 60px;
            height: 60px;
            object-fit: cover;
        }
        .avatar-option .checkmark {
            position: absolute;
            top: 0;
            right: 0;
            background: #4154f1;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .avatar-option.selected .checkmark {
            opacity: 1;
        }
        @media (max-width: 768px) {
            .signup-container {
                flex-direction: column;
                max-width: 400px;
            }
            .signup-image {
                min-height: 200px;
                padding: 30px;
            }
            .signup-image h2 {
                font-size: 2rem;
            }
            .signup-form {
                padding: 30px;
            }
            .avatar-options {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>
    
    <div class="signup-container">
        <div class="signup-image">
            <h2>Join KAcademyX</h2>
            <p>Create your account to start your learning journey. Access premium courses, track your progress, and connect with expert instructors.</p>
            <div class="mt-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span>Access 100+ premium courses</span>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span>Learn at your own pace</span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span>Get certificates of completion</span>
                </div>
            </div>
        </div>
        
        <div class="signup-form">
            <h3>Create Your Account</h3>
            
            <?php 
            if (isset($_GET['signup']) && $_GET['signup'] == 'success') {
                echo '<div class="alert alert-success">Your account has been created successfully! Please login.</div>';
            }
            if (!empty($signup_err)) {
                echo '<div class="alert alert-danger">' . $signup_err . '</div>';
            }
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" required>
                    <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    <div class="form-text">Use your real name as it will appear on your certificate.</div>
                    <i class="bi bi-person"></i>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    <div class="form-text">This will be your unique identifier. Use only letters, numbers, and underscores.</div>
                    <i class="bi bi-person-badge"></i>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    <div class="form-text">We'll never share your email with anyone else.</div>
                    <i class="bi bi-envelope"></i>
                </div>
                
                <div class="form-group avatar-selection">
                    <label>Choose Your Avatar</label>
                    <div class="avatar-options">
                        <div class="avatar-option" data-avatar="avatar1">
                            <img src="../assets/avatars/avatar1.png" alt="Avatar 1">
                            <div class="checkmark">
                                <i class="bi bi-check"></i>
                            </div>
                        </div>
                        <div class="avatar-option" data-avatar="avatar2">
                            <img src="../assets/avatars/avatar2.png" alt="Avatar 2">
                            <div class="checkmark">
                                <i class="bi bi-check"></i>
                            </div>
                        </div>
                        <div class="avatar-option" data-avatar="avatar3">
                            <img src="../assets/avatars/avatar3.png" alt="Avatar 3">
                            <div class="checkmark">
                                <i class="bi bi-check"></i>
                            </div>
                        </div>
                        <div class="avatar-option" data-avatar="avatar4">
                            <img src="../assets/avatars/avatar4.png" alt="Avatar 4">
                            <div class="checkmark">
                                <i class="bi bi-check"></i>
                            </div>
                        </div>
                        <div class="avatar-option" data-avatar="avatar5">
                            <img src="../assets/avatars/avatar5.png" alt="Avatar 5">
                            <div class="checkmark">
                                <i class="bi bi-check"></i>
                            </div>
                        </div>
                        <div class="avatar-option" data-avatar="avatar6">
                            <img src="../assets/avatars/avatar6.png" alt="Avatar 6">
                            <div class="checkmark">
                                <i class="bi bi-check"></i>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="avatar" id="selectedAvatar" value="<?php echo $avatar; ?>" required>
                    <span class="invalid-feedback"><?php echo $avatar_err; ?></span>
                    <div class="form-text">Select an avatar that represents you. This will be visible on your profile.</div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    <div class="form-text">Use 8 or more characters with a mix of uppercase, lowercase, and numbers.</div>
                    <div class="password-strength">
                        <div class="password-strength-meter" id="passwordStrength"></div>
                    </div>
                    <i class="bi bi-lock"></i>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required>
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    <i class="bi bi-lock-fill"></i>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </label>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-signup">Create Account</button>
                </div>
                
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const nameInput = document.getElementById('name');
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const selectedAvatarInput = document.getElementById('selectedAvatar');
            const avatarOptions = document.querySelectorAll('.avatar-option');
            
            // Avatar selection
            avatarOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    avatarOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Update hidden input with selected avatar
                    selectedAvatarInput.value = this.getAttribute('data-avatar');
                });
            });
            
            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]+/)) strength++;
                if (password.match(/[A-Z]+/)) strength++;
                if (password.match(/[0-9]+/)) strength++;
                
                passwordStrength.className = 'password-strength-meter';
                if (strength === 1) {
                    passwordStrength.classList.add('weak');
                } else if (strength === 2 || strength === 3) {
                    passwordStrength.classList.add('medium');
                } else if (strength === 4) {
                    passwordStrength.classList.add('strong');
                }
            });
            
            // Form validation
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Validate name
                if (!nameInput.value.trim()) {
                    nameInput.classList.add('is-invalid');
                    isValid = false;
                } else if (nameInput.value.trim().length < 3) {
                    nameInput.classList.add('is-invalid');
                    isValid = false;
                } else if (!/^[a-zA-Z\s]+$/.test(nameInput.value.trim())) {
                    nameInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    nameInput.classList.remove('is-invalid');
                }
                
                // Validate username
                if (!usernameInput.value.trim()) {
                    usernameInput.classList.add('is-invalid');
                    isValid = false;
                } else if (usernameInput.value.trim().length < 3) {
                    usernameInput.classList.add('is-invalid');
                    isValid = false;
                } else if (!/^[a-zA-Z0-9_]+$/.test(usernameInput.value.trim())) {
                    usernameInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    usernameInput.classList.remove('is-invalid');
                }
                
                // Validate email
                if (!emailInput.value.trim()) {
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                } else if (!validateEmail(emailInput.value)) {
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    emailInput.classList.remove('is-invalid');
                }
                
                // Validate avatar selection
                if (!selectedAvatarInput.value) {
                    isValid = false;
                    // Show error or highlight the avatar selection
                }
                
                // Validate password
                if (!passwordInput.value.trim()) {
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else if (passwordInput.value.length < 8) {
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/.test(passwordInput.value)) {
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    passwordInput.classList.remove('is-invalid');
                }
                
                // Validate confirm password
                if (!confirmPasswordInput.value.trim()) {
                    confirmPasswordInput.classList.add('is-invalid');
                    isValid = false;
                } else if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    confirmPasswordInput.classList.remove('is-invalid');
                }
                
                // Validate terms checkbox
                const agreeTerms = document.getElementById('agreeTerms');
                if (!agreeTerms.checked) {
                    isValid = false;
                    // Show message or highlight the checkbox
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
            
            // Email validation function
            function validateEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(String(email).toLowerCase());
            }
        });
    </script>
</body>
</html>