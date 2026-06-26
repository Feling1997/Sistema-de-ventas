<?php
$base = __DIR__ . '/../../src/backups';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
$errors = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if (strtolower($file->getExtension()) !== 'php') continue;
    $path = $file->getPathname();
    $cmd = 'php -l ' . escapeshellarg($path);
    exec($cmd, $out, $rc);
    if ($rc !== 0) {
        $errors[$path] = implode("\n", $out);
    }
}
if (empty($errors)) {
    echo "No syntax errors in src/backups\n";
    exit(0);
}
foreach ($errors as $p => $msg) {
    echo "FILE: $p\n";
    echo $msg . "\n\n";
}
exit(1);
