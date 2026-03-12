@echo off
echo ============================================
echo DAS Master Architecture - Complete Setup
echo ============================================
echo.

echo Step 1: Installing PHPWord via Composer...
cd /d "c:\xampp\htdocs\Credit\DAS"

REM Check if composer is installed
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Composer not found!
    echo Please install Composer from https://getcomposer.org/
    pause
    exit /b 1
)

echo Running: composer require phpoffice/phpword
composer require phpoffice/phpword

if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to install PHPWord
    pause
    exit /b 1
)

echo.
echo Step 2: Creating directory structure...
if not exist "generated" mkdir generated
if not exist "temp" mkdir temp
echo Directories created successfully!

echo.
echo ============================================
echo Installation Complete!
echo ============================================
echo.
echo Next Steps:
echo 1. Import database/master_architecture_migration.sql in phpMyAdmin
echo 2. Import database/workflow_enhancements.sql in phpMyAdmin  
echo 3. Import database/comprehensive_placeholders.sql in phpMyAdmin
echo 4. Create master templates in Microsoft Word
echo 5. Upload templates to scheme folders
echo 6. Test the system!
echo.
pause
