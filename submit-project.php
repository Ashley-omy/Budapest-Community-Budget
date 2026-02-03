<?php
require_once 'lib/auth.php';
require_once 'lib/storage.php';
require_once 'lib/validation.php';
require_once 'data/config.php';

require_login();

$projects = load_data('data/projects.php');
$user = current_user();
$error = '';
$edit_id = $_GET['id'] ?? null;
$project = null;
$resubmit_mode = false;

// form field variables (so they are always defined)
$title = $desc = $img = '';
$cat = $pc = null;

// ---------- Load project for rework (if any) ----------
if ($edit_id) {
    $edit_id = intval($edit_id);
    if (
        isset($projects[$edit_id]) &&
        $projects[$edit_id]['owner'] === $user['id'] &&
        in_array($projects[$edit_id]['status'], [3]) // 3 = rework
    ) {
        $project = $projects[$edit_id];
        $resubmit_mode = true; // flag to distinguish new vs resubmit

        // Pre-fill form fields with existing project data on initial load (GET)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $title = $project['title'];
            $desc = $project['description'];
            $cat = $project['category'];
            $pc = $project['postal_code'];
            $img = $project['image'];
        }
    } else {
        header('Location: projects-own.php');
        exit;
    }
}

// ---------- Handle form submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $cat = intval($_POST['category'] ?? 0);
    $pc = intval($_POST['postal_code'] ?? 0);
    $img = trim($_POST['image'] ?? '');

    // ---------- Validation ----------
    if (strlen($title) < 10)
        $error = 'Title too short (min 10 chars)';
    elseif (strlen($desc) < 150)
        $error = 'Description too short (min 150 chars)';
    elseif (!isset(CATEGORIES[$cat]))
        $error = 'Invalid category';
    elseif (!valid_postal_code($pc))
        $error = 'Invalid postal code';
    elseif (!valid_image_url($img))
        $error = 'Invalid image URL';

    // ---------- Save project ----------
    if (!$error) {

        if ($resubmit_mode) {
            // ---------- Rework resubmit ----------
            $p =& $projects[$edit_id];

            // Ensure history exists
            if (!isset($p['history']) || !is_array($p['history'])) {
                $p['history'] = [];
            }

            // Store old values for history (extra task)
            $p['history'][] = [
                'old' => [
                    'title' => $p['title'],
                    'description' => $p['description'],
                    'postal_code' => $p['postal_code'],
                    'image' => $p['image']
                ],
                'date' => date('Y-m-d H:i:s')
            ];

            // Update with new values
            $p['title'] = $title;
            $p['description'] = $desc;
            $p['category'] = $cat;
            $p['postal_code'] = $pc;
            $p['image'] = $img;

            $p['status'] = 0; // back to pending
            save_data('data/projects.php', $projects);

            header('Location: projects-own.php');
            exit;

        } else {
            // ---------- New project submission ----------
            $id = $projects ? max(array_keys($projects)) + 1 : 1;
            $projects[$id] = [
                'id' => $id,
                'status' => 0,
                'title' => $title,
                'description' => $desc,
                'category' => $cat,
                'postal_code' => $pc,
                'image' => $img,
                'owner' => $user['id'],
                'submitted' => date('Y-m-d H:i:s'),
                'votes' => 0,
                'rework_comments' => [],
                'history' => []
            ];
            save_data('data/projects.php', $projects);

            header('Location: projects-own.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $resubmit_mode ? "Edit & Resubmit Project" : "Submit New Project" ?> – Budapest Community Budget</title>
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
            <h1><?= $resubmit_mode ? "Edit & Resubmit Project" : "Submit New Project" ?></h1>

            <form method="post">
                <div class="mt-2">
                    <label for="title">Title:</label>
                    <input id="title" name="title" value="<?= htmlspecialchars($title) ?>">
                </div>

                <div class="mt-2">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="8"><?= htmlspecialchars($desc) ?></textarea>
                </div>

                <div class="mt-2">
                    <label for="category">Category:</label>
                    <select id="category" name="category">
                        <?php foreach (CATEGORIES as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($cat !== null && (int) $cat === (int) $k) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-2">
                    <label for="postal_code">Postal code:</label>
                    <input id="postal_code" name="postal_code" value="<?= htmlspecialchars($pc ?? '') ?>">
                </div>

                <div class="mt-2">
                    <label for="image">Image URL (optional):</label>
                    <input id="image" name="image" value="<?= htmlspecialchars($img) ?>">
                </div>

                <div class="mt-3">
                    <button type="submit">
                        <?= $resubmit_mode ? "Resubmit Project" : "Submit Project" ?>
                    </button>
                </div>
            </form>

            <?php if ($error): ?>
                <p class="form-error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($resubmit_mode && !empty($project['rework_comments'])): ?>
            <div class="project-detail">
                <h2>Admin Rework Comments</h2>
                <ul>
                    <?php foreach ($project['rework_comments'] as $c): ?>
                        <li>[<?= htmlspecialchars($c['time']) ?>]
                            <?= nl2br(htmlspecialchars($c['comment'])) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>