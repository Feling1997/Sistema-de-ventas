@echo off
echo ============================================
echo SCRIPT DE PERSONALIZACION AUTOMATICA
echo PARA LOGO EN TICKETS/COMANDERA
echo ============================================
echo.

set /p RUTA_XAMPP="Ingresa la ruta completa de XAMPP (ej: C:\xampp): "
set /p RUTA_LOGO="Ingresa la ruta completa del logo para tickets (ej: C:\logo_ticket.png): "

if not exist "%RUTA_XAMPP%" (
    echo ERROR: La ruta de XAMPP no existe
    pause
    exit /b 1
)

if not exist "%RUTA_LOGO%" (
    echo ERROR: El archivo de logo no existe
    pause
    exit /b 1
)

echo.
echo Copiando logo a la carpeta de assets...
if not exist "%RUTA_XAMPP%\htdocs\VENTAS\publico\assets\img" (
    mkdir "%RUTA_XAMPP%\htdocs\VENTAS\publico\assets\img"
)

copy "%RUTA_LOGO%" "%RUTA_XAMPP%\htdocs\VENTAS\publico\assets\img\logo_ticket.png" /Y

echo.
echo Modificando ControladorReparaciones.php...

REM Crear archivo temporal con el código modificado
powershell -Command "& {
    $file = '%RUTA_XAMPP%\htdocs\VENTAS\aplicacion\controladores\ControladorReparaciones.php'
    $content = Get-Content $file -Raw -Encoding UTF8

    # Buscar la línea específica y agregar el logo
    $pattern = '<div class=''ticket''>\s*<div class=''titulo''>\$comercio</div>'
    $replacement = '<div class=''ticket''>`n    <div style=''text-align: center; margin: 5px 0;''>`n        <img src=''/VENTAS/publico/assets/img/logo_ticket.png''`n             style=''max-width: 60mm; height: auto;'' alt=''Logo''>`n    </div>`n    <div class=''titulo''>$comercio</div>'

    $newContent = $content -replace $pattern, $replacement

    # Agregar CSS para el logo
    $cssPattern = '@media print \{\s*\.acciones \{ display: none; \}\s*\}'
    $cssReplacement = '@media print {`n    .acciones { display: none; }`n}`n.logo-ticket {`n    max-width: 60mm;`n    height: auto;`n    margin: 5px 0;`n}'

    $newContent = $newContent -replace $cssPattern, $cssReplacement

    $newContent | Out-File -FilePath $file -Encoding UTF8
}"

echo.
echo Â¡Personalizacion completada!
echo.
echo RECUERDA:
echo - Reiniciar XAMPP
echo - Limpiar cache del navegador
echo - Probar imprimir un ticket de reparacion
echo.
pause