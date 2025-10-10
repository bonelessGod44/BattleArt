<?php
// Include config file
require_once "config.php";
 
// Define variables and initialize with empty values
$firstName = $middleName = $lastName = $email = $password = $confirm_password = $userName ="";
$firstName_err = $middleName_err = $lastName_err = $email_err = $password_err = $confirm_password_err = $userName_err ="";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate first name
    if(empty(trim($_POST["firstName"]))){
        $firstName_err = "Please enter your first name.";
    } else{
        $firstName = trim($_POST["firstName"]);
    }

    // Validate middle name (optional)
    $middleName = trim($_POST["middleName"]);
    
    // Validate last name
    if(empty(trim($_POST["lastName"]))){
        $lastName_err = "Please enter your last name.";
    } else{
        $lastName = trim($_POST["lastName"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else {
        // Prepare a select statement to check if email is already taken
        $sql = "SELECT user_id FROM users WHERE user_email = ?";
        
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST["email"]);
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){
                    $email_err = "This email is already taken.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirmPassword"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirmPassword"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Passwords did not match.";
        }
    }

    if(empty(trim($_POST["userName"]))){
        $userName_err = "Please enter a username.";
    } else {
        // Prepare a select statement to check if username is already taken
        $sql = "SELECT user_id FROM users WHERE user_userName = ?";
        
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $param_userName);
            $param_userName = trim($_POST["userName"]);
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){
                    $userName_err = "This username is already taken.";
                } else{
                    $userName = trim($_POST["userName"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    
    // Check input errors before inserting in database
    if(empty($firstName_err) && empty($lastName_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($userName_err)){

        // Prepare an insert statement
        $sql = "INSERT INTO users (user_firstName, user_middleName, user_lastName, user_email, user_password, user_userName) VALUES (?, ?, ?, ?, ?, ?)";
         
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("ssssss", $param_firstname, $param_middlename, $param_lastname, $param_email, $param_password, $param_username);
            
            $param_firstname = $firstName;
            $param_middlename = $middleName;
            $param_lastname = $lastName;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_username = $userName;

            if($stmt->execute()){
                // Redirect to login page
                header("location: login.php");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    
    // Close connection
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BattleArt - Register</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid vh-100">
        <div class="row h-100 justify-content-center align-items-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        
                        <!-- Logo/Brand -->
                        <div class="text-center mb-4">
                            <h1 class="brand-title fw-bold text-primary mb-2">BattleArt</h1>
                            <p class="subtitle text-muted">Join the community and start your art battle journey</p>
                        </div>

                        <!-- Registration Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                        <!-- Username -->
                                <div class="col-md-5">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-at" aria-hidden="true"></i>
                                        </span>
                                        <input type="text"
                                               name="userName"
                                               class="form-control <?php echo (!empty($userName_err)) ? 'is-invalid' : ''; ?>"
                                               id="userName"
                                               value="<?php echo $userName; ?>"
                                               placeholder="Enter username">
                                    </div>
                                    <span class="text-danger"><?php echo $userName_err; ?></span>
                                </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person" aria-hidden="true"></i>
                                        </span>
                                        <input type="text" 
                                               name="firstName"
                                               class="form-control <?php echo (!empty($firstName_err)) ? 'is-invalid' : ''; ?>" 
                                               value="<?php echo $firstName; ?>"
                                               id="firstName" 
                                               placeholder="Enter first name">
                                    </div>
                                    <span class="text-danger"><?php echo $firstName_err; ?></span>
                                </div>
                                
                                <!-- Middle Name -->
                                <div class="col-md-6">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                                        </span>
                                        <input type="text" 
                                               name="middleName"
                                               class="form-control" 
                                               id="middleName" 
                                               value="<?php echo $middleName; ?>"
                                               placeholder="Enter middle name">
                                    </div>
                                </div>
                                
                                <!-- Last Name -->
                                <div class="col-md-8">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person-fill" aria-hidden="true"></i>
                                        </span>
                                        <input type="text" 
                                               name="lastName"
                                               class="form-control <?php echo (!empty($lastName_err)) ? 'is-invalid' : ''; ?>" 
                                               id="lastName" 
                                               value="<?php echo $lastName; ?>"
                                               placeholder="Enter last name">
                                    </div>
                                    <span class="text-danger"><?php echo $lastName_err; ?></span>
                                </div>
                                
                                <!-- Email -->
                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-envelope" aria-hidden="true"></i>
                                        </span>
                                        <input type="email" 
                                               name="email"
                                               class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                               id="email" 
                                               value="<?php echo $email; ?>"
                                               placeholder="Enter email">
                                    </div>
                                    <span class="text-danger"><?php echo $email_err; ?></span>
                                </div>
                                
                                <!-- Password -->
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock" aria-hidden="true"></i>
                                        </span>
                                        <input type="password" 
                                               name="password"
                                               class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                               id="password"
                                               value="<?php echo $password; ?>"
                                               placeholder="Enter password">
                                    </div>
                                     <span class="text-danger"><?php echo $password_err; ?></span>
                                </div>
                                
                                <!-- Confirm Password -->
                                <div class="col-md-6">
                                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                        </span>
                                        <input type="password" 
                                               name="confirmPassword"
                                               class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                                               id="confirmPassword" 
                                               value="<?php echo $confirm_password; ?>"
                                               placeholder="Re-enter password">
                                    </div>
                                     <span class="text-danger"><?php echo $confirm_password_err; ?></span>
                                </div>
                            </div>

                            <!-- Register Button -->
                            <div class="d-grid mt-4 mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus-fill me-2" aria-hidden="true"></i>
                                    Create Account
                                </button>
                            </div>

                            <!-- Login Link -->
                            <div class="text-center">
                                <p class="mb-0">Already have an account? 
                                    <a href="./login.php" class="signup-link">
                                        Sign in here
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Additional Links -->
                <div class="text-center mt-3">
                    <a href="./index.html" class="back-home-link">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>