<?php
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    
    // Safety check: ensure it's a URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        die("Invalid URL Format");
    }

    // Allow multiple arie-pay related domains
    $allowed = false;
    if (strpos($url, 'arie-pay') !== false || strpos($url, 'qris') !== false) {
        $allowed = true;
    }

    if (!$allowed) {
        die("Domain not allowed for proxy download.");
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="QRIS_PAYMENT_DIMZSTORE.png"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Fetch and pipe the image
    readfile($url);
    exit;
}
?>
