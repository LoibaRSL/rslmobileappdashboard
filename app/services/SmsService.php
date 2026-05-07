<?php
// app/Services/SmsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $apiUrl;
    protected string $username;
    protected string $password;
    protected string $fromNumber;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiUrl = config('services.sms.api_url', 'https://api.etl.co.ls/restapi/sms/1/text/single');
        $this->username = config('services.sms.username', 'LRALesotho');
        $this->password = config('services.sms.password', 'RSLAdmin@2024');
        $this->fromNumber = config('services.sms.from_number', '22235000');
        $this->enabled = config('services.sms.enabled', true);
    }

    public function sendTinNotification(string $phoneNumber, string $tin, string $recipientName): array
    {
        if (!$this->enabled) {
            Log::info('SMS service disabled, skipping TIN notification', [
                'phone' => $phoneNumber,
                'tin' => $tin
            ]);
            return ['success' => false, 'message' => 'SMS service disabled'];
        }

        try {
            $message = $this->buildTinMessage($tin, $recipientName);
            return $this->sendViaEtlApi($phoneNumber, $message);

        } catch (\Exception $e) {
            Log::error('Failed to send TIN SMS', [
                'phone' => $phoneNumber,
                'tin' => $tin,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


// In SmsService class, add this method for generic SMS sending
public function sendSMS(string $phoneNumber, string $message): array
{
    return $this->sendViaEtlApi($phoneNumber, $message);
}
    public function sendAmendmentNotification(string $phoneNumber, string $recipientName): array
    {
        if (!$this->enabled) {
            Log::info('SMS service disabled, skipping amendment notification', [
                'phone' => $phoneNumber
            ]);
            return ['success' => false, 'message' => 'SMS service disabled'];
        }

        try {
            $message = $this->buildAmendmentMessage($recipientName);
            return $this->sendViaEtlApi($phoneNumber, $message);

        } catch (\Exception $e) {
            Log::error('Failed to send amendment SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function sendRejectionNotification(string $phoneNumber, string $recipientName, string $reason): array
    {
        if (!$this->enabled) {
            Log::info('SMS service disabled, skipping rejection notification', [
                'phone' => $phoneNumber,
                'reason' => $reason
            ]);
            return ['success' => false, 'message' => 'SMS service disabled'];
        }

        try {
            $message = $this->buildRejectionMessage($recipientName, $reason);
            return $this->sendViaEtlApi($phoneNumber, $message);

        } catch (\Exception $e) {
            Log::error('Failed to send rejection SMS', [
                'phone' => $phoneNumber,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function buildTinMessage(string $tin, string $recipientName): string
    {
        // Customize this message as needed
        return "Dear Client, your TIN registration has been approved. Your TIN is: {$tin}. Please keep it for future use. Thank for using RSL App";
    }

    protected function buildAmendmentMessage(string $recipientName): string
    {
        return "Dear Client, your registration amendment has been approved. Thank for using RSL App.";
    }

    protected function buildRejectionMessage(string $recipientName, string $reason): string
    {
        // Truncate reason if too long for SMS (SMS typically 160 characters)
        $truncatedReason = strlen($reason) > 100 
            ? substr($reason, 0, 97) . '...' 
            : $reason;

        return "Dear Client, your TIN registration has been rejected. Reason: {$truncatedReason}. Thank for using RSL App.";
    }



// Add this method to your existing SmsService class
public function sendBusinessRegistrationNotification(string $phoneNumber, string $businessName, string $tin): array
{
    if (!$this->enabled) {
        Log::info('SMS service disabled, skipping business registration notification', [
            'phone' => $phoneNumber,
            'business' => $businessName
        ]);
        return ['success' => false, 'message' => 'SMS service disabled'];
    }

    try {
        $message = $this->buildBusinessRegistrationMessage($businessName, $tin);
        return $this->sendViaEtlApi($phoneNumber, $message);

    } catch (\Exception $e) {
        Log::error('Failed to send business registration SMS', [
            'phone' => $phoneNumber,
            'business' => $businessName,
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Add this message builder method
protected function buildBusinessRegistrationMessage(string $businessName, string $tin): string
{
    return "Dear Client, your TIN registration has been approved. Your TIN is: {$tin}. Please keep it for future use. Thank for using RSL App";
}

    

    protected function sendViaEtlApi(string $phoneNumber, string $message): array
    {
        $auth = base64_encode("{$this->username}:{$this->password}");
        $to = $phoneNumber;

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'authorization' => "Basic {$auth}",
            'content-type' => 'application/json',
        ])
        ->withoutVerifying() // Disable SSL verification as in your original code
        ->timeout(30)
        ->post($this->apiUrl, [
            'from' => $this->fromNumber,
            'to' => $to,
            'text' => $message
        ]);

        Log::info('SMS API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'phone' => $phoneNumber
        ]);

        if ($response->successful()) {
            Log::info('SMS sent successfully', [
                'phone' => $phoneNumber,
                'message' => $message,
                'response' => $response->json()
            ]);
            return ['success' => true, 'message' => 'SMS sent successfully'];
        }

        throw new \Exception('SMS API error: ' . $response->body());
    }
}