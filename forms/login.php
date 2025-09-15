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
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
        }
        .login-image {
            flex: 1;
            background: linear-gradient(135deg, #4154f1 0%, #7b68ee 100%);
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 500px;
        }
        .login-image h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .login-image p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        .login-form {
            flex: 1;
            padding: 40px;
        }
        .login-form h3 {
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
        .btn-login {
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
        .btn-login:hover {
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
        .form-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        .signup-link {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }
        .signup-link a {
            color: #4154f1;
            font-weight: 600;
            text-decoration: none;
        }
        .signup-link a:hover {
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
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
            }
            .login-image {
                min-height: 200px;
                padding: 30px;
            }
            .login-image h2 {
                font-size: 2rem;
            }
            .login-form {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>
    
    <div class="login-container">
        <div class="login-image">
            <h2>Welcome Back!</h2>
            <p>Login to access your courses, track your progress, and continue your learning journey with KAcademyX.</p>
            <div class="mt-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span>Access all premium courses</span>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span>Track your learning progress</span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span>Get personalized recommendations</span>
                </div>
            </div>
        </div>
        
        <div class="login-form">
            <h3>Login to Your Account</h3>
            
            <?php 
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    <div class="form-text">We'll never share your email with anyone else.</div>
                    <i class="bi bi-envelope"></i>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    <div class="form-text">Use 6 or more characters with a mix of letters and numbers.</div>
                    <i class="bi bi-lock"></i>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-login">Login</button>
                </div>
                
                <div class="signup-link">
                    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
                    <p><a href="forgot-password.php">Forgot your password?</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple client-side validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
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
                
                // Validate password
                if (!passwordInput.value.trim()) {
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else if (passwordInput.value.length < 6) {
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    passwordInput.classList.remove('is-invalid');
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
