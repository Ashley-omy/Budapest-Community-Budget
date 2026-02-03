<?php
require_once 'lib/auth.php';
require_once 'lib/storage.php';
require_once 'data/config.php';

$projects = load_data('data/projects.php');
$users = load_data('data/users.php');

$id = $_GET['id'] ?? null;

// Basic checks
if ($id === null || !isset($projects[$id])) {
    header('Location: index.php');
    exit;
}

// Work with the project by reference so that changes are saved back
$p = &$projects[$id];
$user = current_user();
$error = '';

// ---------- Access control (view) ----------
// If not approved (status != 1), only owner and admins can see
if ((int) $p['status'] !== 1) {
    if (!$user || (!$user['is_admin'] && $user['id'] !== $p['owner'])) {
        header('Location: index.php');
        exit;
    }
}

// Make sure rework_comments exists and is an array
if (!isset($p['rework_comments']) || !is_array($p['rework_comments'])) {
    $p['rework_comments'] = [];
}

// Make sure history exists and is an array
if (!isset($p['history']) || !is_array($p['history'])) {
    $p['history'] = [];
}

// ---------- Admin actions (approve / reject / rework) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admins are allowed to change status
    if (!$user || !$user['is_admin']) {
        header('Location: index.php');
        exit;
    }

    // Approve (status = 1)
    if (isset($_POST['approve'])) {
        $p['status'] = 1; // approved
        $p['approved'] = date('Y-m-d H:i');
    }

    // Reject (status = 2)
    if (isset($_POST['reject'])) {
        $p['status'] = 2; // rejected
    }

    // Rework (status = 3) – comment REQUIRED and stored in rework_comments[]
    if (isset($_POST['rework'])) {
        $comment = trim($_POST['admin_comment'] ?? '');
        if ($comment === '') {
            $error = 'Please write a comment before sending the project back for rework.';
        } else {
            $p['status'] = 3; // rework

            $p['rework_comments'][] = [
                'time' => date('Y-m-d H:i'),
                'admin_id' => $user['id'],
                'comment' => $comment,
            ];
        }
    }

    // If there was no validation error, save and redirect admin
    if ($error === '') {
        save_data('data/projects.php', $projects);

        if ($user && $user['is_admin']) {
            header('Location: projects-admin.php');
        } else {
            header('Location: project.php?id=' . urlencode($id));
        }
        exit;
    }
}

// Helper: status pill CSS class
$statusClass = 'status-pending';
switch ((int) $p['status']) {
    case 1:
        $statusClass = 'status-approved';
        break;
    case 2:
        $statusClass = 'status-rejected';
        break;
    case 3:
        $statusClass = 'status-rework';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($p['title']) ?> – Budapest Community Budget</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <a href="index.php">Home</a>
        <?php if ($user): ?>
            <span>Logged in as: <?= htmlspecialchars($user['username']) ?></span>
            <a href="projects-own.php">My projects</a>
            <?php if ($user['is_admin']): ?>
                <a href="projects-admin.php">Admin</a>
                <a href="statistics.php">Statistics</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </header>

    <div class="container">
        <div class="project-detail">
            <h1><?= htmlspecialchars($p['title']) ?></h1>

            <div class="project-meta">
                <span class="status-pill <?= $statusClass ?>">
                    <?= htmlspecialchars(STATUS[$p['status']] ?? 'unknown') ?>
                </span>
                <div class="mt-2">
                    Category:
                    <?= htmlspecialchars(CATEGORIES[$p['category']] ?? $p['category']) ?>
                    &nbsp; | &nbsp;
                    Postal code: <?= htmlspecialchars($p['postal_code']) ?>
                </div>
                <div class="mt-1">
                    Submitted:
                    <?= htmlspecialchars($p['submitted'] ?? '-') ?>
                    <?php if (!empty($p['approved'])): ?>
                        &nbsp; | &nbsp;
                        Published: <?= htmlspecialchars($p['approved']) ?>
                    <?php endif; ?>
                    &nbsp; | &nbsp;
                    Proposer: <?= htmlspecialchars($users[$p['owner']]['username'] ?? '-') ?>
                </div>
            </div>

            <p class="mt-3">
                <?= nl2br(htmlspecialchars($p['description'])) ?>
            </p>

            <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="Project image">
            <?php endif; ?>
        </div>

        <?php if (!empty($p['rework_comments']) && $user !== null && ($user['id'] === $p['owner'] || $user['is_admin'])): ?>
            <section class="project-detail">
                <h2>Rework comments from admins</h2>
                <ul>
                    <?php foreach ($p['rework_comments'] as $c): ?>
                        <li>
                            <strong><?= htmlspecialchars($c['time']) ?>:</strong>
                            <?= nl2br(htmlspecialchars($c['comment'])) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php
        // Show history (old input values) ONLY when status is pending or rework
        if (in_array((int) $p['status'], [0, 3], true) && !empty($p['history'])): ?>
            <section class="project-detail">
                <h2>Previous versions (old values)</h2>
                <ul>
                    <?php foreach ($p['history'] as $h): ?>
                        <li>
                            <strong><?= htmlspecialchars($h['date'] ?? '') ?></strong><br>
                            <?php if (!empty($h['old']['title'])): ?>
                                <em>Title (old):</em>
                                <?= htmlspecialchars($h['old']['title']) ?><br>
                            <?php endif; ?>

                            <?php if (!empty($h['old']['description'])): ?>
                                <em>Description (old):</em><br>
                                <?= nl2br(htmlspecialchars($h['old']['description'])) ?><br>
                            <?php endif; ?>

                            <?php if (isset($h['old']['postal_code'])): ?>
                                <em>Postal code (old):</em>
                                <?= htmlspecialchars($h['old']['postal_code']) ?><br>
                            <?php endif; ?>

                            <?php if (!empty($h['old']['image'])): ?>
                                <em>Image URL (old):</em>
                                <a href="<?= htmlspecialchars($h['old']['image']) ?>" target="_blank">
                                    <?= htmlspecialchars($h['old']['image']) ?>
                                </a><br>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="form-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php
        // Admin control buttons (only for pending or rework)
        if ($user && $user['is_admin'] && in_array((int) $p['status'], [0, 3], true)): ?>
            <section class="project-detail">
                <h2>Admin actions</h2>
                <form method="post" class="mt-2">
                    <label for="admin_comment">
                        Comment for the submitter (required for rework):
                    </label>
                    <textarea name="admin_comment" id="admin_comment" rows="4"></textarea>

                    <div class="mt-2">
                        <button type="submit" name="approve">Approve</button>
                        <button type="submit" name="reject">Reject</button>
                        <button type="submit" name="rework">Send back for rework</button>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <?php
        // "Edit & resubmit" for project owner when status is rework
        if (
            $user &&
            !$user['is_admin'] &&
            $user['id'] === $p['owner'] &&
            (int) $p['status'] === 3
        ): ?>
            <section class="project-detail">
                <form method="get" action="submit-project.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                    <button type="submit">Edit &amp; resubmit</button>
                </form>
            </section>
        <?php endif; ?>
    </div>
</body>

</html>