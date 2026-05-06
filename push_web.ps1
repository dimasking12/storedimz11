# ============================================================
# push_web.ps1 - Upload Sistem Non-Laravel ke public_html/store
# ============================================================

$ftpHost    = "ftp.vip1120.site"
$ftpUser    = "vipc7892"
$ftpPass    = "Dimasahm12#"
$localBase  = "E:\Storedimz"
$remoteBase = "/public_html/store"

# Folder yang tetap harus dilewati agar tidak terhapus/rusak
$skipFolders = @("admin", "botdimas", "vendor", ".git")

function Upload-File($localPath, $remotePath) {
    $uri = "ftp://$ftpHost" + $remotePath
    try {
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.UseBinary = $true
        $request.UsePassive = $true
        $request.KeepAlive = $false
        $content = [System.IO.File]::ReadAllBytes($localPath)
        $request.ContentLength = $content.Length
        $stream = $request.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "  [OK] $remotePath" -ForegroundColor Green
    } catch {
        Write-Host "  [ERR] $remotePath - $($_.Exception.Message)" -ForegroundColor Red
    }
}

function Create-RemoteDir($remotePath) {
    $uri = "ftp://$ftpHost" + $remotePath
    try {
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.UsePassive = $true
        $response = $request.GetResponse()
        $response.Close()
    } catch { }
}

function Upload-Dir($localDir, $remoteDir) {
    $folderName = Split-Path $localDir -Leaf
    if ($skipFolders -contains $folderName) {
        Write-Host "  [SKIP] $folderName" -ForegroundColor Yellow
        return
    }
    Create-RemoteDir $remoteDir
    foreach ($file in (Get-ChildItem -Path $localDir -File)) {
        # Jangan upload script push itu sendiri
        if ($file.Name -like "push_*.ps1") { continue }
        Upload-File $file.FullName ($remoteDir + "/" + $file.Name)
    }
    foreach ($dir in (Get-ChildItem -Path $localDir -Directory)) {
        Upload-Dir $dir.FullName ($remoteDir + "/" + $dir.Name)
    }
}

Write-Host "Mulai upload sistem baru ke /store..."
Upload-Dir $localBase $remoteBase
Write-Host "Upload selesai! Silakan cek shop.vip1120.site"
