<?php

return [
    'gdpr_secret_key' => env('GDPR_SECRET_KEY'),
    'gdpr_secret_iv' => env('GDPR_SECRET_IV'),
    'encrypt_decrypt_secret_key' => env('encrypt_decrypt_secret_key'),
    'encrypt_decrypt_secret_iv' => env('encrypt_decrypt_secret_iv'),
    'encrypt_decrypt_method' => env('encrypt_decrypt_method', 'AES-256-CBC')
];