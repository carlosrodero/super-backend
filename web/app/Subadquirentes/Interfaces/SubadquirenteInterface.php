<?php

namespace App\Subadquirentes\Interfaces;

interface SubadquirenteInterface
{
    /**
     * Cria uma cobrança PIX na subadquirente
     *
     * @param array $data Dados para criar o PIX
     * @return array Resposta da API da subadquirente
     */
    public function createPix(array $data): array;

    /**
     * Cria um saque na subadquirente
     *
     * @param array $data Dados para criar o saque
     * @return array Resposta da API da subadquirente
     */
    public function createWithdraw(array $data): array;

    /**
     * Processa um webhook recebido da subadquirente
     *
     * @param array $payload Payload do webhook
     * @param string $type Tipo do webhook ('pix' ou 'withdraw')
     * @return void
     */
    public function processWebhook(array $payload, string $type): void;
}

