<?php
require_once 'lib/auth.php';
require_once 'lib/storage.php';
require_once 'data/config.php';

require_admin();

$projects = load_data('data/projects.php');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin – Pending Projects</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <a href="index.php">Home</a>
        <?php if ($user): ?>
            <span>Logged in as: <?= htmlspecialchars($user['username']) ?></span>
            <a href="projects-admin.php">Admin</a>
            <a href="statistics.php">Statistics</a>
            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </header>

    <div class="container">
        <h1>Admin – Pending Projects</h1>

        <?php
        $hasPending = false;
        foreach (CATEGORIES as $cid => $cname):
            // Collect pending projects for this category
            $pendingInCat = array_filter($projects, function ($p) use ($cid) {
                return (int) $p['status'] === 0 && (int) $p['category'] === (int) $cid;
            });
            if (empty($pendingInCat)) {
                continue;
            }
            $hasPending = true;
            ?>
            <section class="category-section">
                <h2><?= htmlspecialchars($cname) ?></h2>

                <ul class="admin-projects-list">
                    <?php foreach ($pendingInCat as $p): ?>
                        <li class="admin-project-item">
                            <h3>
                                <a class="project-link" href="project.php?id=<?= htmlspecialchars($p['id']) ?>">
                                    <?= htmlspecialchars($p['title']) ?>
                                </a>
                            </h3>
                            <div class="project-meta">
                                Submitted:
                                <?= htmlspecialchars($p['submitted'] ?? '-') ?>
                                &nbsp; | &nbsp;
                                Owner ID: <?= htmlspecialchars($p['owner']) ?>
                            </div>
                            <div class="mt-1">
                                <span class="status-pill status-pending">
                                    <?= htmlspecialchars(STATUS[$p['status']] ?? 'pending') ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>

        <?php if (!$hasPending): ?>
            <p class="text-muted">There are no pending projects at the moment.</p>
        <?php endif; ?>
    </div>
</body>

</html>