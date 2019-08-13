@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../phpcr/phpcr-utils/bin/phpcr
php "%BIN_TARGET%" %*
