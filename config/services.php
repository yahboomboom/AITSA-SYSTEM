<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paymongo' => [
        'secret' => env('PAYMONGO_SECRET_KEY'),
        'public' => env('PAYMONGO_PUBLIC_KEY'),/**public key */
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
        'success_url' => env('PAYMONGO_SUCCESS_URL'),/**success */
        'cancel_url' => env('PAYMONGO_CANCEL_URL'),/**cancel */
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
    ],

        'docusign' => [
        // Master switch. Keep this false until real sandbox credentials are
        // filled in below — with it off, PaymentService skips DocuSign
        // entirely and behaves exactly as before (uploaded-image signature
        // only), so nothing breaks for a defense/demo without DocuSign set up.
        'enabled' => env('DOCUSIGN_ENABLED', false),

        // From your DocuSign Developer (sandbox) account, under Apps and Keys.
        'integration_key' => env('DOCUSIGN_INTEGRATION_KEY'),
        'user_id' => env('DOCUSIGN_USER_ID'), // API Username (a GUID), impersonated by the JWT grant.
        'account_id' => env('DOCUSIGN_ACCOUNT_ID'), // API Account ID (a GUID).

        // RSA private key generated for your Integration Key (PEM format).
        // Store the key file outside of source control and point to it here,
        // or base64-encode it into DOCUSIGN_PRIVATE_KEY_B64.
        'private_key_path' => env('DOCUSIGN_PRIVATE_KEY_PATH'),
        'private_key_b64' => env('DOCUSIGN_PRIVATE_KEY_B64'),

        // Sandbox vs production auth/API hosts.
        'auth_base_url' => env('DOCUSIGN_AUTH_BASE_URL', 'https://account-d.docusign.com'),
        'api_base_url' => env('DOCUSIGN_API_BASE_URL', 'https://demo.docusign.net/restapi'),

        // Optional: reuse a pre-built DocuSign template instead of the plain
        // inline HTML agreement this scaffold builds by default.
        'template_id' => env('DOCUSIGN_TEMPLATE_ID'),

        // HMAC key configured on the DocuSign Connect webhook, used the same
        // way PAYMONGO_WEBHOOK_SECRET is used above.
        'webhook_secret' => env('DOCUSIGN_WEBHOOK_SECRET'),
    ],
];
