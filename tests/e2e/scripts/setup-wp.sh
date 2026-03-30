#!/bin/sh

# Remove setup-complete flag to signal setup is in progress
rm -f /var/www/html/.e2e-setup-complete

echo "⏳ Waiting for database to be ready..."
until wp db check --quiet 2>/dev/null; do
  sleep 2
done
echo "✅ Database is ready."

echo "⏳ Waiting for WordPress to be installed..."
until wp core is-installed --quiet 2>/dev/null; do
  wp core install \
    --url="http://localhost:${PORT:-8080}" \
    --title="Disable Comments Test" \
    --admin_user=admin \
    --admin_password=password \
    --admin_email=admin@example.com \
    --skip-email 2>/dev/null || true
  sleep 3
done
echo "✅ WordPress is installed."

# Basic configuration
wp option update blogdescription "E2E Test Site" --quiet || true
wp option update timezone_string "Asia/Dhaka" --quiet || true
wp rewrite structure "/%postname%/" --quiet || true
wp rewrite flush --quiet || true

# Clean up default plugins (ignore errors if already deleted)
wp plugin delete hello akismet 2>/dev/null || true

# Create test users (idempotent)
wp user get editor --field=ID --quiet 2>/dev/null || \
  wp user create editor editor@example.com --role=editor --user_pass=password --quiet || true
wp user get author --field=ID --quiet 2>/dev/null || \
  wp user create author author@example.com --role=author --user_pass=password --quiet || true
wp user get contributor --field=ID --quiet 2>/dev/null || \
  wp user create contributor contributor@example.com --role=contributor --user_pass=password --quiet || true
wp user get subscriber --field=ID --quiet 2>/dev/null || \
  wp user create subscriber subscriber@example.com --role=subscriber --user_pass=password --quiet || true

# Activate Disable Comments plugin (mounted directly from repo root)
if wp plugin is-installed disable-comments 2>/dev/null; then
  wp plugin activate disable-comments --quiet || true
  echo "✅ Disable Comments plugin activated."
else
  echo "⚠️  Disable Comments plugin not found at wp-content/plugins/disable-comments"
fi

# Signal setup complete
touch /var/www/html/.e2e-setup-complete
echo "✅ WordPress setup complete!"
