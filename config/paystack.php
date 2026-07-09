<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paystack Secret Key
    |--------------------------------------------------------------------------
    */

    'secretKey' => env('PAYSTACK_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Paystack Public Key
    |--------------------------------------------------------------------------
    */

    'publicKey' => env('PAYSTACK_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Paystack Payment URL
    |--------------------------------------------------------------------------
    */

    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),

    /*
    |--------------------------------------------------------------------------
    | Paystack Webhook Secret
    |--------------------------------------------------------------------------
    */

    'webhookSecret' => env('PAYSTACK_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Skip Paystack Verification (testing bypass)
    |--------------------------------------------------------------------------
    |
    | When true, the PaymentPage polling will activate the user immediately
    | upon returning from Paystack without verifying the transaction.
    | Only enable for testing.
    |
    */

    'skipVerification' => false,

];
