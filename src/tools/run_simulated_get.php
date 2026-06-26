<?php
// Usage: php run_simulated_get.php controller action
error_reporting(E_ALL);
ini_set('display_errors', '1');

$controller = $argv[1] ?? '';
$action = $argv[2] ?? '';

// Prepare superglobals to simulate a GET request
$_GET = ['c' => $controller, 'a' => $action];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

register_shutdown_function(function() use ($controller, $action) {
	$err = error_get_last();
	if ($err) {
		echo "\n<<<SIMULATED_FATAL>>>\n";
		echo json_encode($err, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
	}
	echo "<<<SIMULATED_END {$controller}/{$action}>>>\n";
});

try {
	include __DIR__ . '/../../publico/index.php';
} catch (Throwable $e) {
	echo "\n<<<SIMULATED_EXCEPTION>>>\n";
	echo get_class($e) . ": " . $e->getMessage() . "\n";
	echo $e->getTraceAsString() . "\n";
}