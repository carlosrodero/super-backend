#!/bin/bash

echo "🔧 Corrigindo permissões do Laravel..."

if [ ! -f "web/artisan" ]; then
    echo "❌ Laravel não está instalado. Execute ./install.sh primeiro."
    exit 1
fi

echo "Ajustando permissões das pastas storage e bootstrap/cache..."
docker compose run --rm app sh -c "
    # Garantir que todas as pastas existem
    mkdir -p /var/www/html/storage/logs
    mkdir -p /var/www/html/storage/framework/cache/data
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/views
    mkdir -p /var/www/html/storage/app/public
    
    # Remover arquivo de log para recriar com permissões corretas
    rm -f /var/www/html/storage/logs/laravel.log
    
    # Aplicar permissões recursivamente
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
    
    # Garantir que o arquivo de log pode ser criado
    touch /var/www/html/storage/logs/laravel.log
    chown www-data:www-data /var/www/html/storage/logs/laravel.log
    chmod 664 /var/www/html/storage/logs/laravel.log
"

echo "✅ Permissões corrigidas!"

