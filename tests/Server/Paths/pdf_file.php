<?php
/**
 * 1. DEFINE PDF OBJECTS
 * We use an array to track byte offsets for the Cross-Reference (xref) table.
 */
$pdfObjects = [];
$header = "%PDF-1.4\n";

// Object 1: The Catalog (The root of the PDF)
$pdfObjects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
// Object 2: The Pages tree
$pdfObjects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
// Object 3: The Page definition (MediaBox defines the size: 612x792 is Letter)
$pdfObjects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj";
// Object 4: The Font
$pdfObjects[4] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";

// Object 5: The Content Stream (The text "Hello, Developer!")
$content = "BT /F1 24 Tf 70 700 Td (Hello, Developer!) Tj ET";
$pdfObjects[5] = "5 0 obj\n<< /Length " . \strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj";

/**
 * 2. CALCULATE OFFSETS & BUILD BODY
 */
$body = "";
$offsets = [];
$currentOffset = \strlen($header);

foreach ($pdfObjects as $id => $obj) {
    $offsets[$id] = $currentOffset;
    $body .= $obj . "\n";
    $currentOffset += \strlen($obj) + 1;
}

/**
 * 3. BUILD XREF AND TRAILER
 */
$xref = "xref\n0 " . (count($pdfObjects) + 1) . "\n";
$xref .= "0000000000 65535 f \n";
foreach ($offsets as $offset) {
    $xref .= \sprintf("%010d 00000 n \n", $offset);
}

$startxref = $currentOffset;
$trailer = "trailer\n<< /Size " . (count($pdfObjects) + 1) . " /Root 1 0 R >>\n";
$trailer .= "startxref\n" . $startxref . "\n%%EOF";

/**
 * 4. THE RESPONSE (Sending to Browser)
 */
$fullPdf = $header . $body . $xref . $trailer;

// Clear any previous output or whitespace to prevent corruption
if (\ob_get_length()) \ob_end_clean();

\header('Content-Type: application/pdf');
\header('Content-Length: ' . \strlen($fullPdf));
\header('Content-Disposition: inline; filename="generated_report.pdf"');
\header('Cache-Control: private, max-age=0, must-revalidate');
\header('Pragma: public');

echo $fullPdf;
exit;