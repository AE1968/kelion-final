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
  $patterns = [
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
      return "I can’t help with violence, self-harm, hate, or illegal wrongdoing.";
    }
  }
  return null;
}

function openai_answer(string $userText, string $lang = 'AUTO', array $conversationHistory = []): array
{
  $block = kelion_safety_block($userText);
  if ($block)
    return ['ok' => true, 'text' => $block];

  global $CONFIG;
  $model = $CONFIG['openai']['chat_model'] ?? 'gpt-4o';

  // System prompt
  $system = "You are KELION, a futuristic hologram guardian assistant. Memory active. Web Search & Email tools enabled. 
  IMPORTANT: You MUST output a JSON object. Format: { \"language\": \"DetectedUserLanguage (e.g. Romanian, English)\", \"content\": \"Your response text here\" }.
  Reply in the user's language. Be helpful, friendly, intelligent (GPT-4o).
  If asked to check email, use check_email tool. If asked to send email, use send_email tool.";

  // Build messages array
  $messages = [
    ['role' => 'system', 'content' => $system]
  ];

  // History with JSON cleanup
  $historyLimit = min(count($conversationHistory), 10);
  for ($i = 0; $i < $historyLimit; $i++) {
    $msg = $conversationHistory[$i];
    $role = $msg['role'] === 'user' ? 'user' : 'assistant';
    $histText = (string) $msg['text'];
    $decoded = json_decode($histText, true);
    if (is_array($decoded) && isset($decoded['content'])) {
      $histText = $decoded['content'];
    }
    $messages[] = ['role' => $role, 'content' => $histText];
  }

  $messages[] = ['role' => 'user', 'content' => $userText];

  // Define Tools
  $tools = [
    [
      'type' => 'function',
      'function' => [
        'name' => 'web_search',
        'description' => 'Search the internet for real-time information.',
        'parameters' => [
          'type' => 'object',
          'properties' => ['query' => ['type' => 'string']],
          'required' => ['query'],
        ],
      ],
    ],
    [
      'type' => 'function',
      'function' => [
        'name' => 'check_email',
        'description' => 'Check recent emails in the inbox (read last 5).',
        'parameters' => [
          'type' => 'object',
          'properties' => ['limit' => ['type' => 'integer', 'description' => 'Default 5']],
        ],
      ],
    ],
    [
      'type' => 'function',
      'function' => [
        'name' => 'send_email',
        'description' => 'Send an email to a recipient.',
        'parameters' => [
          'type' => 'object',
          'properties' => [
            'to' => ['type' => 'string'],
            'subject' => ['type' => 'string'],
            'body' => ['type' => 'string'],
          ],
          'required' => ['to', 'subject', 'body'],
        ],
      ],
    ]
  ];

  $payload = [
    'model' => $model,
    'messages' => $messages,
    'tools' => $tools,
    'tool_choice' => 'auto',
    'response_format' => ['type' => 'json_object'],
  ];

  $res = openai_post_json('https://api.openai.com/v1/chat/completions', $payload);
  if (!$res['ok'])
    return $res;

  $msg = $res['data']['choices'][0]['message'] ?? [];

  // Handle Tool Calls
  if (!empty($msg['tool_calls'])) {
    $messages[] = $msg;

    // Include mailer if needed
    if (file_exists(__DIR__ . '/mailer_ai.php')) {
      require_once __DIR__ . '/mailer_ai.php';
    }

    foreach ($msg['tool_calls'] as $tc) {
      $args = json_decode($tc['function']['arguments'], true);
      $resContent = '';
      $n = $tc['function']['name'];

      if ($n === 'web_search') {
        $resContent = json_encode(kelion_web_search($args['query'] ?? ''));
      } elseif ($n === 'check_email') {
        $limit = $args['limit'] ?? 5;
        $resContent = json_encode(function_exists('ai_check_email') ? ai_check_email($limit) : ['error' => 'Email module missing']);
      } elseif ($n === 'send_email') {
        $resContent = json_encode(function_exists('ai_send_email') ? ai_send_email($args['to'] ?? '', $args['subject'] ?? '', $args['body'] ?? '') : ['error' => 'Email module missing']);
      }

      $messages[] = [
        'role' => 'tool',
        'tool_call_id' => $tc['id'],
        'content' => $resContent
      ];
    }

    $payload['messages'] = $messages;
    unset($payload['tools']); // Final answer without tools
    $res2 = openai_post_json('https://api.openai.com/v1/chat/completions', $payload);
    if (!$res2['ok'])
      return $res2;
    $msg = $res2['data']['choices'][0]['message'] ?? [];
  }

  $rawContent = $msg['content'] ?? '';
  $parsed = json_decode($rawContent, true);
  $finalText = '';
  $detectedLang = 'English';

  if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
    $finalText = $parsed['content'] ?? $rawContent;
    $detectedLang = $parsed['language'] ?? 'English';
  } else {
    $finalText = $rawContent;
  }

  $finalText = trim($finalText);
  if ($finalText === '')
    $finalText = '[No output]';

  return ['ok' => true, 'text' => $finalText, 'lang' => $detectedLang];
}

function kelion_web_search(string $query): array
{
  global $CONFIG;
  $key = $CONFIG['search']['api_key'] ?? '';
  if (!$key)
    return ['error' => 'Search API key not configured.'];

  $ch = curl_init('https://google.serper.dev/search');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['X-API-KEY: ' . $key, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['q' => $query, 'num' => 5]),
    CURLOPT_TIMEOUT => 10
  ]);
  $out = curl_exec($ch);
  curl_close($ch);

  if (!$out)
    return ['error' => 'Search failed'];
  $j = json_decode($out, true);

  $results = [];
  if (!empty($j['organic'])) {
    foreach ($j['organic'] as $r) {
      $results[] = [
        'title' => $r['title'] ?? '',
        'link' => $r['link'] ?? '',
        'snippet' => $r['snippet'] ?? ''
      ];
    }
  }
  return $results;
}

function openai_tts_mp3(string $text, ?string $voice = null, ?string $model = null): array
{
  global $CONFIG;
  $key = openai_key();
  if ($key === '')
    return ['ok' => false, 'error' => 'Missing OpenAI Key'];

  $voice = $voice ?: ($CONFIG['openai']['voice_default'] ?? 'cedar');
  $model = 'tts-1';

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
  curl_close($ch);

  if ($bin === false)
    return ['ok' => false, 'error' => 'cURL error'];
  return ['ok' => true, 'bin' => $bin];
}

function openai_stt(string $audioData, string $filename = 'audio.webm', ?string $language = null): array
{
  global $CONFIG;
  $key = openai_key();
  $tmpFile = tempnam(sys_get_temp_dir(), 'stt_');
  file_put_contents($tmpFile, $audioData);

  $postFields = [
    'file' => new CURLFile($tmpFile, 'audio/webm', $filename),
    'model' => 'whisper-1',
  ];
  if ($language)
    $postFields['language'] = $language;

  $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key],
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_TIMEOUT => 60,
  ]);

  $out = curl_exec($ch);
  curl_close($ch);
  @unlink($tmpFile);

  $json = json_decode($out, true);
  return ['ok' => true, 'text' => $json['text'] ?? ''];
}
