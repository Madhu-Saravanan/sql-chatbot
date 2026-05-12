<?php

function callClaude(string $systemPrompt, array $messages): array {
    $body = json_encode([
        'model'      => getenv('ANTHROPIC_MODEL') ?: 'claude-sonnet-4-20250514',
        'max_tokens' => (int)(getenv('ANTHROPIC_MAX_TOKENS') ?: 2048),
        'system'     => $systemPrompt,
        'messages'   => $messages,
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . getenv('ANTHROPIC_API_KEY'),
            'anthropic-version: 2023-06-01',
        ],
    ]);

    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('Claude API cURL error: ' . $curlError);
    }

    $data = json_decode($response, true);
    if (isset($data['error'])) {
        throw new RuntimeException('Claude API error: ' . $data['error']['message']);
    }

    return [
        'content'       => $data['content'][0]['text'],
        'input_tokens'  => $data['usage']['input_tokens'],
        'output_tokens' => $data['usage']['output_tokens'],
    ];
}
