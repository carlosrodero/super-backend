# Super Backend

Sistema de integração com subadquirentes de pagamento desenvolvido em Laravel 10, permitindo que diferentes usuários utilizem diferentes subadquirentes para processar PIX e saques.

## 📋 Sobre o Projeto

Este projeto implementa um sistema de **multiadquirência** onde:
- Cada usuário pode estar vinculado a uma subadquirente diferente
- O sistema suporta múltiplas subadquirentes (atualmente SubadqA e SubadqB)
- Arquitetura extensível para adicionar novas subadquirentes facilmente
- Processamento assíncrono de webhooks
- Tratamento robusto de erros com exceptions customizadas

### Funcionalidades

- ✅ **Geração de PIX**: Criação de cobranças PIX através das subadquirentes
- ✅ **Solicitação de Saques**: Processamento de saques bancários
- ✅ **Webhooks Simulados**: Simulação automática de confirmações de pagamento e saque
- ✅ **Processamento Assíncrono**: Jobs em background para processar webhooks
- ✅ **Tratamento de Erros**: Sistema de exceptions customizadas
- ✅ **Logs Detalhados**: Rastreamento de operações

---

## 🏗️ Arquitetura

### Estrutura de Diretórios

```
web/app/
├── Models/
│   ├── User.php
│   ├── Subadquirente.php
│   ├── Pix.php
│   └── Withdraw.php
├── Subadquirentes/
│   ├── Interfaces/
│   │   └── SubadquirenteInterface.php
│   ├── AbstractSubadquirente.php
│   ├── Requests/
│   │   └── AbstractBaseRequest.php
│   ├── SubadqA/
│   │   ├── SubadqA.php
│   │   ├── BaseRequest.php
│   │   ├── Requests/
│   │   │   ├── CreatePixRequest.php
│   │   │   └── CreateWithdrawRequest.php
│   │   └── Webhook/
│   │       └── SubadqAWebhookHandler.php
│   └── SubadqB/
│       ├── SubadqB.php
│       ├── BaseRequest.php
│       ├── Requests/
│       │   ├── CreatePixRequest.php
│       │   └── CreateWithdrawRequest.php
│       └── Webhook/
│           └── SubadqBWebhookHandler.php
├── Services/
│   ├── PixService.php
│   ├── WithdrawService.php
│   └── SubadquirenteServiceFactory.php
├── Repositories/
│   ├── PixRepository.php
│   └── WithdrawRepository.php
├── Jobs/
│   ├── ProcessPixWebhook.php
│   ├── ProcessWithdrawWebhook.php
│   ├── SimulatePixWebhook.php
│   └── SimulateWithdrawWebhook.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── PixController.php
│   │       └── WithdrawController.php
│   └── Requests/
│       ├── CreatePixRequest.php
│       └── CreateWithdrawRequest.php
└── Exceptions/
    ├── Handler.php
    ├── SubadquirenteNotFoundException.php
    ├── PixCreationException.php
    ├── WithdrawCreationException.php
    └── WebhookProcessingException.php
```

---

## 🚀 Instalação e Configuração

### Requisitos

- Docker
- Docker Compose

### Passo a Passo

1. **Clone o repositório**:
```bash
git clone git@github.com:carlosrodero/super-backend.git
cd super-backend
```

2. **Instale o Laravel** (se ainda não foi feito):
```bash
./install.sh
```

Ou manualmente:
```bash
docker compose run --rm app composer create-project laravel/laravel:^10.0 . --prefer-dist
```

3. **Configure o arquivo `.env`**:
```bash
cp web/.env.example web/.env
```

Edite `web/.env` e configure:
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=super_backend
DB_USERNAME=super_backend
DB_PASSWORD=root

