#!/usr/bin/env bash
# IMS OLT Telemetry Collector (Linux/Unix Start Script)

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$DIR"

if [ ! -f "$DIR/olt-collector" ]; then
    if ! command -v go &> /dev/null; then
        echo "[ERROR] Golang belum terpasang di server Linux Anda."
        echo "Silakan install terlebih dahulu dengan perintah:"
        echo "   sudo apt update && sudo apt install -y golang-go"
        echo ""
        exit 1
    fi

    echo "[INFO] Compiling Go collector binary..."
    go build -o olt-collector .
    if [ $? -ne 0 ]; then
        echo "[ERROR] Kompilasi gagal."
        exit 1
    fi
fi

chmod +x "$DIR/olt-collector"
echo "[INFO] Starting daemon on http://127.0.0.1:8088 ..."
exec "$DIR/olt-collector"
