#!/bin/bash

echo "🔧 Corrigindo permissões do Laravel..."

if [ ! -f "web/artisan" ]; then
    echo "❌ Laravel não está instalado. Execute ./install.sh primeiro."
    exit 1
fi

echo "Ajustando permissões das pastas storage e bootstrap/cache..."
docker compose run --rm app sh -c "
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
"

echo "✅ Permissões corrigidas!"

