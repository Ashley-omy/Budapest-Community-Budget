<?php
session_start();

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    if (!current_user()) {
        header('Location: index.php');
        exit;
    }
}

function require_admin()
{
    if (!current_user() || !current_user()['is_admin']) {
        header('Location: index.php');
        exit;
    }
}
