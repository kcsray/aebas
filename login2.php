<?php
// Initialize the session
session_start();
$pfile = "";
if(isset($_SESSION["pfile"])) {
    $pfile = $_SESSION["pfile"];
} else {
    $pfile = "addIssue.php";
}

// Check if the user is already logged in, if yes then redirect
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ".$pfile);
    exit;
}
 
// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $captcha_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CAPTCHA first
    if(empty($_POST['g-recaptcha-response'])) {
        $captcha_err = "Please complete the CAPTCHA verification.";
    } else {
        $secret_key = 'YOUR_SECRET_KEY'; // Replace with your actual secret key
        $response = $_POST['g-recaptcha-response'];
        
        // Verify the CAPTCHA response
        $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret_key.'&response='.$response);
        $response_data = json_decode($verify_response);
        
        if(!$response_data->success) {
            $captcha_err = "CAPTCHA verification failed. Please try again.";
        }
    }
    
    // Check if username is empty
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials only if CAPTCHA is valid
    if(empty($username_err) && empty($password_err) && empty($captcha_err)) {
        // Prepare a select statement
        $sql = "SELECT userid, pass FROM basadmn WHERE userid = :username";
        
        if($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                // Check if username exists, if yes then verify password
                if($stmt->rowCount() == 1) {
                    if($row = $stmt->fetch()) {
                        $username = $row["userid"];
                        $hashed_password = password_hash($row["pass"], PASSWORD_DEFAULT);

                        if(password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to welcome page
                            header("location: ".$pfile);
                        } else {
                            // Display an error message if password is not valid
                            $password_err = "The password you entered was not valid.";
                        }
                    }
                } else {
                    // Display an error message if username doesn't exist
                    $username_err = "No account found with that username.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
    }
    
    // Close connection
    unset($pdo);
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | System Access</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .login-header i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .form-control {
            height: 45px;
            border-radius: 4px;
            border: 1px solid #ddd;
            padding-left: 15px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            height: 45px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: var(--secondary-color);
        }
        
        .input-group-text {
            background-color: white;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group .form-control:focus {
            border-left: none;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .footer-links a {
            color: var(--dark-color);
            text-decoration: none;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .g-recaptcha {
            margin: 15px 0;
            display: flex;
            justify-content: center;
        }
        
        .invalid-feedback.d-block {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-user-shield"></i>
                <h2>System Access</h2>
                <p>Please enter your credentials</p>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                        </div>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo $username; ?>" placeholder="Enter username">
                    </div>
                    <?php if(!empty($username_err)): ?>
                        <div class="invalid-feedback d-block"><?php echo $username_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                               placeholder="Enter password">
                    </div>
                    <?php if(!empty($password_err)): ?>
                        <div class="invalid-feedback d-block"><?php echo $password_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- CAPTCHA Field -->
                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY"></div>
                    <?php if(!empty($captcha_err)): ?>
                        <div class="invalid-feedback d-block"><?php echo $captcha_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
            </form>
            
            <div class="footer-links">
                <a href="#"><i class="fas fa-question-circle"></i> Need help?</a>
                <span class="mx-2">|</span>
                <a href="#"><i class="fas fa-envelope"></i> Contact support</a>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>