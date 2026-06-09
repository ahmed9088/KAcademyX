<?php
include "db.php";
session_start();

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email format.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check input errors before querying the database
    if (empty($email_err) && empty($password_err)) {
        
        // Prepare a select statement
        $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if email exists, if yes then verify password
                if ($stmt->num_rows == 1) {                    
                    // Bind result variables
                    $stmt->bind_result($id, $name, $email, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $email;
                            $_SESSION["user"] = $name;
                            
                            // Redirect user to welcome page
                            header("location: ../index.php");
                        } else {
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist, display a generic error message
                    $login_err = "Invalid email or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
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
    <title>Login - KAcademyX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body class="auth-body">

    <a href="../index.php" class="back-button position-absolute top-0 start-0 m-4">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>
    
    <div class="login-container">
        <div class="login-form">
            <h3>Login to KAcademyX</h3>
            <p class="auth-subtitle">Please enter your credentials to access your dashboard.</p>
            
            <?php 
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger d-flex align-items-center rounded-3 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> ' . $login_err . '</div>';
            }
            if (isset($_GET['signup']) && $_GET['signup'] == 'success') {
                echo '<div class="alert alert-success d-flex align-items-center rounded-3 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2 fs-5"></i> Your account has been created successfully! Please login.</div>';
            }
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group <?php echo (!empty($email_err)) ? 'is-invalid-group' : ''; ?>">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="email">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group <?php echo (!empty($password_err)) ? 'is-invalid-group' : ''; ?>">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required autocomplete="current-password">
                        <i class="bi bi-eye password-toggle-btn" id="passwordToggle"></i>
                    </div>
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                
                <div class="form-group d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <span>Login</span> <i class="bi bi-arrow-right"></i>
                </button>
                
                <div class="signup-link">
                    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            
            // Password eye toggle functionality
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
            
            // Client-side validation
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Validate email
                if (!emailInput.value.trim()) {
                    emailInput.closest('.form-group').classList.add('is-invalid-group');
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                } else if (!validateEmail(emailInput.value)) {
                    emailInput.closest('.form-group').classList.add('is-invalid-group');
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    emailInput.closest('.form-group').classList.remove('is-invalid-group');
                    emailInput.classList.remove('is-invalid');
                }
                
                // Validate password
                if (!passwordInput.value.trim()) {
                    passwordInput.closest('.form-group').classList.add('is-invalid-group');
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else if (passwordInput.value.length < 6) {
                    passwordInput.closest('.form-group').classList.add('is-invalid-group');
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    passwordInput.closest('.form-group').classList.remove('is-invalid-group');
                    passwordInput.classList.remove('is-invalid');
                }
                
                if (isValid) {
                    const submitBtn = form.querySelector('.btn-login');
                    submitBtn.classList.add('btn-loading');
                    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...`;
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
