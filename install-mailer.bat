@echo off
REM Script pour installer symfony/mailer et symfony/mime malgre l'erreur SSL (Avast).
REM Utilisation : desactivez temporairement le "Scan HTTPS" dans Avast, puis lancez ce script.

cd /d D:\xampp\htdocs\medilink

echo.
echo ============================================
echo  Installation de symfony/mailer et symfony/mime
echo ============================================
echo.

REM Utiliser le PHP de XAMPP avec son php.ini (certificats CA)
set COMPOSER_CAFILE=C:\xampp\apache\bin\curl-ca-bundle.crt
set CURL_CA_BUNDLE=C:\xampp\apache\bin\curl-ca-bundle.crt

composer require symfony/mailer symfony/mime --no-interaction

if %ERRORLEVEL% EQU 0 (
    echo.
    echo OK - Les paquets ont ete installes.
) else (
    echo.
    echo ECHEC - Si vous voyez "SSL certificate problem":
    echo 1. Ouvrez Avast ^> Parametres ^> Protection ^> Boucliers principaux
    echo 2. Desactivez temporairement "Analyse HTTPS" ou "Web Shield"
    echo 3. Relancez ce script: install-mailer.bat
    echo 4. Reactivez la protection Avast apres l'installation.
    echo.
)

pause
