<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payments Enabled
    |--------------------------------------------------------------------------
    |
    | When false, online payment (Paystack) is disabled. The payment page
    | renders manual bank-transfer instructions instead. Paystack routes
    | return graceful responses. Set to true to re-enable card payments.
    |
    */

    'enabled' => env('PAYMENTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Manual Payment Instructions
    |--------------------------------------------------------------------------
    |
    | Bank details shown to members when payments are in manual mode.
    | These are env-backed placeholders — fill them in production.
    |
    */

    'manual_instructions' => [
        'bank' => env('MANUAL_PAYMENT_BANK', ''),
        'account_name' => env('MANUAL_PAYMENT_ACCOUNT_NAME', ''),
        'account_number' => env('MANUAL_PAYMENT_ACCOUNT_NUMBER', ''),
    ],

];
