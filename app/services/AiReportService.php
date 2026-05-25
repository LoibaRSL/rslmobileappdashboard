<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiReportService
{
    public function generate(string $prompt, array $context): array
    {
        if (! config('services.ai_reports.enabled') || ! config('services.ai_reports.api_key')) {
            return [
                'success' => false,
                'message' => 'AI report generation is not configured. Set AI_REPORTS_ENABLED=true and AI_REPORTS_API_KEY in .env.',
                'content' => null,
            ];
        }

        try {
            $safeContext = $this->redactContext($context);

            $response = Http::withToken(config('services.ai_reports.api_key'))
                ->acceptJson()
                ->timeout((int) config('services.ai_reports.timeout', 45))
                ->post(rtrim(config('services.ai_reports.base_url'), '/') . '/chat/completions', [
                    'model' => config('services.ai_reports.model'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a reporting analyst for Revenue Services Lesotho. Produce concise, factual management reports from the provided JSON only. Mention trends, risks, anomalies, and recommended actions. Do not invent figures.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt . "\n\nReport context JSON:\n" . json_encode($safeContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                    'temperature' => 0.2,
                ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => 'AI provider returned HTTP ' . $response->status(),
                    'content' => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'AI report generated successfully.',
                'content' => data_get($response->json(), 'choices.0.message.content'),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'content' => null,
            ];
        }
    }

    public function redactContext(array $context): array
    {
        return $this->redactValue($context);
    }

    public function redactText(?string $value): string
    {
        $value = (string) ($value ?? '');
        $value = preg_replace('/sk-[A-Za-z0-9_\-]+/', '[redacted-secret]', $value);
        $value = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[redacted-email]', $value);
        $value = preg_replace('/\b(?:\+?\d[\d\s\-().]{5,}\d)\b/', '[redacted-number]', $value);

        return trim($value);
    }

    private function redactValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => $this->redactValue($item))
                ->all();
        }

        if (is_string($value)) {
            return $this->redactText($value);
        }

        return $value;
    }
}
