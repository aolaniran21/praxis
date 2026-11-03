#!/usr/bin/env bash
set -e

if [[ -z "${APP_URL:-}" && -n "${RENDER_EXTERNAL_URL:-}" ]]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi

# Run deploy scripts in number order
for script in /var/www/html/scripts/*; do
  bash "$script"
done

exec /usr/bin/supervisord -n -c /etc/supervisord.conf