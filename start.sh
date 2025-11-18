#!/bin/bash

echo "🚀 Iniciando containers do Super Backend..."

# Iniciar containers
docker compose up -d

# Aguardar containers iniciarem
echo "⏳ Aguardando containers iniciarem..."
sleep 5

# Corrigir permissões
echo "🔧 Ajustando permissões..."
./fix-permissions.sh

echo "✅ Ambiente pronto!"
echo ""
echo "Acesse a aplicação em: http://localhost:8000"
echo "API disponível em: http://localhost:8000/api"

