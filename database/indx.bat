@echo off
echo Creating new database...
mysql -v -uroot -p123456 < indx-reset.sql
if "%ERRORLEVEL%" NEQ "0" goto :abort
mysql -v -uroot -p123456 indx < indx.ddl
if "%ERRORLEVEL%" NEQ "0" goto :abort
if "%ERRORLEVEL%" NEQ "0" goto :abort
echo.
echo **********************
echo *       ALL OK       *
echo **********************
goto :end
:abort
echo.
echo ***************************
echo *       CHECK ERROR       *
echo ***************************
pause
:end
echo.