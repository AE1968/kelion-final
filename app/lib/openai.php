<?php
function openai_key(): string
{
  global $CONFIG;
  return (string) ($CONFIG['openai']['api_key'] ?? '');
}
function openai_post_json(string $url, array $payload): array
{
  $key = openai_key();
  if ($key === '')
    return ['ok' => false, 'error' => 'Missing OpenAI API key in config.php (openai.api_key).'];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $key,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 60,
  ]);
  $out = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);

  if ($out === false)
    return ['ok' => false, 'error' => "cURL error: $err"];
  $json = json_decode($out, true);
  if ($code < 200 || $code >= 300) {
    $msg = $json['error']['message'] ?? ('HTTP ' . $code);
    return ['ok' => false, 'error' => $msg, 'raw' => $json];
  }
  return ['ok' => true, 'data' => $json];
}

function kelion_safety_block(string $text): ?string
{
  // Non-aggression / safety gate (lightweight). Expand as needed.
  $patterns = [
    // Explicit sexual content (adult porn)
    '/\b(porn|pornography|xxx|sex video|nude pics?|send nudes?)\b/i',
    '/\b(explicit sex|hardcore|blowjob|anal sex|cumshot)\b/i',
    '/\b(make|build|create)\b.*\b(bomb|explosive|molotov)\b/i',
    '/\b(kill|murder|assassinate|stab|shoot)\b/i',
    '/\b(hate|genocide|ethnic cleansing)\b/i',
    '/\bself[- ]?harm|suicide\b/i',
    '/\b(child sexual|csam)\b/i',
  ];
  foreach ($patterns as $p) {
    if (preg_match($p, $text)) {
      return "I can’t help with violence, self-harm, hate, or illegal wrongdoing. If you’re in danger or thinking about harming yourself, please contact local emergency services or a crisis hotline immediately.";
    }
  }
  return null;
}

function openai_answer(string $userText, string $lang = 'AUTO'): array
{
  $block = kelion_safety_block($userText);
  if ($block)
    return ['ok' => true, 'text' => $block];

  global $CONFIG;
  $model = $CONFIG['openai']['chat_model'] ?? 'gpt-4.1-mini';
  $system = "You are KELION, a futuristic hologram guardian assistant. UI is English. Reply in the user's language. Safety policy: refuse any request involving violence, weapons, hate/harassment, self-harm, sexual content involving minors, illegal wrongdoing, or instructions that could harm people. If asked, respond calmly and offer safe alternatives.";
  $payload = [
    'model' => $model,
    'input' => [
      ['role' => 'system', 'content' => [['type' => 'text', 'text' => $system]]],
      ['role' => 'user', 'content' => [['type' => 'text', 'text' => $userText]]],
    ],
  ];
  $res = openai_post_json('https://api.openai.com/v1/responses', $payload);
  if (!$res['ok'])
    return $res;

  $data = $res['data'];
  $text = '';
  if (!empty($data['output'])) {
    foreach ($data['output'] as $out) {
      if (($out['type'] ?? '') === 'message' && !empty($out['content'])) {
        foreach ($out['content'] as $c) {
          if (($c['type'] ?? '') === 'output_text')
            $text .= $c['text'] ?? '';
          if (($c['type'] ?? '') === 'text')
            $text .= $c['text'] ?? '';
        }
      }
    }
  }
  $text = trim($text);
  if ($text === '')
    $text = '[No output]';
  return ['ok' => true, 'text' => $text];
}

function openai_tts_mp3(string $text, ?string $voice = null, ?string $model = null): array
{
  global $CONFIG;
  $key = openai_key();
  if ($key === '')
    return ['ok' => false, 'error' => 'Missing OpenAI API key in config.php (openai.api_key).'];

  $voice = $voice ?: ($CONFIG['openai']['voice_default'] ?? 'cedar');
  $model = $model ?: ($CONFIG['openai']['tts_model'] ?? 'gpt-4o-mini-tts');

  $payload = ['model' => $model, 'voice' => $voice, 'format' => 'mp3', 'input' => $text];

  $ch = curl_init('https://api.openai.com/v1/audio/speech');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $key,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 60,
  ]);
  $bin = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);

  if ($bin === false)
    return ['ok' => false, 'error' => "cURL error: $err"];
  if ($code < 200 || $code >= 300) {
    $j = json_decode($bin, true);
    $msg = $j['error']['message'] ?? ('HTTP ' . $code);
    return ['ok' => false, 'error' => $msg];
  }
  return ['ok' => true, 'bin' => $bin];
}

/**
 * Speech-to-Text using OpenAI Audio API
 * @param string $audioData - Raw audio file data (mp3, wav, webm, etc.)
 * @param string $filename - Original filename with extension
 * @param string|null $language - Optional language hint (ISO 639-1)
 * @return array - ['ok'=>bool, 'text'=>string] or ['ok'=>false, 'error'=>string]
 */
function openai_stt(string $audioData, string $filename = 'audio.webm', ?string $language = null): array
{
  global $CONFIG;
  $key = openai_key();
  if ($key === '')
    return ['ok' => false, 'error' => 'Missing OpenAI API key in config.php (openai.api_key).'];

  $model = $CONFIG['openai']['stt_model'] ?? 'gpt-4o-mini-transcribe';

  // Create temp file for cURL upload
  $tmpFile = tempnam(sys_get_temp_dir(), 'stt_');
  file_put_contents($tmpFile, $audioData);

  $postFields = [
    'file' => new CURLFile($tmpFile, 'audio/webm', $filename),
    'model' => $model,
  ];

  if ($language) {
    $postFields['language'] = $language;
  }

  $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $key,
    ],
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_TIMEOUT => 60,
  ]);

  $out = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);

  // Clean up temp file
  @unlink($tmpFile);

  if ($out === false)
    return ['ok' => false, 'error' => "cURL error: $err"];

  $json = json_decode($out, true);
  if ($code < 200 || $code >= 300) {
    $msg = $json['error']['message'] ?? ('HTTP ' . $code);
    return ['ok' => false, 'error' => $msg];
  }

  $text = $json['text'] ?? '';
  return ['ok' => true, 'text' => $text];
}

