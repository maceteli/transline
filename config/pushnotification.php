<?php

return [
  'gcm' => [
      'priority' => 'high',
      'dry_run' => false,
      'apiKey' => env('ANDROID_USER_PUSH_KEY', 'yourAPIKey'),
  ],
  'fcm' => [
        'priority' => 'high',
        'dry_run' => false,
        'apiKey' => env('ANDROID_PROVIDER_PUSH_KEY', 'yourAPIKey'),
  ],
  'apn' => [
      
  ]
];
