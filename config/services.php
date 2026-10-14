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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'external_api' => [
        'key' => env('API_KEY'),
    ],

    'facturapi' => [
        'key' => env('FACTURAPI_API_KEY'),
        'default_use_cfdi' => env('FACTURAPI_USE_CFDI', 'G03'),
        'default_payment_form_contado' => env('FACTURAPI_PAYMENT_FORM_CONTADO', '01'),
        'default_payment_form_credito' => env('FACTURAPI_PAYMENT_FORM_CREDITO', '99'),
        'default_payment_method_contado' => env('FACTURAPI_PAYMENT_METHOD_CONTADO', 'PUE'),
        'default_payment_method_credito' => env('FACTURAPI_PAYMENT_METHOD_CREDITO', 'PPD'),
        'default_product_key' => env('FACTURAPI_DEFAULT_PRODUCT_KEY', '01010101'),
        'default_unit_key' => env('FACTURAPI_DEFAULT_UNIT_KEY', 'ACT'),
    ],
];
