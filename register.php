<?php
session_start();
require_once "config.php";
define('ADMIN_SECRET_CODE', 'BATTLEARTSECRETADMINCODE!');

$firstName = $middleName = $lastName = $email = $password = $confirm_password = $userName = $dob = $admin_code = "";
$firstName_err = $middleName_err = $lastName_err = $email_err = $password_err = $confirm_password_err = $userName_err = $dob_err = $admin_code_err = "";

$user_type = "user";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_type = $_POST['user_type'] ?? 'user';
    $admin_code = trim($_POST['admin_code']);

    // Validate first name
    if (empty(trim($_POST["firstName"]))) {
        $firstName_err = "Please enter your first name.";
    } else {
        $firstName = trim($_POST["firstName"]);
    }

    // Validate middle name (optional)
    $middleName = trim($_POST["middleName"]);

    // Validate last name
    if (empty(trim($_POST["lastName"]))) {
        $lastName_err = "Please enter your last name.";
    } else {
        $lastName = trim($_POST["lastName"]);
    }

    // Validate Date of Birth
    if (empty(trim($_POST["dob"]))) {
        $dob_err = "Please enter your date of birth.";
    } else {
        $dob = trim($_POST["dob"]);
        // Optional: Add an age check (e.g., must be 13 or older)
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        if ($birthDate > $today) {
            $dob_err = "Birth date cannot be in the future.";
        } elseif ($birthDate->diff($today)->y < 13) {
            $dob_err = "You must be at least 13 years old to register.";
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Prepare a select statement to check if email is already taken
        $sql = "SELECT user_id FROM users WHERE user_email = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST["email"]);

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $email_err = "This email is already taken.";
                } else {
                    $email = trim($_POST["email"]);
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
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirmPassword"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirmPassword"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }

    if (empty(trim($_POST["userName"]))) {
        $userName_err = "Please enter a username.";
    } else {
        // Prepare a select statement to check if username is already taken
        $sql = "SELECT user_id FROM users WHERE user_userName = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_userName);
            $param_userName = trim($_POST["userName"]);

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $userName_err = "This username is already taken.";
                } else {
                    $userName = trim($_POST["userName"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }

    // --- NEW ---
    // Validate Admin Code if admin registration is selected
    if ($user_type == 'admin') {
        if (empty($admin_code)) {
            $admin_code_err = "Please enter the admin code.";
        } elseif ($admin_code != ADMIN_SECRET_CODE) {
            $admin_code_err = "Invalid admin code.";
        }
    }
    // -----------


    // --- MODIFIED ---
    // Check input errors before inserting in database
    // Added $admin_code_err check
    if (empty($firstName_err) && empty($lastName_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($userName_err) && empty($dob_err) && empty($admin_code_err)) {

        // --- MODIFIED ---
        // Prepare an insert statement - Added user_type
        $sql = "INSERT INTO users (user_firstName, user_middleName, user_lastName, user_email, user_password, user_userName, user_dob, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $mysqli->prepare($sql)) {
            // --- MODIFIED ---
            // Bind variables to the prepared statement as parameters
            // Changed "sssssss" to "ssssssss" and added $param_usertype
            $stmt->bind_param("ssssssss", $param_firstname, $param_middlename, $param_lastname, $param_email, $param_password, $param_username, $param_dob, $param_usertype);

            // Set parameters
            $param_firstname = $firstName;
            $param_middlename = $middleName;
            $param_lastname = $lastName;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_username = $userName;
            $param_dob = $dob;
            $param_usertype = $user_type; // --- NEW ---

            if ($stmt->execute()) {
                // Redirect to login page
                header("location: login.php");
            } else {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid vh-100">
        <div class="row h-100 justify-content-center align-items-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">

                        <div class="text-center mb-4">
                            <h1 class="brand-title fw-bold text-primary mb-2">BattleArt</h1>
                            <p class="subtitle text-muted">Join the community and start your art battle journey</p>
                        </div>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                            <ul class="nav nav-tabs nav-fill mb-3" id="registerTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo ($user_type == 'user') ? 'active' : ''; ?>" id="user-tab" data-bs-toggle="tab" data-bs-target="#user-pane" type="button" role="tab" aria-controls="user-pane" aria-selected="<?php echo ($user_type == 'user') ? 'true' : 'false'; ?>">
                                        <i class="bi bi-person me-2"></i>Register as User
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo ($user_type == 'admin') ? 'active' : ''; ?>" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-pane" type="button" role="tab" aria-controls="admin-pane" aria-selected="<?php echo ($user_type == 'admin') ? 'true' : 'false'; ?>">
                                        <i class="bi bi-person-badge me-2"></i>Register as Admin
                                    </button>
                                </li>
                            </ul>
                            <input type="hidden" name="user_type" id="userTypeInput" value="<?php echo $user_type; ?>">

                            <div class="mb-3">
                                <label for="userName" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-at" aria-hidden="true"></i></span>
                                    <input type="text" name="userName" class="form-control <?php echo (!empty($userName_err)) ? 'is-invalid' : ''; ?>" id="userName" value="<?php echo $userName; ?>" placeholder="Enter username">
                                </div>
                                <span class="text-danger"><?php echo $userName_err; ?></span>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                                        <input type="text" name="firstName" class="form-control <?php echo (!empty($firstName_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $firstName; ?>" id="firstName" placeholder="Enter first name">
                                    </div>
                                    <span class="text-danger"><?php echo $firstName_err; ?></span>
                                </div>
                                <div class="col-md-6">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
                                        <input type="text" name="middleName" class="form-control" id="middleName" value="<?php echo $middleName; ?>" placeholder="Enter middle name">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="lastName" class="form-label">Last Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
                                    <input type="text" name="lastName" class="form-control <?php echo (!empty($lastName_err)) ? 'is-invalid' : ''; ?>" id="lastName" value="<?php echo $lastName; ?>" placeholder="Enter last name">
                                </div>
                                <span class="text-danger"><?php echo $lastName_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="dob" class="form-control <?php echo (!empty($dob_err)) ? 'is-invalid' : ''; ?>" id="dob" value="<?php echo $dob; ?>">
                                </div>
                                <span class="text-danger"><?php echo $dob_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" value="<?php echo $email; ?>" placeholder="Enter email">
                                </div>
                                <span class="text-danger"><?php echo $email_err; ?></span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password" value="<?php echo $password; ?>" placeholder="Enter password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <span class="text-danger"><?php echo $password_err; ?></span>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill" aria-hidden="true"></i></span>
                                        <input type="password" name="confirmPassword" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirmPassword" value="<?php echo $confirm_password; ?>" placeholder="Re-enter password">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <span class="text-danger"><?php echo $confirm_password_err; ?></span>
                                </div>
                            </div>

                            <div class="tab-content pt-2" id="registerTabsContent">
                                <div class="tab-pane fade <?php echo ($user_type == 'user') ? 'show active' : ''; ?>" id="user-pane" role="tabpanel" aria-labelledby="user-tab">
                                </div>
                                
                                <div class="tab-pane fade <?php echo ($user_type == 'admin') ? 'show active' : ''; ?>" id="admin-pane" role="tabpanel" aria-labelledby="admin-tab">
                                    <div class="mb-3">
                                        <label for="admin_code" class="form-label">Admin Code</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                            <input type="password" name="admin_code" class="form-control <?php echo (!empty($admin_code_err)) ? 'is-invalid' : ''; ?>" id="admin_code" value="<?php echo $admin_code; ?>" placeholder="Enter secret admin code">
                                        </div>
                                        <span class="text-danger"><?php echo $admin_code_err; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid mt-4 mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus-fill me-2" aria-hidden="true"></i>
                                    Create Account
                                </button>
                            </div>

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

                <div class="text-center mt-3">
                    <a href="./index.php" class="back-home-link">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPassword = document.getElementById('confirmPassword');
            const icon = this.querySelector('i');

            if (confirmPassword.type === 'password') {
                confirmPassword.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                confirmPassword.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        const userTab = document.getElementById('user-tab');
        const adminTab = document.getElementById('admin-tab');
        const userTypeInput = document.getElementById('userTypeInput');

        if(userTab) {
            userTab.addEventListener('click', function() {
                userTypeInput.value = 'user';
            });
        }
        
        if(adminTab) {
            adminTab.addEventListener('click', function() {
                userTypeInput.value = 'admin';
            });
        }
    </script>
</body>

</html>