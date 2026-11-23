@echo off
rem Respaldo de la base de datos de RecuperaYa (XAMPP). Uso: doble clic o "deploy\respaldo_bd.bat"
rem Restaurar:  C:\xampp\mysql\bin\mysql.exe -u root recuperaya_laravel < archivo.sql
setlocal
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmm"') do set FECHA=%%i
set DESTINO=%USERPROFILE%\Desktop\Respaldos_RecuperaYa
if not exist "%DESTINO%" mkdir "%DESTINO%"
echo Exportando recuperaya_laravel...
C:\xampp\mysql\bin\mysqldump.exe -u root --default-character-set=utf8mb4 --single-transaction --routines recuperaya_laravel > "%DESTINO%\recuperaya_laravel_%FECHA%.sql"
if errorlevel 1 (echo Error al crear el respaldo & exit /b 1)
echo Respaldo creado: %DESTINO%\recuperaya_laravel_%FECHA%.sql
endlocal
