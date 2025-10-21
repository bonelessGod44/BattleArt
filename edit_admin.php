<?php
require_once "admin_auth_check.php";
requireAdmin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['user_userName']);
    $email = trim($_POST['user_email']);
    $bio = trim($_POST['user_bio']);
    $password = trim($_POST['user_password']);

    // File uploads
    $profilePic = $_FILES['user_profile_pic']['name'] ?? null;
    $bannerPic = $_FILES['user_banner_pic']['name'] ?? null;
    $uploadDir = 'assets/uploads/';

    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    if ($profilePic) {
        $profilePath = $uploadDir . basename($profilePic);
        move_uploaded_file($_FILES['user_profile_pic']['tmp_name'], $profilePath);
        $profilePicSql = ", user_profile_pic = '" . $profilePic . "'";
    } else {
        $profilePicSql = "";
    }

    if ($bannerPic) {
        $bannerPath = $uploadDir . basename($bannerPic);
        move_uploaded_file($_FILES['user_banner_pic']['tmp_name'], $bannerPath);
        $bannerPicSql = ", user_banner_pic = '" . $bannerPic . "'";
    } else {
        $bannerPicSql = "";
    }

    // Update password only if filled
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $passwordSql = ", user_password = '$hashed'";
    } else {
        $passwordSql = "";
    }

    $sql = "UPDATE users 
            SET user_userName = ?, user_email = ?, user_bio = ? 
            $profilePicSql $bannerPicSql $passwordSql
            WHERE user_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("sssi", $username, $email, $bio, $user_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
        } else {
            $error = "Error updating profile: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// Fetch admin info
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Edit Profile</h4>
            <a href="admin_profile.php" class="btn btn-light btn-sm">Back</a>
        </div>
        <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="user_userName" class="form-label">Username</label>
                    <input type="text" class="form-control" name="user_userName" value="<?= htmlspecialchars($admin['user_userName']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="user_email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="user_email" value="<?= htmlspecialchars($admin['user_email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="user_bio" class="form-label">Bio</label>
                    <textarea class="form-control" name="user_bio" rows="3"><?= htmlspecialchars($admin['user_bio']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="user_password" class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" name="user_password">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="user_profile_pic" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" name="user_profile_pic">
                        <?php if ($admin['user_profile_pic']): ?>
                            <img src="uploads/<?= htmlspecialchars($admin['user_profile_pic']) ?>" class="mt-2 rounded" width="100">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="user_banner_pic" class="form-label">Banner Picture</label>
                        <input type="file" class="form-control" name="user_banner_pic">
                        <?php if ($admin['user_banner_pic']): ?>
                            <img src="uploads/<?= htmlspecialchars($admin['user_banner_pic']) ?>" class="mt-2 rounded" width="100">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php $mysqli->close(); ?>
