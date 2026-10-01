@echo off
title Koperasi Sekolah Desktop Application
echo ========================================================
echo Starting Koperasi Sekolah POS and Management Desktop App...
echo ========================================================

cd /d "%~dp0"
if exist "%~dp0Publish\KoopSchoolDesktop.exe" (
    start "" /d "%~dp0Publish" "%~dp0Publish\KoopSchoolDesktop.exe"
    exit /b 0
) else (
    cd /d "%~dp0KoopSchoolDesktop"
    dotnet run --configuration Release
)
