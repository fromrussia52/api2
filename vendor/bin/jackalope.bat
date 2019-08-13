@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../jackalope/jackalope-doctrine-dbal/bin/jackalope
php "%BIN_TARGET%" %*
