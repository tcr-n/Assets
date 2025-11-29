<?php
// Parcourt récursivement le dossier `logo/` et fusionne tous les
// fichiers nommés `trafic.json` en une seule sortie JSON.

// Définir le répertoire de base (par défaut : dossier `logo` à côté du script)
$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'logo';
if (php_sapi_name() === 'cli') {
	global $argv;
	if (isset($argv[1]) && $argv[1] !== '') {
		$baseDir = $argv[1];
	}
}

if (!is_dir($baseDir)) {
	$msg = "Directory not found: $baseDir\n";
	if (php_sapi_name() === 'cli') {
		fwrite(STDERR, $msg);
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo $msg;
	}
	exit(2);
}

// If run from a web server, send JSON headers for download
if (php_sapi_name() !== 'cli') {
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="trafic.json"');
}

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS));
$merged = [];

function is_list_array(array $arr): bool {
	return array_keys($arr) === range(0, count($arr) - 1);
}

foreach ($it as $file) {
	if (!$file->isFile()) continue;
	if (strtolower($file->getFilename()) !== 'trafic.json') continue;

	$path = $file->getPathname();
	$contents = file_get_contents($path);
	if ($contents === false) continue;

	// Remove UTF-8 BOM if present
	$contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

	$data = json_decode($contents, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		// Skip files with invalid JSON
		if (php_sapi_name() === 'cli') {
			fwrite(STDERR, "Warning: skipping invalid JSON file: $path\n");
		}
		continue;
	}

	if (is_array($data)) {
		// If the decoded value is a list (array of items), merge its elements
		if (is_list_array($data)) {
			foreach ($data as $item) {
				$merged[] = $item;
			}
		} else {
			// Associative array / object -> add as single entry
			$merged[] = $data;
		}
	} else {
		// Scalars (unlikely) -> add as-is
		$merged[] = $data;
	}
}

$output = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($output === false) {
	if (php_sapi_name() === 'cli') {
		fwrite(STDERR, "Error encoding merged JSON: " . json_last_error_msg() . "\n");
		exit(3);
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo "Error encoding merged JSON: " . json_last_error_msg();
		exit(3);
	}
}

echo $output;

