<?php

namespace App\Modules\Core\Contracts;

interface NotificationChannelInterface
{
    /**
     * Enviar mensaje a un destinatario
     */
    public function send(string $recipient, string $message, array $options = []): array;

    /**
     * Verificar si el canal está disponible
     */
    public function isAvailable(): bool;

    /**
     * Obtener el nombre del canal
     */
    public function getChannelName(): string;
}
