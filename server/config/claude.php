<?php

function callClaude(string $systemPrompt, array $messages): array {
    $msgs = array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $messages
    );

    $body = json_encode([
        'model'      => getenv('OPENAI_MODEL') ?: 'gpt-4o',
        'max_tokens' => (int)(getenv('OPENAI_MAX_TOKENS') ?: 2048),
        'messages'   => $msgs,
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('OPENAI_API_KEY'),
        ],
    ]);

    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('OpenAI API cURL error: ' . $curlError);
    }

    $data = json_decode($response, true);
    if (isset($data['error'])) {
        throw new RuntimeException('OpenAI API error: ' . $data['error']['message']);
    }

    return [
        'content'       => $data['choices'][0]['message']['content'],
        'input_tokens'  => $data['usage']['prompt_tokens'],
        'output_tokens' => $data['usage']['completion_tokens'],
    ];
}