QUEUE_CONNECTION=database
```

4. **Gere a chave da aplicação**:
```bash
docker compose run --rm app php artisan key:generate
```

5. **Execute as migrações e seeders**:
```bash
docker compose run --rm app php artisan migrate --seed
```

Isso criará:
- Tabelas do banco de dados
- Subadquirentes (SubadqA e SubadqB)
- Usuários de exemplo (Usuário A, Usuário B e Usuário C)

6. **Inicie os containers**:
```bash
docker compose up -d
```

7. **Inicie o worker de filas** (em um terminal separado):
```bash
docker compose run --rm app php artisan queue:work
```

---

## 📖 Uso

### Acessar a aplicação

- **API**: http://localhost:8000/api
- **Documentação de Exemplos**: Veja [EXEMPLOS_CURL.md](./EXEMPLOS_CURL.md)

### Comandos Úteis

#### Executar comandos Artisan:
```bash
docker compose run --rm app php artisan [comando]
```

#### Executar comandos Composer:
```bash
docker compose run --rm app composer [comando]
```

#### Acessar o container:
```bash
docker compose exec app bash
```

#### Corrigir permissões (se necessário):
```bash
chmod +x fix-permissions.sh
./fix-permissions.sh
```

#### Limpar cache:
```bash
docker compose run --rm app php artisan cache:clear
docker compose run --rm app php artisan config:clear
```

---

## 🔄 Fluxos de Operação

### Fluxo de Geração de PIX (Recebimento)

1. Cliente faz `POST /api/pix` (autenticado)
2. `PixController::store()` valida a requisição
3. `PixService::createPix()` busca a subadquirente do usuário
4. `SubadquirenteServiceFactory` retorna a instância da subadquirente
5. Subadquirente cria `CreatePixRequest` específico
6. `CreatePixRequest` monta payload e faz chamada HTTP para API da subadquirente
7. `PixRepository` salva cobrança PIX no banco com status `PENDING`
8. Job `SimulatePixWebhook` é disparado (delay aleatório de 2-10 segundos)
9. Job gera payload específico da subadquirente e chama `Subadquirente::processWebhook()`
10. `WebhookHandler` normaliza os dados e dispara `ProcessPixWebhook`
11. `ProcessPixWebhook` chama `PixService::processWebhook()` que atualiza o status do PIX

### Fluxo de Solicitação de Saque (Retirada)

1. Cliente faz `POST /api/withdraw` (autenticado)
2. `WithdrawController::store()` valida a requisição
3. `WithdrawService::createWithdraw()` busca a subadquirente do usuário
4. `SubadquirenteServiceFactory` retorna a instância da subadquirente
5. Subadquirente cria `CreateWithdrawRequest` específico
6. `CreateWithdrawRequest` monta payload e faz chamada HTTP para API da subadquirente
7. `WithdrawRepository` salva solicitação de saque no banco com status `PENDING`
8. Job `SimulateWithdrawWebhook` é disparado (delay aleatório de 2-10 segundos)
9. Job gera payload específico da subadquirente e chama `Subadquirente::processWebhook()`
10. `WebhookHandler` normaliza os dados e dispara `ProcessWithdrawWebhook`
11. `ProcessWithdrawWebhook` chama `WithdrawService::processWebhook()` que atualiza o status do saque

---

## 🔌 API Endpoints

### Autenticação

- `POST /api/register` - Registrar novo usuário
- `POST /api/login` - Fazer login e obter token
- `POST /api/logout` - Fazer logout
- `GET /api/user` - Obter dados do usuário autenticado

### PIX

- `GET /api/pix` - Listar todos os PIX do usuário
- `POST /api/pix` - Criar nova cobrança PIX
- `GET /api/pix/{id}` - Obter detalhes de um PIX específico

### Saques

- `GET /api/withdraw` - Listar todos os saques do usuário
- `POST /api/withdraw` - Criar nova solicitação de saque
- `GET /api/withdraw/{id}` - Obter detalhes de um saque específico

**📝 Para exemplos detalhados de uso, consulte [EXEMPLOS_CURL.md](./EXEMPLOS_CURL.md)**

---

## 🛡️ Tratamento de Erros

O sistema possui um tratamento de erros com exceptions customizadas:

### Exceptions Customizadas

- **`SubadquirenteNotFoundException`** (404/401/403): Subadquirente não encontrada ou inativa
- **`PixCreationException`** (422/500/502): Erro ao criar cobrança PIX
- **`WithdrawCreationException`** (422/500/502): Erro ao criar solicitação de saque
- **`WebhookProcessingException`** (500): Erro ao processar webhook

### Formato de Resposta de Erro

```json
{
  "success": false,
  "message": "Mensagem de erro descritiva",
  "error_code": "CODIGO_DO_ERRO",
  "context": {
    "campo_adicional": "valor"
  }
}
```

### Logs

Todos os erros são registrados em `storage/logs/laravel.log` com contexto detalhado para facilitar o debug.

---

## 📊 Banco de Dados

### Tabelas Principais

- **`users`**: Usuários do sistema (com `subadquirente_id`)
- **`subadquirentes`**: Subadquirentes cadastradas (SubadqA, SubadqB)
- **`pix`**: Cobranças PIX criadas
- **`withdraws`**: Solicitações de saque
- **`jobs`**: Fila de jobs para processamento assíncrono

### Status

**PIX:**
- `PENDING`: Cobrança criada, aguardando pagamento
- `PAID`: Pagamento recebido e confirmado
- `FAILED`: Falha no processamento

**Saques:**
- `PENDING`: Saque solicitado, aguardando processamento
- `COMPLETED`: Saque concluído com sucesso
- `FAILED`: Falha no processamento

---

## 🔧 Extensibilidade

### Adicionar Nova Subadquirente

Para adicionar uma nova subadquirente (ex: SubadqC):

1. **Criar estrutura de diretórios**:
```
web/app/Subadquirentes/SubadqC/
├── SubadqC.php
├── BaseRequest.php
├── Requests/
│   ├── CreatePixRequest.php
│   └── CreateWithdrawRequest.php
└── Webhook/
    └── SubadqCWebhookHandler.php
