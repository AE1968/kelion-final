<?php
return [
  'app' => [
    'name' => 'KELION AI',
    'version' => 'v1.0.6',
    'base_url' => '',
    'timezone' => 'Europe/London',
    'default_ui_lang' => 'English',
    'demo_user' => ['username' => 'demo', 'password' => 'demo'],
    'admin_seed' => ['username' => 'admin', 'password' => 'admin1234'],
  ],

  'db' => [
    'driver' => 'sqlite',
    'sqlite_path' => __DIR__ . '/storage/kelion.sqlite',
  ],

  'openai' => [
    'api_key' => getenv('OPENAI_API_KEY') ?: '', // <-- set this (or env var)
    'chat_model' => 'gpt-4o', // Most intelligent model
    'tts_model' => 'tts-1',
    'stt_model' => 'whisper-1',
    'voice_default' => 'cedar',
    // Built-in voices (OpenAI Audio API):
    'voices' => ['alloy', 'ash', 'ballad', 'coral', 'echo', 'fable', 'nova', 'onyx', 'sage', 'shimmer', 'verse', 'marin', 'cedar'],
    // Optional mapping (heuristic). User can override in UI.
    'voice_by_lang' => [
      'English' => 'cedar',
      'Romanian' => 'marin',
      'Spanish' => 'coral',
      'French' => 'nova',
      'German' => 'onyx',
      'Italian' => 'shimmer',
    ],
  ],

  'search' => [
    'enabled' => true,
    'provider' => 'serper', // serper.dev
    'api_key' => getenv('SERPER_API_KEY') ?: '',
  ],

  'mail' => [
    'from' => 'contact@kelionai.app',
    'smtp' => [
      'enabled' => false,
      'host' => '',
      'port' => 587,
      'username' => '',
      'password' => '',
      'encryption' => 'tls',
    ],
  ],

  'sms' => [
    'enabled' => false,
    'provider' => 'twilio',
    'account_sid' => '',
    'auth_token' => '',
    'from' => '',
  ],

  'payments' => [
    'currency' => 'GBP',
    'paypal' => [
      'enabled' => false,
      'mode' => 'sandbox',
      'client_id' => getenv('PAYPAL_CLIENT_ID') ?: '',
      'client_secret' => getenv('PAYPAL_CLIENT_SECRET') ?: '',
      'webhook_id' => getenv('PAYPAL_WEBHOOK_ID') ?: '',
    ],
    'bank' => [
      'enabled' => true,
      'account_name' => 'KELION AI LTD',
      'iban' => '',
      'sort_code' => '',
      'account_number' => '',
      'reference_prefix' => 'KEL',
    ],
  ],

  'security' => [
    'session_name' => 'KELIONSESS',
    'csrf_key' => 'csrf_token',
    'rate_limit' => [
      'login_per_ip_per_10min' => 25,
      'register_per_ip_per_10min' => 10,
      'ai_per_user_per_day' => 500,
    ],
  ],
];
