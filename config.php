<?php
return [
  'app' => [
    'name' => 'KELION AI',
    'version' => 'v1.1.0',
    'base_url' => '',
    'timezone' => 'Europe/London',
    'default_ui_lang' => 'English',
    'demo_user' => ['username' => 'demo', 'password' => 'demo'],
    'admin_seed' => ['username' => 'admin', 'password' => 'Andrada_1968!'],
  ],

  'db' => [
    'driver' => 'sqlite',
    'sqlite_path' => __DIR__ . '/storage/kelion.sqlite',
  ],

  'openai' => [
    'api_key' => base64_decode('c2stcHJvai1NTlhQT2FUSmpnYVYxeE1nRVZyTjBiV2lCTkEyYkZsQmpQR21PNDVTNEJkTGtQMjVUV2ZpQU5XRnRaM005REk3anR6RFZxYXFHZFQzQmxia0ZKUE1weFRDcnYzM1Nsb3E0TXhldmI5WWNFd1dKckZDY0JOdkdEWTVRV2MzUFNsRjYzazFoZEZKV2otWVFfd2dsZTZsRkFaNW4wa0E='),
    'chat_model' => 'gpt-4o',
    'tts_model' => 'tts-1',
    'stt_model' => 'whisper-1',
    'voice_default' => 'pNInz6obpgDQGcFmaJgB', // Adam (ElevenLabs)
  ],

  'elevenlabs' => [
    'enabled' => true,
    'api_key' => base64_decode('c2tfZWZlODkxMzZjNDhmOWQzYTdkN2UyZjFjOTJmYzc5NzAyMjBjOGNiNzRjYWFlOWQ4'),
  ],

  'voices_map' => [
    'English' => 'pNInz6obpgDQGcFmaJgB', // Adam
    'Romanian' => 'pNInz6obpgDQGcFmaJgB', // Adam
    'Spanish' => 'pNInz6obpgDQGcFmaJgB', // Adam
    'French' => 'pNInz6obpgDQGcFmaJgB', // Adam
    'German' => 'pNInz6obpgDQGcFmaJgB', // Adam
  ],

  'search' => [
    'enabled' => true,
    'provider' => 'serper', // serper.dev
    'api_key' => base64_decode('YjMzOWQwMjIwMGIxYmJmZWRlM2Y4Y2EyZTJiNWRjOWY1ZTcxNjdlZA=='),
  ],

  'mail' => [
    'from' => 'contact@kelionai.app',
    'smtp' => [
      'enabled' => true,
      'host' => 'smtp.privateemail.com',
      'port' => 465,
      'username' => 'contact@kelionai.app',
      'password' => base64_decode('QW5kcmFkYV8xOTY4IQ=='),
      'encryption' => 'ssl',
    ],
    'imap' => [
      'enabled' => true,
      'host' => 'mail.privateemail.com',
      'port' => 993,
      'username' => 'contact@kelionai.app',
      'password' => base64_decode('QW5kcmFkYV8xOTY4IQ=='),
      'encryption' => 'ssl',
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
      'enabled' => true,
      'mode' => 'live',
      'client_id' => base64_decode('QkFBai1LU0MtWTkwNVAxUnpnemMwN1RiM0x3X2FRUHE3MEg2OXdUTmxUZEpXY1pXWi1VMGUwVTl0YmJiUHNTNkVDbEJzWXdmYm1XdUg4QXBkMA=='),
      'client_secret' => base64_decode('RUQ4dHVtZ2FyX1hHRmZoaW1MUDdPSWhPdzh2MTU4WS1KdHpNTUV2dWRIeGYweWx6LVh4dExacDhhX3A1ZzlzVDkycmZrM0Y2QmlfMU5UYlI='),
      'webhook_id' => '',
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
