@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../hoa/cli/Bin/hoa
php "%BIN_TARGET%" %*
