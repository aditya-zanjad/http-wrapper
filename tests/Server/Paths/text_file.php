<?php

$fileName   =   'temp.txt';
$filePath   =   getenv('SERVER_TEMP_DIRECTORY') . DIRECTORY_SEPARATOR . $fileName;
$tempFile   =   \fopen($filePath, 'w');

\fwrite($tempFile, 'This is a test content generated for test case.');
\fclose($tempFile);

$fileLength =   \filesize($filePath);
$mimeType   =   (new finfo(FILEINFO_MIME_TYPE))->file($filePath);

header("Content-Type: {$mimeType}; charset=UTF-8");
header("Content-Disposition: attachment; filename={$fileName}");
header("Content-Length: {$fileLength}");

ob_clean();
flush();
readfile($filePath);
