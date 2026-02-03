<?php
require_once 'lib/auth.php';
require_once 'lib/storage.php';
require_once 'data/config.php';

require_login();

$projects = load_data('data/projects.php');
$user = current_user();

// Filter projects owned by current user
$ownProjects = array_filter($projects, function ($p) use ($user) {
    return isset($p['owner']) && $p['owner'] === $user['id'];
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Projects – Budapest Community Budget</title>
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
        <h1>My Projects</h1>

        <?php if (empty($ownProjects)): ?>
            <p class="text-muted">You have not submitted any projects yet.</p>
            <p><a class="button-link" href="submit-project.php">+ Submit your first project</a></p>
        <?php else: ?>
            <ul class="own-projects-list">
                <?php foreach ($ownProjects as $p): ?>
                    <?php
                    // Status pill CSS class
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
                    <li class="own-project-item">
                        <h3>
                            <a class="project-link" href="project.php?id=<?= htmlspecialchars($p['id']) ?>">
                                <?= htmlspecialchars($p['title']) ?>
                            </a>
                        </h3>

                        <div class="project-meta">
                            Category:
                            <?= htmlspecialchars(CATEGORIES[$p['category']] ?? $p['category']) ?>
                            &nbsp; | &nbsp;
                            Submitted:
                            <?= htmlspecialchars($p['submitted'] ?? '-') ?>
                        </div>

                        <div class="mt-1">
                            <span class="status-pill <?= $statusClass ?>">
                                <?= htmlspecialchars(STATUS[$p['status']] ?? 'unknown') ?>
                            </span>
                            <?php if ((int) $p['status'] === 3): ?>
                                <span class="text-muted"> – This project has been sent back for rework.</span>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($p['votes'])): ?>
                            <div class="mt-1 text-muted">
                                Votes: <?= (int) $p['votes'] ?>
                            </div>
                        <?php endif; ?>

                        <?php if ((int) $p['status'] === 3): ?>
                            <div class="mt-2">
                                <a class="button-link" href="project.php?id=<?= htmlspecialchars($p['id']) ?>">
                                    View details &amp; rework
                                </a>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($user && !$user['is_admin']): ?>
            <p class="mt-3">
                <a class="button-link" href="submit-project.php">+ Submit new project</a>
            </p>
        <?php endif; ?>
    </div>
</body>

</html>