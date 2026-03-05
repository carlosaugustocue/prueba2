<?php

namespace App\Modules\Integrations\WhatsApp;

use App\Modules\Core\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements NotificationChannelInterface
{
    protected string $provider;

    // Meta Cloud API
    protected string $apiUrl;
    protected string $phoneNumberId;
    protected string $accessToken;
    protected string $defaultLanguage;

    // Twilio WhatsApp API
    protected string $twilioAccountSid;
    protected string $twilioAuthToken;
    protected string $twilioFrom;

    public function __construct()
    {
        $this->provider = strtolower((string) (config('services.whatsapp.provider') ?? 'meta'));

        // config() puede retornar null si la key existe pero el env está vacío.
        // Forzamos a string para evitar TypeError en propiedades tipadas.
        $this->apiUrl = (string) (config('services.whatsapp.api_url') ?? 'https://graph.facebook.com/v18.0');
        $this->phoneNumberId = (string) (config('services.whatsapp.phone_number_id') ?? '');
        $this->accessToken = (string) (config('services.whatsapp.access_token') ?? '');
        $this->defaultLanguage = (string) (config('services.whatsapp.language') ?? 'es_CO');

        $this->twilioAccountSid = (string) (config('services.whatsapp.twilio.account_sid') ?? '');
        $this->twilioAuthToken = (string) (config('services.whatsapp.twilio.auth_token') ?? '');
        $this->twilioFrom = (string) (config('services.whatsapp.twilio.from') ?? '');
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        if (! $this->isAvailable()) {
            Log::warning('WhatsApp service not configured', [
                'provider' => $this->provider,
                'recipient' => $recipient,
            ]);

            if (config('app.env') === 'local') {
                return ['success' => true, 'simulated' => true, 'message_id' => 'simulated_' . uniqid()];
            }

            throw new \Exception('WhatsApp service is not configured');
        }

        if ($this->provider === 'twilio') {
            return $this->sendViaTwilio($recipient, $message, $options);
        }

        return $this->sendViaMeta($recipient, $message, $options);
    }

    protected function sendViaMeta(string $recipient, string $message, array $options = []): array
    {
        $recipient = $this->normalizePhoneNumber($recipient);
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

    protected function sendViaTwilio(string $recipient, string $message, array $options = []): array
    {
        $to = $this->normalizeTwilioRecipient($recipient);
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioAccountSid}/Messages.json";

        $payload = [
            'To' => $to,
            'From' => $this->twilioFrom,
        ];

        $type = $options['type'] ?? 'text';
        if ($type === 'template') {
            // Para Twilio usamos Content Template SID (HX...).
            // Reutilizamos template_name para no romper Jobs existentes.
            $contentSid = (string) ($options['content_sid'] ?? $options['template_name'] ?? '');
            if ($contentSid === '') {
                throw new \InvalidArgumentException('content_sid (o template_name con HX...) es requerido para mensajes template en Twilio');
            }

            $parameters = $options['parameters'] ?? [];
            $contentVariables = [];
            foreach (array_values((array) $parameters) as $idx => $value) {
                $contentVariables[(string) ($idx + 1)] = (string) $value;
            }

            $payload['ContentSid'] = $contentSid;
            if (! empty($contentVariables)) {
                $payload['ContentVariables'] = json_encode($contentVariables, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $payload['Body'] = $message;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->twilioAccountSid, $this->twilioAuthToken)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Twilio WhatsApp message sent', [
                    'recipient' => $to,
                    'message_id' => $data['sid'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['sid'] ?? null,
                    'response' => $data,
                ];
            }

            $error = $response->json()['message'] ?? 'Unknown Twilio API error';
            throw new \Exception($error);
        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp send failed', ['recipient' => $to, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function isAvailable(): bool
    {
        if ($this->provider === 'twilio') {
            return ! empty($this->twilioAccountSid)
                && ! empty($this->twilioAuthToken)
                && ! empty($this->twilioFrom);
        }

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

    protected function normalizeTwilioRecipient(string $phone): string
    {
        $normalized = $this->normalizePhoneNumber($phone);
        return 'whatsapp:+' . $normalized;
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
