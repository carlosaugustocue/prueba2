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

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->accessToken = config('services.whatsapp.access_token', '');
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

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $message],
        ];

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
}
