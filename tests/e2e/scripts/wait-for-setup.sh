#!/bin/sh
# Wait for WordPress E2E setup to complete.
# Polls until the wpcli container creates the .e2e-setup-complete flag.

TIMEOUT=180
ELAPSED=0

echo "⏳ Waiting for WordPress setup to complete..."

while [ "$ELAPSED" -lt "$TIMEOUT" ]; do
  if docker compose exec -T wpcli test -f /var/www/html/.e2e-setup-complete 2>/dev/null; then
    echo ""
    echo "✅ WordPress is ready at http://localhost:${PORT:-8080}"
    exit 0
  fi
  printf "."
  sleep 3
  ELAPSED=$((ELAPSED + 3))
done

echo ""
echo "❌ Timeout: WordPress setup did not complete within ${TIMEOUT}s"
echo "   Check logs with: docker compose logs wpcli"
exit 1
