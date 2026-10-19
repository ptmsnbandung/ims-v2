@echo off
echo ========================================================
echo   Starting IMS OLT Telemetry Collector (Golang Engine)
echo ========================================================
echo.

if not exist "%~dp0olt-collector.exe" (
    echo [INFO] Compiling Go collector binary...
    cd /d "%~dp0"
    go build -o olt-collector.exe .
    if errorlevel 1 (
        echo [ERROR] Compilation failed. Please ensure Go is installed.
        pause
        exit /b 1
    )
)

echo [INFO] Starting daemon on http://127.0.0.1:8088 ...
cd /d "%~dp0"
olt-collector.exe
