<?php

function elevenlabs_tts(string $text, string $voiceId = 'pNInz6obpgDQGcFmaJgB'): array
{
    global $CONFIG;
    $apiKey = $CONFIG['elevenlabs']['api_key'] ?? '';

    if (!$apiKey) {
        return ['ok' => false, 'error' => 'ElevenLabs API Key missing'];
    }

    // Default voice ID (Adam) if none provided or invalid
    if (!$voiceId)
        $voiceId = 'pNInz6obpgDQGcFmaJgB';

    $url = "https://api.elevenlabs.io/v1/text-to-speech/$voiceId";

    $data = [
        "text" => $text,
        "model_id" => "eleven_multilingual_v2", // Best for multiple languages
        "voice_settings" => [
            "stability" => 0.5,
            "similarity_boost" => 0.75
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "xi-api-key: $apiKey",
        "Content-Type: application/json",
        "Accept: audio/mpeg"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['ok' => false, 'error' => "ElevenLabs Error ($httpCode): " . substr($result, 0, 100)];
    }

    return ['ok' => true, 'data' => $result, 'mime' => 'audio/mpeg'];
}
