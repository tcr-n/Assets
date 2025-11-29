<?php
// Parcourt récursivement le dossier `logo/` et concatène tous les
// fichiers nommés `lines_picto.csv` en une seule sortie CSV.

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

// If run from a web server, send CSV headers for download
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lines_picto.csv"');
}

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS));
$firstHeaderPrinted = false;
$out = fopen('php://output', 'w');

foreach ($it as $file) {
    if (!$file->isFile()) continue;
    if (strtolower($file->getFilename()) !== 'lines_picto.csv') continue;

    $path = $file->getPathname();
    $handle = fopen($path, 'r');
    if (!$handle) continue;

    $lineNum = 0;
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        // Enlever BOM UTF-8 si présent sur la première ligne du fichier
        if ($lineNum === 1) {
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
        }

        // Imprimer l'en-tête une seule fois (la première en-tête rencontrée)
        if ($lineNum === 1 && !$firstHeaderPrinted) {
            fwrite($out, rtrim($line, "\r\n") . PHP_EOL);
            $firstHeaderPrinted = true;
            continue;
        }

        // Saute la première ligne (header) des fichiers suivants
        if ($lineNum === 1 && $firstHeaderPrinted) {
            continue;
        }

        // Écrire la ligne telle quelle (préserve le délimiteur `;`)
        fwrite($out, rtrim($line, "\r\n") . PHP_EOL);
    }

    fclose($handle);
}

fclose($out);