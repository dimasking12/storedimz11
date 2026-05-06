# sync.ps1 - Improved PowerShell script for FTP sync (CLEAN version)
$ftpHost = "ftp://sago.iixcp.rumahweb.net/public_html/store/"
$username = "vipc7892"
$password = "Dimasahm12#"
$localFolder = "e:\Storedimz"

# Files/extensions to skip (sensitive, debug, or temporary files)
$skipFiles = @("sync.ps1", "error_log.txt", ".DS_Store")
$skipExtensions = @(".sql", ".json")
$skipDirs = @(".git")

function Create-FTPDirectory($url) {
    try {
        $request = [System.Net.FtpWebRequest]::Create($url)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
        $response = $request.GetResponse()
        $response.Close()
    } catch { }
}

function Upload-File($localPath, $remotePath) {
    try {
        $target = $ftpHost + $remotePath
        $request = [System.Net.FtpWebRequest]::Create($target)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
        
        $fileContent = [System.IO.File]::ReadAllBytes($localPath)
        $request.ContentLength = $fileContent.Length
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        Write-Host "Uploaded: $remotePath" -ForegroundColor Green
    } catch {
        Write-Host "Failed: $remotePath - $($_.Exception.Message)" -ForegroundColor Red
    }
}

function Sync-Folder($currentLocalDir, $relativeDir) {
    if ($relativeDir -ne "") {
        Create-FTPDirectory ($ftpHost + $relativeDir)
    }

    $items = Get-ChildItem -Path $currentLocalDir
    foreach ($item in $items) {
        $relPath = if ($relativeDir -eq "") { $item.Name } else { "$relativeDir/$($item.Name)" }
        
        if ($item.PSIsContainer) {
            if ($item.Name -notin $skipDirs) {
                Sync-Folder $item.FullName $relPath
            }
        } else {
            $ext = $item.Extension.ToLower()
            if ($item.Name -notin $skipFiles -and $ext -notin $skipExtensions) {
                Upload-File $item.FullName $relPath
            }
        }
    }
}

Write-Host "Starting sync to $ftpHost..." -ForegroundColor Cyan
Sync-Folder $localFolder ""
Write-Host "Sync completed!" -ForegroundColor Cyan
