@echo off
cd /d "%~dp0"
set CGO_ENABLED=0
set GOOS=linux
set GOARCH=amd64
"C:\Program Files\Go\bin\go.exe" build -ldflags="-s -w" -o olt-collector .
if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Linux binary olt-collector built successfully.
) else (
    echo [ERROR] Build failed.
)