```

2. **Implementar a interface**:
- `SubadqC` deve implementar `SubadquirenteInterface`
- Estender `AbstractSubadquirente` para código comum

3. **Criar Requests específicos**:
- Estender `BaseRequest` da subadquirente
- Implementar métodos `getResource()`, `build()`, `getMockResponseName()`

4. **Criar WebhookHandler**:
- Implementar normalização de payloads específicos
- Disparar jobs de processamento

5. **Adicionar no banco de dados**:
```sql
INSERT INTO subadquirentes (name, base_url, config, active) 
VALUES ('SubadqC', 'https://api.subadqc.com', '{}', true);
```

O `SubadquirenteServiceFactory` carregará automaticamente a nova subadquirente!

---

## 🧪 Testes

### Testar Criação de PIX

```bash
# 1. Fazer login
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@example.com","password":"senha123456"}' \
  | jq -r '.access_token')

# 2. Criar PIX
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"amount": 100.00}'
```

### Verificar Logs

```bash
docker compose exec app tail -f storage/logs/laravel.log
```

### Verificar Jobs na Fila

```bash
docker compose run --rm app php artisan queue:work --verbose
```

---

## 📁 Estrutura do Projeto

```
super-backend/
├── web/                          # Código Laravel
│   ├── app/                      # Código da aplicação
│   ├── database/                 # Migrations e Seeders
│   ├── routes/                   # Rotas da API
│   ├── storage/                  # Logs e cache
│   └── ...
├── docker/                       # Configurações Docker
│   ├── mysql/data/              # Dados do MySQL
│   ├── nginx/                   # Configuração Nginx
│   └── php/                     # Configuração PHP
├── docker-compose.yml           # Orquestração Docker
├── Dockerfile                   # Imagem Docker
├── README.md                    # Este arquivo
├── EXEMPLOS_CURL.md            # Exemplos de uso da API
└── INSTRUCOES.md               # Instruções originais do desafio
```

---

## 🔍 Monitoramento

### Logs

Os logs são salvos em `web/storage/logs/laravel.log` e incluem:
- Requisições para subadquirentes
- Respostas das APIs
- Processamento de webhooks
- Erros e exceptions
- Operações de criação de PIX e saques

### Queue Monitor

Para monitorar jobs em processamento:
```bash
docker compose run --rm app php artisan queue:work --verbose
```

---

## 🐛 Troubleshooting

### Erro de Permissão

Se encontrar erros de permissão:
```bash
chmod +x fix-permissions.sh
./fix-permissions.sh
```

### Erro de Conexão com Banco

Verifique se o container MySQL está rodando:
```bash
docker compose ps
```

### Jobs Não Processando

Certifique-se de que o worker está rodando:
```bash
docker compose run --rm app php artisan queue:work
```

### Limpar Cache

```bash
docker compose run --rm app php artisan cache:clear
docker compose run --rm app php artisan config:clear
docker compose run --rm app php artisan route:clear
```

---

## 📚 Documentação Adicional

- **[EXEMPLOS_CURL.md](./EXEMPLOS_CURL.md)**: Exemplos práticos de uso da API
- **[INSTRUCOES.md](./INSTRUCOES.md)**: Instruções originais do desafio

---

## 🚀 Serviços Docker

- **app**: Container PHP 8.1 com FPM
- **nginx**: Servidor web Nginx
- **db**: Banco de dados MySQL 8.0

### Portas

- **8000**: Nginx (aplicação web)
- **3306**: MySQL

---

## 📝 Notas Importantes

- **Armazenamento Local**: Todo o código fica em `./web/` e os dados do MySQL em `./docker/mysql/data/`
- **Queue Driver**: Utiliza `database` como driver de filas, para o ambiente do teste.
- **Webhooks Simulados**: Os webhooks são simulados automaticamente após criação de PIX/saque com delay aleatório (2-10 segundos)
- **Extensibilidade**: A arquitetura permite adicionar novas subadquirentes sem modificar código existente

---

## 👨‍💻 Desenvolvido com

- Laravel 10
- PHP 8.1
- MySQL 8.0
- Docker & Docker Compose
- Laravel Sanctum (Autenticação)
- Laravel Queue (Processamento Assíncrono)

---

## 📄 Licença

Este projeto foi desenvolvido como parte de um desafio técnico.
