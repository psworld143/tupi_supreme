<?php
// TEMPORARY diagnostic — upload to the LIVE site root, open it once, then DELETE it.
// Reports whether PHP can write into uploads/ on this server.
header('Content-Type: text/plain');

$dir = __DIR__ . '/uploads/images/';
echo "document_root : " . ($_SERVER['DOCUMENT_ROOT'] ?? '?') . "\n";
echo "upload dir    : $dir\n";
echo "dir exists    : " . var_export(is_dir($dir), true) . "\n";
echo "dir writable  : " . var_export(is_writable($dir), true) . "\n";
if (is_dir($dir)) {
    echo "dir perms     : " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
    echo "dir owner uid : " . var_export(@fileowner($dir), true) . "\n";
}
echo "php euid      : " . var_export(function_exists('posix_geteuid') ? @posix_geteuid() : null, true) . "\n";
echo "upload_tmp_dir: " . ini_get('upload_tmp_dir') . "\n";
echo "sys temp dir  : " . sys_get_temp_dir() . " writable=" . var_export(is_writable(sys_get_temp_dir()), true) . "\n";
echo "open_basedir  : " . (ini_get('open_basedir') ?: '(none)') . "\n";
echo "disk free     : " . var_export(@disk_free_space($dir), true) . "\n";

$test = $dir . '_wtest.txt';
$ok = @file_put_contents($test, 'x');
echo "write test    : " . var_export($ok, true) . "\n";
if ($ok !== false) {
    unlink($test);
    echo "write+delete  : OK\n";
} else {
    $e = error_get_last();
    echo "write error   : " . ($e['message'] ?? 'unknown') . "\n";
}
