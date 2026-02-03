<?php
// Load PHP array data from file
function load_data(string $file): array
{
    return file_exists($file) ? include $file : [];
}

// Save PHP array data permanently
// function save_data(string $file, array $data): void
// {
//     $export = "<?php\nreturn " . var_export($data, true) . ";\n";
//     file_put_contents($file, $export, LOCK_EX);
// }
function save_data(string $file, array $data): void
{
    // PHP の配列として保存
    $php = "<?php\nreturn " . var_export($data, true) . ";\n";

    file_put_contents($file, $php);

    // --- ここがポイント：OPcache を更新させる ---
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($file, true);   // force = true で強制再コンパイル
    }
}