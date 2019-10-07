@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../atoum/praspel-extension/Bin/praspel
php "%BIN_TARGET%" %*
