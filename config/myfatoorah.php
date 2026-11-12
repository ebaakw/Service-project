<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable MyFatoorah as the active payment gateway.
    | When false, FakePaymentGateway is used (local/testing/placeholders).
    |--------------------------------------------------------------------------
    */
    'enabled' => (bool) env('MYFATOORAH_ENABLED', false),

    'api_key' => env('MYFATOORAH_API_KEY'),

    /*
    | Test: https://apitest.myfatoorah.com
    | Live KW: https://api-kw.myfatoorah.com
    | Leave empty to pick test/live default from is_test + country.
    */
    'api_base_url' => env('MYFATOORAH_API_BASE_URL'),

    'is_test' => (bool) env('MYFATOORAH_IS_TEST', true),

    /*
    | Country vendor code for default hosts: KWT, SAU, ARE, QAT, BHR, OMN, JOD, EGY
    */
    'country_iso' => env('MYFATOORAH_COUNTRY_ISO', 'KWT'),

    'webhook_secret' => env('MYFATOORAH_WEBHOOK_SECRET'),

    /*
    | After payment (browser redirect). Mobile can register a custom scheme
    | e.g. khidmati://payments/result — leave empty to return JSON.
    */
    'success_redirect_url' => env('MYFATOORAH_SUCCESS_REDIRECT_URL'),
    'error_redirect_url' => env('MYFATOORAH_ERROR_REDIRECT_URL'),

    'currency_iso' => env('MYFATOORAH_CURRENCY_ISO', 'KWD'),

    'language' => env('MYFATOORAH_LANGUAGE', 'AR'),

];
