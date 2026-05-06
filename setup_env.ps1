# setup_env.ps1
$phpZip = "e:\Storedimz\installers\php.zip"
$phpDest = "C:\php"

Write-Host "Extracting PHP to $phpDest..."
if (!(Test-Path $phpDest)) { New-Item -ItemType Directory -Path $phpDest }
Expand-Archive -Path $phpZip -DestinationPath $phpDest -Force

Write-Host "Adding PHP to PATH..."
$oldPath = [Environment]::GetEnvironmentVariable("Path", "User")
if ($oldPath -notlike "*C:\php*") {
    $newPath = $oldPath + ";C:\php"
    [Environment]::GetEnvironmentVariable("Path", "User")
    [Environment]::SetEnvironmentVariable("Path", $newPath, "User")
    $env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [Environment]::GetEnvironmentVariable("Path", "User")
}

Write-Host "PHP Setup Complete. Please run installers\Composer-Setup.exe manually now."
