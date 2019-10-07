@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../hoa/kitab/bin/kitab
php "%BIN_TARGET%" %*
