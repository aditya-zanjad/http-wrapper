<?php

$fileDir = 'temp';

if (!is_dir($fileDir)) {
    mkdir($fileDir, 0755, true);
}

$fileName   =   'temp.txt';
$filePath   =   $fileDir . DIRECTORY_SEPARATOR . $fileName;
$tempFile   =   \fopen($filePath, 'w');

\fwrite($tempFile, 'This is a test content generated for test case.');
\fclose($tempFile);

$fileLength =   \filesize($filePath);
$mimeType   =   (new finfo(FILEINFO_MIME_TYPE))->file($filePath);

header("Content-Type: {$mimeType}");
header("Content-Disposition: attachment; filename={$fileName}");
header("Content-Length: {$fileLength}");

ob_clean();
flush();
readfile($filePath);
exit;