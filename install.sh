#!/bin/bash

echo "🚀 Instalando Super Backend..."

# Verificar se o diretório web existe e se já tem Laravel instalado
if [ ! -d "web" ] || [ ! -f "web/artisan" ]; then
    echo "📦 Instalando Laravel 10..."
    
    # Criar diretório web se não existir
    mkdir -p web
    
    # Instalar Laravel via Composer
    docker compose run --rm app composer create-project laravel/laravel:^10.0 . --prefer-dist --no-interaction
    
    echo "✅ Laravel instalado com sucesso!"
else
    echo "✅ Laravel já está instalado em web/"
fi

# Verificar se o arquivo .env existe
if [ ! -f "web/.env" ]; then
    echo "⚙️  Configurando arquivo .env..."
    
    if [ -f "web/.env.example" ]; then
        cp web/.env.example web/.env
        # Garantir que o banco de dados está correto usando perl (mais confiável)
        perl -i -pe 's/^DB_DATABASE=.*/DB_DATABASE=super_backend/' web/.env 2>/dev/null || \
        perl -i -pe 's/^DB_DATABASE=.*/DB_DATABASE=super_backend/' web/.env
        perl -i -pe 's/^DB_HOST=.*/DB_HOST=db/' web/.env 2>/dev/null || \
        perl -i -pe 's/^DB_HOST=.*/DB_HOST=db/' web/.env
        perl -i -pe 's/^DB_USERNAME=.*/DB_USERNAME=super_backend/' web/.env 2>/dev/null || \
        perl -i -pe 's/^DB_USERNAME=.*/DB_USERNAME=super_backend/' web/.env
        perl -i -pe 's/^DB_PASSWORD=.*/DB_PASSWORD=root/' web/.env 2>/dev/null || \
        perl -i -pe 's/^DB_PASSWORD=.*/DB_PASSWORD=root/' web/.env
    else
        echo "⚠️  Arquivo .env.example não encontrado. Criando .env básico..."
        cat > web/.env << EOF
APP_NAME=SuperBackend
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=super_backend
DB_USERNAME= root
DB_PASSWORD=root

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
EOF
    fi
    
    echo "✅ Arquivo .env criado!"
else
    echo "✅ Arquivo .env já existe"
    # Verificar e corrigir configurações do banco se necessário
    if ! grep -q "DB_DATABASE=super_backend" web/.env 2>/dev/null; then
        echo "🔧 Corrigindo configuração do banco de dados no .env..."
        # Usar o container Docker para editar (evita problemas de permissão)
        if docker compose ps app | grep -q "Up"; then
            docker compose exec -T app sh -c "cd /var/www/html && perl -i -pe 's/^DB_DATABASE=.*/DB_DATABASE=super_backend/' .env && perl -i -pe 's/^DB_HOST=.*/DB_HOST=db/' .env && perl -i -pe 's/^DB_USERNAME=.*/DB_USERNAME=super_backend/' .env && perl -i -pe 's/^DB_PASSWORD=.*/DB_PASSWORD=root/' .env" 2>/dev/null
        else
            # Se o container não estiver rodando, tentar editar localmente
            if command -v perl >/dev/null 2>&1; then
                perl -i -pe 's/^DB_DATABASE=.*/DB_DATABASE=super_backend/' web/.env 2>/dev/null || true
                perl -i -pe 's/^DB_HOST=.*/DB_HOST=db/' web/.env 2>/dev/null || true
                perl -i -pe 's/^DB_USERNAME=.*/DB_USERNAME=super_backend/' web/.env 2>/dev/null || true
                perl -i -pe 's/^DB_PASSWORD=.*/DB_PASSWORD=root/' web/.env 2>/dev/null || true
            fi
        fi
        echo "✅ Configurações do banco corrigidas!"
    fi
fi

# Gerar chave da aplicação se não existir
if ! grep -q "APP_KEY=base64:" web/.env 2>/dev/null; then
    echo "🔑 Gerando chave da aplicação..."
    docker compose run --rm app php artisan key:generate
    echo "✅ Chave gerada!"
else
    echo "✅ Chave da aplicação já configurada"
fi

# Iniciar containers
echo "🐳 Iniciando containers Docker..."
docker compose up -d

# Aguardar MySQL estar pronto
echo "⏳ Aguardando MySQL estar pronto..."
MAX_ATTEMPTS=30
ATTEMPT=0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    # Verificar se o container está rodando
    if ! docker compose ps db | grep -q "Up"; then
        echo "   Container MySQL não está rodando. Aguardando..."
        ATTEMPT=$((ATTEMPT + 1))
        sleep 2
        continue
    fi
    
    # Tentar conectar ao MySQL
    if docker compose exec -T db mysqladmin ping -h localhost --silent 2>/dev/null || \
       docker compose exec -T db mysql -uroot -proot -e "SELECT 1" >/dev/null 2>&1; then
        echo "✅ MySQL está pronto!"
        break
    fi
    
    ATTEMPT=$((ATTEMPT + 1))
    if [ $((ATTEMPT % 5)) -eq 0 ]; then
        echo "   Tentativa $ATTEMPT/$MAX_ATTEMPTS... (aguardando MySQL inicializar)"
    fi
    sleep 2
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo "⚠️  Aviso: MySQL pode não estar totalmente pronto após $MAX_ATTEMPTS tentativas"
    echo "   Continuando mesmo assim..."
fi

# Aguardar mais um pouco para garantir que o MySQL está totalmente inicializado
echo "   Aguardando mais 3 segundos para garantir inicialização completa..."
sleep 3

# Executar migrações e seeders
echo "📊 Executando migrações e seeders..."
docker compose run --rm app php artisan migrate --seed --force

# Ajustar permissões
if [ -f "fix-permissions.sh" ]; then
    echo "🔧 Ajustando permissões..."
    chmod +x fix-permissions.sh
    ./fix-permissions.sh
else
    echo "⚠️  Script fix-permissions.sh não encontrado. Ajustando permissões manualmente..."
    sudo chown -R $(whoami):www-data web/storage web/bootstrap/cache
    chmod -R 775 web/storage web/bootstrap/cache
fi

echo ""
echo "✅ Instalação concluída!"
echo ""
echo "📝 Próximos passos:"
echo "   1. Inicie o worker de filas: docker compose run --rm app php artisan queue:work"
echo "   2. Acesse a aplicação: http://localhost:8000"
echo "   3. API disponível em: http://localhost:8000/api"
echo ""
echo "📚 Consulte o README.md para mais informações"

