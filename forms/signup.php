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
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", trim($_POST["password"]))) {
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body class="auth-body">

    <a href="../index.php" class="back-button position-absolute top-0 start-0 m-4">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>
    
    <div class="signup-container">
        <div class="signup-form">
            <h3>Create Your Account</h3>
            <p class="auth-subtitle">Fill in the details below to register.</p>
            
            <?php 
            if (!empty($signup_err)) {
                echo '<div class="alert alert-danger d-flex align-items-center rounded-3 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> ' . $signup_err . '</div>';
            }
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group <?php echo (!empty($name_err)) ? 'is-invalid-group' : ''; ?>">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>" required autocomplete="name">
                    <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    <div class="form-text">Your name as it will appear on your certificate.</div>
                </div>
                
                <div class="form-group <?php echo (!empty($username_err)) ? 'is-invalid-group' : ''; ?>">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required autocomplete="username">
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>
                
                <div class="form-group <?php echo (!empty($email_err)) ? 'is-invalid-group' : ''; ?>">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="email">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group avatar-selection">
                    <label>Choose Your Avatar</label>
                    <div class="avatar-options">
                        <?php 
                        $avatars = ['avatar1', 'avatar2', 'avatar3', 'avatar4', 'avatar5', 'avatar6'];
                        foreach ($avatars as $av):
                            $isSelected = ($avatar === $av) ? 'selected' : '';
                        ?>
                            <div class="avatar-option <?php echo $isSelected; ?>" data-avatar="<?php echo $av; ?>">
                                <img src="../assets/avatars/<?php echo $av; ?>.png" alt="<?php echo ucfirst($av); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="avatar" id="selectedAvatar" value="<?php echo htmlspecialchars($avatar); ?>" required>
                    <span class="invalid-feedback d-block" id="avatarError"><?php echo $avatar_err; ?></span>
                </div>
                
                <div class="form-group <?php echo (!empty($password_err)) ? 'is-invalid-group' : ''; ?>">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required autocomplete="new-password">
                        <i class="bi bi-eye password-toggle-btn" id="passwordToggle"></i>
                    </div>
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    
                    <div class="password-strength">
                        <div class="password-strength-meter" id="passwordStrength"></div>
                    </div>
                    
                    <ul class="password-criteria-list">
                        <li class="password-criteria-item" id="critLength">
                            <i class="bi bi-circle"></i> 8+ Characters
                        </li>
                        <li class="password-criteria-item" id="critUpper">
                            <i class="bi bi-circle"></i> 1 Uppercase (A-Z)
                        </li>
                        <li class="password-criteria-item" id="critLower">
                            <i class="bi bi-circle"></i> 1 Lowercase (a-z)
                        </li>
                        <li class="password-criteria-item" id="critNumber">
                            <i class="bi bi-circle"></i> 1 Number (0-9)
                        </li>
                    </ul>
                </div>
                
                <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'is-invalid-group' : ''; ?>">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required autocomplete="new-password">
                        <i class="bi bi-eye password-toggle-btn" id="confirmPasswordToggle"></i>
                    </div>
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I agree to the <a href="#">Terms of Service</a> &amp; <a href="#">Privacy Policy</a>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-signup">
                    <span>Create Account</span> <i class="bi bi-arrow-right"></i>
                </button>
                
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
            const avatarError = document.getElementById('avatarError');
            
            const passwordToggle = document.getElementById('passwordToggle');
            const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
            
            // Password criteria list items
            const critLength = document.getElementById('critLength');
            const critUpper = document.getElementById('critUpper');
            const critLower = document.getElementById('critLower');
            const critNumber = document.getElementById('critNumber');

            // Password toggles
            if (passwordToggle) {
                passwordToggle.addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        passwordToggle.classList.remove('bi-eye');
                        passwordToggle.classList.add('bi-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        passwordToggle.classList.remove('bi-eye-slash');
                        passwordToggle.classList.add('bi-eye');
                    }
                });
            }
            if (confirmPasswordToggle) {
                confirmPasswordToggle.addEventListener('click', function() {
                    if (confirmPasswordInput.type === 'password') {
                        confirmPasswordInput.type = 'text';
                        confirmPasswordToggle.classList.remove('bi-eye');
                        confirmPasswordToggle.classList.add('bi-eye-slash');
                    } else {
                        confirmPasswordInput.type = 'password';
                        confirmPasswordToggle.classList.remove('bi-eye-slash');
                        confirmPasswordToggle.classList.add('bi-eye');
                    }
                });
            }

            // Avatar selection
            avatarOptions.forEach(option => {
                option.addEventListener('click', function() {
                    avatarOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedAvatarInput.value = this.getAttribute('data-avatar');
                    avatarError.textContent = '';
                });
            });
            
            // Password criteria & strength checker
            passwordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                let strength = 0;
                
                // 1. Length Criterion
                if (password.length >= 8) {
                    setCriterionMet(critLength, true);
                    strength++;
                } else {
                    setCriterionMet(critLength, false);
                }
                
                // 2. Lowercase Criterion
                if (password.match(/[a-z]+/)) {
                    setCriterionMet(critLower, true);
                    strength++;
                } else {
                    setCriterionMet(critLower, false);
                }
                
                // 3. Uppercase Criterion
                if (password.match(/[A-Z]+/)) {
                    setCriterionMet(critUpper, true);
                    strength++;
                } else {
                    setCriterionMet(critUpper, false);
                }
                
                // 4. Number Criterion
                if (password.match(/[0-9]+/)) {
                    setCriterionMet(critNumber, true);
                    strength++;
                } else {
                    setCriterionMet(critNumber, false);
                }
                
                // Update strength meter UI
                passwordStrength.className = 'password-strength-meter';
                if (strength === 1 || strength === 2) {
                    passwordStrength.classList.add('weak');
                } else if (strength === 3) {
                    passwordStrength.classList.add('medium');
                } else if (strength === 4) {
                    passwordStrength.classList.add('strong');
                }
            });

            function setCriterionMet(element, isMet) {
                const icon = element.querySelector('i');
                if (isMet) {
                    element.classList.add('met');
                    icon.className = 'bi bi-check-circle-fill';
                } else {
                    element.classList.remove('met');
                    icon.className = 'bi bi-circle';
                }
            }
            
            // Client-side form validation
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Validate name
                if (!nameInput.value.trim() || nameInput.value.trim().length < 3 || !/^[a-zA-Z\s]+$/.test(nameInput.value.trim())) {
                    nameInput.closest('.form-group').classList.add('is-invalid-group');
                    nameInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    nameInput.closest('.form-group').classList.remove('is-invalid-group');
                    nameInput.classList.remove('is-invalid');
                }
                
                // Validate username
                if (!usernameInput.value.trim() || usernameInput.value.trim().length < 3 || !/^[a-zA-Z0-9_]+$/.test(usernameInput.value.trim())) {
                    usernameInput.closest('.form-group').classList.add('is-invalid-group');
                    usernameInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    usernameInput.closest('.form-group').classList.remove('is-invalid-group');
                    usernameInput.classList.remove('is-invalid');
                }
                
                // Validate email
                if (!emailInput.value.trim() || !validateEmail(emailInput.value)) {
                    emailInput.closest('.form-group').classList.add('is-invalid-group');
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    emailInput.closest('.form-group').classList.remove('is-invalid-group');
                    emailInput.classList.remove('is-invalid');
                }
                
                // Validate avatar selection
                if (!selectedAvatarInput.value) {
                    avatarError.textContent = 'Please choose an avatar.';
                    isValid = false;
                }
                
                // Validate password strength criteria
                const password = passwordInput.value;
                const isPassStrong = password.length >= 8 && /[a-z]+/.test(password) && /[A-Z]+/.test(password) && /[0-9]+/.test(password);
                if (!isPassStrong) {
                    passwordInput.closest('.form-group').classList.add('is-invalid-group');
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    passwordInput.closest('.form-group').classList.remove('is-invalid-group');
                    passwordInput.classList.remove('is-invalid');
                }
                
                // Validate confirm password
                if (!confirmPasswordInput.value.trim() || passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.closest('.form-group').classList.add('is-invalid-group');
                    confirmPasswordInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    confirmPasswordInput.closest('.form-group').classList.remove('is-invalid-group');
                    confirmPasswordInput.classList.remove('is-invalid');
                }
                
                if (isValid) {
                    const submitBtn = form.querySelector('.btn-signup');
                    submitBtn.classList.add('btn-loading');
                    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating Account...`;
                } else {
                    event.preventDefault();
                }
            });
            
            function validateEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(String(email).toLowerCase());
            }
        });
    </script>
</body>
</html>