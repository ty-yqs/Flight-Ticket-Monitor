#!/usr/bin/env bash
set -euo pipefail

# Downloads Vue 2 and Element UI (CSS + JS) into ./static/
# Usage: ./scripts/fetch_frontend_deps.sh

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
STATIC_DIR="$ROOT_DIR/static"
mkdir -p "$STATIC_DIR"

echo "Downloading Vue 2..."
curl -sSL -o "$STATIC_DIR/vue.min.js" "https://unpkg.com/vue@2.6.14/dist/vue.min.js"

echo "Downloading Element UI JS..."
curl -sSL -o "$STATIC_DIR/element-ui.js" "https://unpkg.com/element-ui@2.15.13/lib/index.js"

echo "Downloading Element UI CSS..."
curl -sSL -o "$STATIC_DIR/element-ui.css" "https://unpkg.com/element-ui@2.15.13/lib/theme-chalk/index.css"

echo "Downloaded files to $STATIC_DIR"

ls -la "$STATIC_DIR"

echo "Done. You can serve /static/ directory as static assets (php -S serves current dir)."
