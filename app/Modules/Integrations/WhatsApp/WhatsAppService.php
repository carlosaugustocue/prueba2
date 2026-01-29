<?php

namespace App\Modules\Integrations\WhatsApp;

use App\Modules\Core\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements NotificationChannelInterface
{
    protected string $apiUrl;
    protected string $phoneNumberId;
    protected string $accessToken;
    protected string $defaultLanguage;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->accessToken = config('services.whatsapp.access_token', '');
        $this->defaultLanguage = config('services.whatsapp.language', 'es_CO');
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        $recipient = $this->normalizePhoneNumber($recipient);

        if (! $this->isAvailable()) {
            Log::warning('WhatsApp service not configured', ['recipient' => $recipient]);

            if (config('app.env') === 'local') {
                return ['success' => true, 'simulated' => true, 'message_id' => 'simulated_' . uniqid()];
            }

            throw new \Exception('WhatsApp service is not configured');
        }

        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $payload = $this->buildPayload($recipient, $message, $options);

        try {
            $response = Http::withToken($this->accessToken)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp message sent', ['recipient' => $recipient, 'message_id' => $data['messages'][0]['id'] ?? null]);
                return ['success' => true, 'message_id' => $data['messages'][0]['id'] ?? null, 'response' => $data];
            }

            throw new \Exception($response->json()['error']['message'] ?? 'Unknown WhatsApp API error');

        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', ['recipient' => $recipient, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function isAvailable(): bool
    {
        return ! empty($this->phoneNumberId) && ! empty($this->accessToken);
    }

    public function getChannelName(): string
    {
        return 'whatsapp';
    }

    protected function normalizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        if (! str_starts_with($phone, '57') && strlen($phone) === 10) {
            $phone = '57' . $phone;
        }
        return $phone;
    }

    /**
     * Construir payload para WhatsApp Cloud API.
     *
     * Soporta:
     * - Texto libre: default
     * - Template message: options['type']='template' + template_name + parameters[]
     */
    protected function buildPayload(string $recipient, string $message, array $options = []): array
    {
        $type = $options['type'] ?? 'text';

        if ($type === 'template') {
            $templateName = $options['template_name'] ?? $options['template'] ?? null;
            $language = $options['language'] ?? $options['language_code'] ?? $this->defaultLanguage;
            $parameters = $options['parameters'] ?? $options['template_parameters'] ?? [];

            if (! $templateName) {
                throw new \InvalidArgumentException('template_name is required for template WhatsApp messages');
            }

            $components = [];
            if (! empty($parameters)) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => array_map(
                        fn ($value) => ['type' => 'text', 'text' => (string) $value],
                        $parameters
                    ),
                ];
            }

            return [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $language],
                    ...(! empty($components) ? ['components' => $components] : []),
                ],
            ];
        }

        // Texto libre (solo funciona en ventana de 24h si el usuario inició conversación)
        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ];
    }
}
