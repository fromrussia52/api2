@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../doctrine/phpcr-odm/bin/phpcrodm
php "%BIN_TARGET%" %*
