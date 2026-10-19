#!/usr/bin/env bash
# IMS OLT Telemetry Collector (Linux/Unix Start Script)

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$DIR"

if [ ! -f "$DIR/olt-collector" ]; then
    echo "[INFO] Compiling Go collector binary..."
    go build -o olt-collector .
fi

echo "[INFO] Starting daemon on http://127.0.0.1:8088 ..."
exec ./olt-collector
