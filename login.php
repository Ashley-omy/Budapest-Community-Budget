<?php
require_once 'lib/storage.php';
session_start();

$users = load_data('data/users.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($users as $u) {
        if (
            $u['username'] === $_POST['username'] &&
            password_verify($_POST['password'], $u['password'])
        ) {
            $_SESSION['user'] = $u;
            header('Location: index.php');
            exit;
        }
    }
    $error = 'Invalid credentials';
}

$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login – Budapest Community Budget</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <a href="index.php">Home</a>
        <?php if ($user): ?>
            <span> | Logged in as: <?= htmlspecialchars($user['username']) ?></span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </header>

    <div class="container">
        <div class="form-card">
            <h1>Login</h1>

            <form method="post" class="form-container">
                <label>Username:</label>
                <input class="form-input" name="username" required>

                <label>Password:</label>
                <input class="form-input" type="password" name="password" required>

                <button class="btn-primary" type="submit">Login</button>
            </form>

            <?php if ($error): ?>
                <p class="error-text"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <p class="text-muted mt-2">
                Don’t have an account?
                <a class="text-link" href="register.php">Register here</a>
            </p>
        </div>
    </div>

</body>

</html>