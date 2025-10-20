<?php
session_start();
require_once "config.php";

// Only allow access if no admin exists yet or if logged in as admin
$check_admin = "SELECT COUNT(*) as admin_count FROM users WHERE user_type = 'admin'";
$result = $mysqli->query($check_admin);
$row = $result->fetch_assoc();
$admin_exists = $row['admin_count'] > 0;

// Check if user is logged in as admin
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

if ($admin_exists && !$is_admin) {
    die("Access denied. Please contact an existing admin to create new admin accounts.");
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin = [
        'firstName' => trim($_POST['firstName']),
        'middleName' => trim($_POST['middleName']),
        'lastName' => trim($_POST['lastName']),
        'email' => trim($_POST['email']),
        'password' => trim($_POST['password']),
        'userName' => trim($_POST['userName']),
        'dob' => trim($_POST['dob'])
    ];

    // Validate inputs
    if (empty($admin['email']) || empty($admin['password']) || empty($admin['userName'])) {
        $error = "Please fill all required fields";
    } else {
        // Check if admin already exists
        $check_sql = "SELECT user_id FROM users WHERE user_email = ? OR user_userName = ?";
        if ($check_stmt = $mysqli->prepare($check_sql)) {
            $check_stmt->bind_param("ss", $admin['email'], $admin['userName']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $error = "Email or username already exists!";
            }
            $check_stmt->close();
        }

        if (empty($error)) {
            // Hash the password
            $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);

            // Insert new admin
            $sql = "INSERT INTO users (user_firstName, user_middleName, user_lastName, user_email, user_password, user_userName, user_dob, user_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'admin')";

            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("sssssss", 
                    $admin['firstName'],
                    $admin['middleName'],
                    $admin['lastName'],
                    $admin['email'],
                    $hashed_password,
                    $admin['userName'],
                    $admin['dob']
                );
                
                if ($stmt->execute()) {
                    $success = "Admin account created successfully!";
                } else {
                    $error = "Error creating account: " . $mysqli->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Admin Account - BattleArt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Create Admin Account</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?><br>
                                Please save these credentials!
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="firstName" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" name="middleName">
                                </div>
                                <div class="col-md-4">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="lastName" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="userName" class="form-label">Username</label>
                                <input type="text" class="form-control" name="userName" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" required>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Create Admin Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>