@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../voryx/thruway/bin/thruway
php "%BIN_TARGET%" %*
