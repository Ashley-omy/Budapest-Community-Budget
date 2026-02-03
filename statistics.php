<?php
require_once 'lib/auth.php';
require_once 'lib/storage.php';
require_once 'data/config.php';

require_admin();

$projects = load_data('data/projects.php');
$user = current_user();

// ---------- Overall top project by votes ----------
$top = null;
foreach ($projects as $p) {
    if (!$top || ($p['votes'] ?? 0) > ($top['votes'] ?? 0)) {
        $top = $p;
    }
}

// ---------- Top 3 projects per category (approved only) ----------
$topByCategory = [];
foreach ($projects as $p) {
    if ((int) $p['status'] !== 1) {
        // Only approved projects have meaningful votes
        continue;
    }
    $cat = $p['category'];
    if (!isset($topByCategory[$cat])) {
        $topByCategory[$cat] = [];
    }
    $topByCategory[$cat][] = $p;
}

// Sort each category's projects by votes (desc) and keep top 3
foreach ($topByCategory as $cat => &$list) {
    usort($list, function ($a, $b) {
        $va = $a['votes'] ?? 0;
        $vb = $b['votes'] ?? 0;
        if ($va === $vb) {
            // Secondary sort by id for stability
            return ($a['id'] <=> $b['id']);
        }
        return $vb <=> $va;
    });
    $list = array_slice($list, 0, 3);
}
unset($list);

// ---------- Stats matrix by category and status ----------
$categoryIds = array_keys(CATEGORIES);
$statusIds = array_keys(STATUS);

// counts[statusIndex][categoryIndex] = integer
$counts = [];
foreach ($statusIds as $si => $sid) {
    $counts[$si] = [];
    foreach ($categoryIds as $ci => $cid) {
        $counts[$si][$ci] = 0;
    }
}

foreach ($projects as $p) {
    $cid = $p['category'];
    $sid = $p['status'];
    $si = array_search($sid, $statusIds, true);
    $ci = array_search($cid, $categoryIds, true);
    if ($si !== false && $ci !== false) {
        $counts[$si][$ci]++;
    }
}

// Prepare labels for JS
$categoryLabels = [];
foreach ($categoryIds as $cid) {
    $categoryLabels[] = CATEGORIES[$cid];
}

$statusLabels = [];
foreach ($statusIds as $sid) {
    $statusLabels[] = STATUS[$sid];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Statistics – Budapest Community Budget</title>
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
        <h1>Statistics</h1>

        <!-- Overall top project -->
        <section>
            <h2>Top project (overall)</h2>
            <?php if ($top && ($top['votes'] ?? 0) > 0): ?>
                <p>
                    <a href="project.php?id=<?= htmlspecialchars($top['id']) ?>">
                        <?= htmlspecialchars($top['title']) ?>
                    </a>
                    – <?= (int) $top['votes'] ?> votes
                    (Category: <?= htmlspecialchars(CATEGORIES[$top['category']] ?? $top['category']) ?>)
                </p>
            <?php else: ?>
                <p>No votes yet.</p>
            <?php endif; ?>
        </section>

        <!-- Top 3 per category -->
        <section>
            <h2>Top 3 projects per category</h2>
            <?php if (empty($topByCategory)): ?>
                <p>No approved projects with votes yet.</p>
            <?php else: ?>
                <?php foreach ($categoryIds as $cid): ?>
                    <h3><?= htmlspecialchars(CATEGORIES[$cid]) ?></h3>
                    <?php if (empty($topByCategory[$cid] ?? [])): ?>
                        <p>No projects in this category.</p>
                    <?php else: ?>
                        <ol>
                            <?php foreach ($topByCategory[$cid] as $proj): ?>
                                <li>
                                    <a href="project.php?id=<?= htmlspecialchars($proj['id']) ?>">
                                        <?= htmlspecialchars($proj['title']) ?>
                                    </a>
                                    – <?= (int) ($proj['votes'] ?? 0) ?> votes
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Table: number of projects grouped by category AND status -->
        <section>
            <h2>Projects by category and status (table)</h2>
            <table border="1" cellpadding="4" cellspacing="0">
                <tr>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
                <?php foreach ($statusIds as $si => $sid): ?>
                    <?php foreach ($categoryIds as $ci => $cid): ?>
                        <tr>
                            <td><?= htmlspecialchars(CATEGORIES[$cid]) ?></td>
                            <td><?= htmlspecialchars(STATUS[$sid]) ?></td>
                            <td><?= (int) $counts[$si][$ci] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        </section>

        <!-- Charts: grouped by category AND status -->
        <section>
            <h2>Projects by category and status (charts)</h2>
            <div style="display:flex; gap:30px; flex-wrap:wrap;">
                <div style="flex:1 1 400px;">
                    <h3>By status (stacked horizontal)</h3>
                    <canvas id="chartStatusHorizontal" width="400" height="250"></canvas>
                </div>
                <div style="flex:1 1 400px;">
                    <h3>By category (stacked vertical)</h3>
                    <canvas id="chartCategoryVertical" width="400" height="250"></canvas>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data passed from PHP
        const categoryLabels = <?= json_encode($categoryLabels) ?>;
        const statusLabels = <?= json_encode($statusLabels) ?>;
        const countsMatrix = <?= json_encode($counts) ?>; // [statusIndex][categoryIndex]

        // Build datasets for the horizontal stacked chart:
        //  - y-axis: status labels
        //  - each dataset: one category
        const horizontalDatasets = categoryLabels.map((catLabel, catIndex) => {
            const data = statusLabels.map((_, statusIndex) => countsMatrix[statusIndex][catIndex]);
            return {
                label: catLabel,
                data: data,
                // Chart.js will auto-assign colors if not specified
                borderWidth: 1
            };
        });

        const ctxH = document.getElementById('chartStatusHorizontal');
        if (ctxH) {
            new Chart(ctxH, {
                type: 'bar',
                data: {
                    labels: statusLabels,
                    datasets: horizontalDatasets
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true }
                    }
                }
            });
        }

        // Build datasets for the vertical stacked chart:
        //  - x-axis: category labels
        //  - each dataset: one status
        const verticalDatasets = statusLabels.map((statusLabel, statusIndex) => {
            const data = categoryLabels.map((_, catIndex) => countsMatrix[statusIndex][catIndex]);
            return {
                label: statusLabel,
                data: data,
                borderWidth: 1
            };
        });

        const ctxV = document.getElementById('chartCategoryVertical');
        if (ctxV) {
            new Chart(ctxV, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: verticalDatasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true }
                    }
                }
            });
        }
    </script>
</body>

</html>