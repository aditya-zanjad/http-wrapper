<?php

$filePath   =   'temp' . DIRECTORY_SEPARATOR . 'temp.txt';
$fileName   =   \basename($filePath);
$fileLength =   \filesize($filePath);
$tempFile   =   \fopen($filePath, 'w');

\fwrite($tempFile, 'This is a test content.');
\fclose($tempFile);

$mimeType = mime_content_type($filePath);
header("Content-Type: {$mimeType}");
header("Content-Disposition: attachment; filename={$fileName}");
header("Content-Length: {$fileLength}");

ob_clean();
flush();
readfile($filePath);
exit;