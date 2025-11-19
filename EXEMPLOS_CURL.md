# Exemplos de cURL - API Super Backend

## 📋 Pré-requisitos

1. **Fazer login** para obter o token de autenticação
2. **Usar o token** no header `Authorization: Bearer {token}` para todas as requisições protegidas

---

## 🔐 1. Login (obter token)

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "usuario@example.com",
    "password": "senha123456"
  }'
```

**Resposta:**
```json
{
  "message": "Login realizado com sucesso",
  "user": {
    "id": 1,
    "name": "Usuário Exemplo",
    "email": "usuario@example.com"
  },
  "access_token": "2|abc123def456...",
  "token_type": "Bearer"
}
```

**💡 Dica:** Salve o `access_token` em uma variável:
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@example.com","password":"senha123456"}' \
  | jq -r '.access_token')

echo "Token: $TOKEN"
```

---

## 💸 2. Criar PIX

### Exemplo básico (apenas valor obrigatório)

```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "amount": 125.50
  }'
```

### Exemplo completo (com dados do pagador)

```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "amount": 250.00,
    "payer_name": "João da Silva",
    "payer_cpf": "12345678900"
  }'
```

### Usando variável de token

```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "amount": 100.00,
    "payer_name": "Maria Oliveira",
    "payer_cpf": "98765432100"
  }'
```

**Resposta de Sucesso (201):**
```json
{
  "message": "PIX criado com sucesso",
  "data": {
    "id": 1,
    "external_id": "TXN123456",
    "amount": "250.00",
    "status": "PENDING",
    "created_at": "2025-11-19T10:30:00.000000Z"
  }
}
```

**Resposta de Erro (422 - Validação):**
```json
{
  "message": "O valor do PIX é obrigatório.",
  "errors": {
    "amount": ["O valor do PIX é obrigatório."]
  }
}
```

**Resposta de Erro (401 - Não autenticado):**
```json
{
  "message": "Unauthenticated."
}
```

---

## 📋 3. Listar PIX do usuário

```bash
curl -X GET http://localhost:8000/api/pix \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta:**
```json
{
  "data": [
    {
      "id": 1,
      "external_id": "TXN123456",
      "amount": "250.00",
      "status": "CONFIRMED",
      "payer_name": "João da Silva",
      "payer_cpf": "12345678900",
      "payment_date": "2025-11-19T10:35:00.000000Z",
      "created_at": "2025-11-19T10:30:00.000000Z"
    }
  ]
}
```

---

## 🔍 4. Ver detalhes de um PIX específico

```bash
curl -X GET http://localhost:8000/api/pix/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta:**
```json
{
  "data": {
    "id": 1,
    "external_id": "TXN123456",
    "amount": "250.00",
    "status": "CONFIRMED",
    "payer_name": "João da Silva",
    "payer_cpf": "12345678900",
    "payment_date": "2025-11-19T10:35:00.000000Z",
    "metadata": {
      "api_response": {...},
      "webhook_received_at": "2025-11-19T10:35:00.000000Z"
    },
    "created_at": "2025-11-19T10:30:00.000000Z",
    "updated_at": "2025-11-19T10:35:00.000000Z"
  }
}
```

---

## 💰 5. Criar Saque

```bash
curl -X POST http://localhost:8000/api/withdraw \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "amount": 500.00,
    "bank_account": {
      "bank_code": "341",
      "agency": "1234",
      "account": "56789-0",
      "account_type": "CHECKING",
      "account_holder_name": "João da Silva",
      "account_holder_document": "12345678900"
    }
  }'
```

**Resposta de Sucesso (201):**
```json
{
  "message": "Saque criado com sucesso",
  "data": {
    "id": 1,
    "external_id": "TXN789012",
    "amount": "500.00",
    "status": "PENDING",
    "requested_at": "2025-11-19T10:40:00.000000Z",
    "created_at": "2025-11-19T10:40:00.000000Z"
  }
}
```

---

## 📝 Script completo de exemplo

```bash
#!/bin/bash

# Configurações
API_URL="http://localhost:8000/api"
EMAIL="usuario@example.com"
PASSWORD="senha123456"

# 1. Fazer login
echo "🔐 Fazendo login..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$EMAIL\",
    \"password\": \"$PASSWORD\"
  }")

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.access_token')

if [ "$TOKEN" == "null" ] || [ -z "$TOKEN" ]; then
  echo "❌ Erro ao fazer login"
  echo $LOGIN_RESPONSE | jq '.'
  exit 1
fi

echo "✅ Login realizado com sucesso"
echo "Token: ${TOKEN:0:20}..."

# 2. Criar PIX
echo ""
echo "💸 Criando PIX..."
PIX_RESPONSE=$(curl -s -X POST "$API_URL/pix" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "amount": 125.50,
    "payer_name": "João da Silva",
    "payer_cpf": "12345678900"
  }')

echo $PIX_RESPONSE | jq '.'

PIX_ID=$(echo $PIX_RESPONSE | jq -r '.data.id')

if [ "$PIX_ID" != "null" ] && [ -n "$PIX_ID" ]; then
  echo ""
  echo "✅ PIX criado com sucesso! ID: $PIX_ID"
  echo ""
  echo "📋 Aguardando processamento do webhook (2-10 segundos)..."
  echo "💡 Execute 'php artisan queue:work' para processar os webhooks"
else
  echo "❌ Erro ao criar PIX"
fi
```

---

## 🚀 Exemplo rápido (uma linha)

```bash
# Login e criar PIX em uma linha
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@example.com","password":"senha123456"}' \
  | jq -r '.access_token') && \
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"amount":100.00}' | jq '.'
```

---

## ⚠️ Observações Importantes

1. **Token de Autenticação**: Todas as rotas de PIX e Saque requerem autenticação via Bearer token
2. **Valor mínimo**: O valor do PIX deve ser maior que 0.01
3. **CPF**: Se informado, deve conter exatamente 11 dígitos numéricos
4. **Webhooks**: Após criar um PIX, um webhook será simulado automaticamente após 2-10 segundos (aleatório)
5. **Queue Worker**: Para processar os webhooks, execute: `php artisan queue:work`

---

## 🔧 Troubleshooting

### Erro 401 (Unauthenticated)
- Verifique se o token está correto
- Verifique se o header `Authorization: Bearer {token}` está presente
- Faça login novamente para obter um novo token

### Erro 422 (Validação)
- Verifique se todos os campos obrigatórios estão presentes
- Verifique se o formato dos dados está correto (amount deve ser numérico, CPF deve ter 11 dígitos)

### Erro 500 (Server Error)
- Verifique se o usuário tem uma subadquirente configurada
- Verifique os logs em `storage/logs/laravel.log`

