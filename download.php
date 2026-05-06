<?php
// web/download.php
require_once 'config_web.php';

if (!isset($_GET['id'])) {
    die("ID not specified.");
}

$id = intval($_GET['id']);
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT file_url, file_name FROM web_news WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$result || empty($result['file_url'])) {
    die("File not found.");
}

$filePath = $result['file_url'];
$fileName = $result['file_name'] ?: 'lampiran';

// Ensure the name has the correct extension from the stored path if it was missing
if (strpos($fileName, '.') === false) {
    $pathExt = pathinfo($filePath, PATHINFO_EXTENSION);
    if ($pathExt) {
        $fileName .= '.' . $pathExt;
    }
}

if (!file_exists($filePath)) {
    die("File does not exist on server.");
}

// Clean output buffer to prevent corrupting the file
if (ob_get_level()) ob_end_clean();

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;
?>
