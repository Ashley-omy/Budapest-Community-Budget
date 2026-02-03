<?php
require_once 'lib/auth.php';
require_once 'lib/storage.php';
require_once 'lib/helpers.php';
require_once 'data/config.php';

$user = current_user();
$projects = load_data('data/projects.php');
$votes = file_exists('data/votes.php') ? load_data('data/votes.php') : [];

// ----- Category filter (GET parameter) -----
$selected_category = $_GET['category'] ?? 'all';

// helper: check if this project is visible on index (approved + category filter)
function project_visible(array $p, $selected_category): bool
{
    if ((int) $p['status'] !== 1)
        return false; // only approved on index
    if ($selected_category === 'all')
        return true;
    return (string) $p['category'] === (string) $selected_category;
}

// helper: has the current user already voted this project?
function user_has_voted(?array $user, array $p, array $votes): bool
{
    if (!$user)
        return false;
    foreach ($votes as $v) {
        if ($v['user'] == $user['id'] && $v['project'] == $p['id']) {
            return true;
        }
    }
    return false;
}

// collect user vote counts per category for initial UI
$userVotesByCategory = [];
if ($user) {
    foreach ($votes as $v) {
        if ($v['user'] == $user['id']) {
            $cat = $v['category'];
            if (!isset($userVotesByCategory[$cat])) {
                $userVotesByCategory[$cat] = 0;
            }
            $userVotesByCategory[$cat]++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Budapest Community Budget</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <a href="index.php">Home</a>
        <?php if ($user): ?>
            <span>Logged in as: <?= htmlspecialchars($user['username']) ?></span>
            <?php if(!$user['is_admin']):?>
            <a href="projects-own.php">My projects</a>
            <?php endif?>
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
        <h1>Budapest Community Budget</h1>

        <!-- Category filter dropdown -->
        <form method="get" style="margin-bottom: 20px;">
            <label for="category_filter"><strong>Filter by category:</strong></label>
            <select name="category" id="category_filter" onchange="this.form.submit()">
                <option value="all" <?= $selected_category === 'all' ? 'selected' : '' ?>>
                    All categories
                </option>
                <?php foreach (CATEGORIES as $cid => $cname): ?>
                    <option value="<?= $cid ?>" <?= (string) $selected_category === (string) $cid ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cname) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript>
                <button type="submit">Apply</button>
            </noscript>
        </form>

        <?php if ($user && !$user['is_admin']): ?>
            <p><a href="submit-project.php">+ Submit new project</a></p>
        <?php endif; ?>

        <?php
        // group projects by category
        foreach (CATEGORIES as $cid => $cname):
            // if filtered and this category is not selected, skip whole block
            if ($selected_category !== 'all' && (string) $selected_category !== (string) $cid) {
                continue;
            }
            ?>
            <section class="category-section">
                <h2><?= htmlspecialchars($cname) ?></h2>

                <?php if ($user && !$user['is_admin']):
                    $used = $userVotesByCategory[$cid] ?? 0;
                    $remaining = max(0, 3 - $used);
                    ?>
                    <p class="remaining-votes" id="remaining-cat-<?= (int) $cid ?>">
                        Remaining votes in this category: <?= $remaining ?>
                    </p>
                <?php endif; ?>

                <ul class="project-list">
                    <?php foreach ($projects as $p): ?>
                        <?php
                        if (!project_visible($p, $selected_category))
                            continue;
                        if ((string) $p['category'] !== (string) $cid)
                            continue;

                        $current_votes = (int) ($p['votes'] ?? 0);
                        $already_voted = user_has_voted($user, $p, $votes);
                        $voting_open = can_vote($p);
                        ?>
                        <li class="project-item">
                            <a class="project-link" href="project.php?id=<?= htmlspecialchars($p['id']) ?>">
                                <?= htmlspecialchars($p['title']) ?>
                            </a>

                            <!-- Vote count -->
                            <span id="votes-<?= htmlspecialchars($p['id']) ?>">
                                <?= $current_votes ?>
                            </span> votes

                            <!-- "voted!" indicator (controlled from JS as well) -->
                            <span class="voted-indicator" id="voted-indicator-<?= $p['id'] ?>"
                                style="display: <?= $already_voted ? 'inline' : 'none' ?>; font-style:italic; color:#2a7; margin-left:6px;">voted!</span>

                            <!-- Voting closed label -->
                            <?php if (!$voting_open): ?>
                                <span class="voting-closed-label">Voting closed</span>
                            <?php endif; ?>

                            <!-- Vote button for non-admin logged users -->
                            <?php if ($user && !$user['is_admin'] && $voting_open): ?>
                                <?php
                                // Votes used in this category
                                $usedInCategory = $userVotesByCategory[$cid] ?? 0;
                                $remainingInCategory = max(0, 3 - $usedInCategory);

                                // Disable button if no remaining votes AND user has not yet voted this project
                                $disableForThisButton = ($remainingInCategory === 0 && !$already_voted);
                                ?>
                                <button class="vote-btn" data-project="<?= htmlspecialchars($p['id']) ?>"
                                    data-category="<?= htmlspecialchars($p['category']) ?>"
                                    data-voted="<?= $already_voted ? '1' : '0' ?>" <?= $disableForThisButton ? 'disabled="disabled"' : '' ?>>
                                    <?= $already_voted ? 'Remove vote' : 'Vote' ?>
                                </button>

                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>

    <script>
        // AJAX Voting Logic
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = document.querySelectorAll('.vote-btn');

            buttons.forEach(btn => {
                btn.addEventListener('click', async () => {
                    const pid = btn.dataset.project;
                    const voted = btn.dataset.voted === '1';
                    const action = voted ? 'remove' : 'add';

                    try {
                        const res = await fetch('api/vote.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                project_id: pid,
                                action: action
                            })
                        });

                        const data = await res.json();

                        if (!data.success) {
                            if (data.error === 'Voting closed') {
                                btn.disabled = true;
                                btn.textContent = 'Voting closed';
                            }
                            alert(data.error || 'Voting failed');
                            return;
                        }

                        // Update vote count for this project
                        const span = document.getElementById('votes-' + pid);
                        if (span && typeof data.votes !== 'undefined') {
                            span.textContent = data.votes;
                        }

                        // Update "voted!" indicator
                        const indicator = document.getElementById('voted-indicator-' + pid);
                        if (indicator) {
                            if (action === 'add') {
                                indicator.style.display = 'inline';
                            } else {
                                indicator.style.display = 'none';
                            }
                        }



                        // Toggle button label & data state for this project
                        if (action === 'add') {
                            btn.dataset.voted = '1';
                            btn.textContent = 'Remove vote';
                        } else {
                            btn.dataset.voted = '0';
                            btn.textContent = 'Vote';
                        }

                        // Handle remaining votes per category and enable/disable other buttons
                        if ('remaining' in data && typeof data.category !== 'undefined') {
                            const catId = data.category;

                            // Update "Remaining votes in this category" label
                            const remElem = document.getElementById('remaining-cat-' + catId);
                            if (remElem) {
                                remElem.textContent = 'Remaining votes in this category: ' + data.remaining;
                            }

                            const otherButtons = document.querySelectorAll(
                                '.vote-btn[data-category="' + catId + '"]'
                            );

                            if (data.remaining === 0) {
                                // Disable all other Vote buttons in this category that are not yet voted
                                otherButtons.forEach(b => {
                                    if (b.dataset.voted === '0') {
                                        b.disabled = true;
                                    }
                                });
                            } else if (data.remaining > 0) {
                                // Re-enable Vote buttons (for not yet voted projects) in this category
                                otherButtons.forEach(b => {
                                    if (b.dataset.voted === '0') {
                                        b.disabled = false;
                                        b.textContent = 'Vote';
                                    }
                                });
                            }
                        }

                    } catch (e) {
                        console.error(e);
                        alert('Network error during voting');
                    }
                });
            });
        });
    </script>
</body>

</html>