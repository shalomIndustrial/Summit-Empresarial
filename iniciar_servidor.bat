@echo off
title Summit Empresarial - Servidor Local
echo ========================================================
echo   Iniciando Servidor Web - Summit Empresarial Mexico
echo ========================================================
echo.
echo Abriendo tu Panel de Control en el navegador...
start http://localhost:8000/admin.php
echo.
echo Servidor activo en http://localhost:8000
echo Para detener el servidor presiona Ctrl + C
echo ========================================================
echo.
"C:\xampp\php\php.exe" -S localhost:8000
pause
