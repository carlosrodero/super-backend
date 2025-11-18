# Super Backend

Projeto Laravel 10 com Docker - Ambiente de desenvolvimento configurado.

## Requisitos

- Docker
- Docker Compose

## Instalação

1. Clone ou crie o projeto neste diretório

2. Instale o Laravel 10 usando o script de instalação:
```bash
./install.sh
```

Ou manualmente:
```bash
docker compose run --rm app composer create-project laravel/laravel:^10.0 . --prefer-dist
```

3. Configure o arquivo `.env` (se não foi feito pelo script):
```bash
cp web/.env.example web/.env
```

Edite o arquivo `web/.env` e configure as seguintes variáveis de banco de dados:
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=super_backend
DB_USERNAME=super_backend
DB_PASSWORD=root
```

4. Gere a chave da aplicação:
```bash
docker compose run --rm app php artisan key:generate
```

5. Execute as migrações (opcional):
```bash
docker compose run --rm app php artisan migrate
```

## Uso

### Iniciar os containers:
```bash
docker compose up -d
```

### Parar os containers:
```bash
docker compose down
```

### Acessar a aplicação:
Abra seu navegador em: http://localhost:8000

### Executar comandos Artisan:
```bash
docker compose run --rm app php artisan [comando]
```

### Executar comandos Composer:
```bash
docker compose run --rm app composer [comando]
```

### Acessar o container da aplicação:
```bash
docker compose exec app bash
```

### Corrigir permissões (se necessário):
Se você encontrar erros de permissão com os logs ou cache:
```bash
./fix-permissions.sh
```

## Estrutura

```
super-backend/
├── web/                    ← Código Laravel (aqui fica toda a aplicação)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   └── ...
├── docker/
│   ├── mysql/data/         ← Dados do banco de dados MySQL
│   ├── nginx/
│   └── php/
├── docker-compose.yml
├── Dockerfile
└── install.sh
```

## Armazenamento Local

✅ **Código Laravel**: Todo o código fica em `./web/` (pasta web do projeto)  
✅ **Banco de Dados**: Os dados do MySQL são armazenados em `./docker/mysql/data/`

Isso significa que:
- Você pode editar o código diretamente no seu editor
- Os dados do banco persistem mesmo após parar os containers
- Tudo fica acessível localmente, sem depender de volumes nomeados do Docker

## Serviços

- **app**: Container PHP 8.1 com FPM
- **nginx**: Servidor web Nginx
- **db**: Banco de dados MySQL 8.0

## Portas

- **8000**: Nginx (aplicação web)
- **3306**: MySQL

