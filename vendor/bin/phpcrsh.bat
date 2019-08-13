@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../phpcr/phpcr-shell/bin/phpcrsh
php "%BIN_TARGET%" %*
