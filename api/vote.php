<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$projects = load_data(__DIR__ . '/../data/projects.php');
$votes = file_exists(__DIR__ . '/../data/votes.php')
    ? load_data(__DIR__ . '/../data/votes.php')
    : [];

$pid = $data['project_id'] ?? null;
$action = $data['action'] ?? null;

// Basic validity & voting window check
if ($pid === null || !isset($projects[$pid]) || !can_vote($projects[$pid])) {
    echo json_encode(['success' => false, 'error' => 'Voting closed']);
    exit;
}

$project = $projects[$pid];
$category = (int) $project['category'];

// Count how many votes the user already has in this category
$usedInCategory = user_category_vote_count($user, $category, $votes);

if ($action === 'add') {
    // Prevent duplicate votes for the same project
    foreach ($votes as $v) {
        if ($v['user'] == $user['id'] && $v['project'] == $pid) {
            echo json_encode(['success' => false, 'error' => 'Already voted']);
            exit;
        }
    }

    // Enforce max 3 votes per category
    if ($usedInCategory >= 3) {
        echo json_encode(['success' => false, 'error' => 'Vote limit reached']);
        exit;
    }

    // Add new vote
    $votes[] = [
        'user' => $user['id'],
        'project' => $pid,
        'category' => $category
    ];
    $projects[$pid]['votes'] = ($projects[$pid]['votes'] ?? 0) + 1;
}

if ($action === 'remove') {
    // Remove only the matching vote
    foreach ($votes as $k => $v) {
        if ($v['user'] == $user['id'] && $v['project'] == $pid) {
            unset($votes[$k]);
            $projects[$pid]['votes'] = max(0, ($projects[$pid]['votes'] ?? 0) - 1);
        }
    }
}

// Save updated data
save_data(__DIR__ . '/../data/votes.php', $votes);
save_data(__DIR__ . '/../data/projects.php', $projects);

// Recalculate usage after update
$usedAfter = user_category_vote_count($user, $category, $votes);
$remaining = max(0, 3 - $usedAfter);

echo json_encode([
    'success' => true,
    'votes' => (int) ($projects[$pid]['votes'] ?? 0),
    'remaining' => $remaining,
    'category' => $category,
    'action' => $action
]);
