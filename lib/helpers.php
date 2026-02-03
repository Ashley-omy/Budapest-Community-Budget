<?php
require_once __DIR__ . '/../data/config.php';

function can_vote(array $project): bool
{
    if ($project['status'] !== 1) {
        return false;
    }
    if (empty($project['approved'])) {
        return false;
    }
    return (time() - strtotime($project['approved'])) <= VOTING_PERIOD;
}

/**
 * Count how many votes the given user has cast in the given category.
 */
function user_category_vote_count(?array $user, int $category, array $votes): int
{
    if (!$user) {
        return 0;
    }
    $count = 0;
    foreach ($votes as $v) {
        if ($v['user'] == $user['id'] && $v['category'] == $category) {
            $count++;
        }
    }
    return $count;
}

/**
 * Returns true if the user may cast more votes in this category (max 3).
 */
function user_can_vote_in_category(?array $user, int $category, array $votes): bool
{
    return user_category_vote_count($user, $category, $votes) < 3;
}
