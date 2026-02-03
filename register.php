<?php
require_once 'lib/storage.php';
require_once 'lib/validation.php';

$users = load_data('data/users.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $p1 = $_POST['password'];
    $p2 = $_POST['password2'];

    foreach ($users as $u) {
        if ($u['username'] === $username) {
            $error = 'Username already exists';
        }
    }

    if (!$error && !valid_username($username))
        $error = 'Username contains spaces';
    if (!$error && !valid_email($email))
        $error = 'Invalid email format';
    if (!$error && !valid_password($p1))
        $error = 'Password must be at least 8 characters and include uppercase, lowercase, and a number.';
    if (!$error && $p1 !== $p2)
        $error = 'Passwords do not match';

    if (!$error) {
        $id = $users ? max(array_keys($users)) + 1 : 1;
        $users[$id] = [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($p1, PASSWORD_DEFAULT),
            'is_admin' => false
        ];
        save_data('data/users.php', $users);

        header('Location: login.php');
        exit;
    }
}

$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register – Budapest Community Budget</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <a href="index.php">Home</a>
        <?php if ($user): ?>
            <span> | Logged in as: <?= htmlspecialchars($user['username']) ?></span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </header>

    <div class="container">
        <div class="form-card">
            <h1>Register</h1>

            <form method="post" class="form-container">
                <label>Username:</label>
                <input class="form-input" name="username" required>

                <label>Email:</label>
                <input class="form-input" type="email" name="email" required>

                <label>Password:</label>
                <input class="form-input" type="password" name="password" required>

                <label>Repeat password:</label>
                <input class="form-input" type="password" name="password2" required>

                <button class="btn-primary" type="submit">Register</button>
            </form>

            <?php if ($error): ?>
                <p class="error-text"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <p class="text-muted mt-2">
                Already have an account?
                <a class="text-link" href="login.php">Login here</a>
            </p>
        </div>
    </div>

</body>

</html>