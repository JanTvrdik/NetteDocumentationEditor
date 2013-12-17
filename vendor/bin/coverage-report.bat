@ECHO OFF
SET BIN_TARGET=%~dp0/../nette/tester/Tester/coverage-report
php "%BIN_TARGET%" %*
