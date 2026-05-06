<?php
// cleanup.php - Menghapus folder sisa routing lama yang menyebabkan bug
$folders = ['dashboard', 'store', 'rental', 'login', 'admin', 'history', 'profile', 'logout', 'redeem', 'history_rental', 'register'];

echo "<pre>";
foreach ($folders as $folder) {
    $dir = __DIR__ . '/' . $folder;
    if (is_dir($dir)) {
        echo "Menghapus folder: $folder...\n";
        deleteDir($dir);
        echo "Berhasil dihapus.\n";
    } else {
        echo "Folder $folder tidak ditemukan, dilewati.\n";
    }
}
echo "Pembersihan selesai! Silakan hapus file cleanup.php ini.\n";
echo "</pre>";

function deleteDir($dirPath) {
    if (!is_dir($dirPath)) return;
    $files = array_diff(scandir($dirPath), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dirPath/$file")) ? deleteDir("$dirPath/$file") : unlink("$dirPath/$file");
    }
    return rmdir($dirPath);
}
?>
