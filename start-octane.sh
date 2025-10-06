#!/bin/bash

# Laravel Octane with FrankenPHP startup script

# Set environment variables
export OCTANE_SERVER=frankenphp
export OCTANE_HOST=127.0.0.1
export OCTANE_PORT=8000
export OCTANE_WORKERS=auto
export OCTANE_MAX_REQUESTS=500

echo "Starting Laravel application with FrankenPHP..."
echo "Server: http://${OCTANE_HOST}:${OCTANE_PORT}"

# Download FrankenPHP binary if not exists
if [ ! -f "./frankenphp" ]; then
    echo "Downloading FrankenPHP..."
    # For Windows, use .exe
    if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "win32" ]]; then
        curl -L -o frankenphp.exe https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-windows-x86_64.exe
        chmod +x frankenphp.exe
        ./frankenphp.exe php-server --listen ${OCTANE_HOST}:${OCTANE_PORT}
    else
        curl -L -o frankenphp https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-x86_64
        chmod +x frankenphp
        ./frankenphp php-server --listen ${OCTANE_HOST}:${OCTANE_PORT}
    fi
else
    echo "Using existing FrankenPHP binary..."
    if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "win32" ]]; then
        ./frankenphp.exe php-server --listen ${OCTANE_HOST}:${OCTANE_PORT}
    else
        ./frankenphp php-server --listen ${OCTANE_HOST}:${OCTANE_PORT}
    fi
fi