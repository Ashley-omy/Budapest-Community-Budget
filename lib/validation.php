<?php

function valid_username(string $u): bool
{
    return !preg_match('/\s/', $u);
}

function valid_password(string $p): bool
{
    return strlen($p) >= 8 &&
        preg_match('/[a-z]/', $p) &&
        preg_match('/[A-Z]/', $p) &&
        preg_match('/[0-9]/', $p);
}

function valid_email(string $e): bool
{
    return filter_var($e, FILTER_VALIDATE_EMAIL);
}

function valid_postal_code(int $pc): bool
{
    if ($pc === 1007) //1007 is valid (Margaret Island)
        return true;
    if ($pc < 1000 || $pc > 1999) //first digit is 1(Budapest)
        return false;
    //second and third digits: district number 01–23
    //fourth digit: 1–9
    $district = intval(substr((string) $pc, 1, 2));
    $last = intval(substr((string) $pc, 3, 1));
    return $district >= 1 && $district <= 23 && $last >= 1 && $last <= 9;
}

function valid_image_url(?string $url): bool
{
    if (!$url)
        return true;
    return filter_var($url, FILTER_VALIDATE_URL);
}
