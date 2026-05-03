<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow FakePaymentService placeholder endpoints (pay-inspection / pay-final).
    |--------------------------------------------------------------------------
    */

    'fake_payment_enabled' => env('ORDERS_FAKE_PAYMENT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Default inspection fee (KWD) when company has no price item and no company default.
    |--------------------------------------------------------------------------
    */
    'default_inspection_fee' => env('ORDERS_DEFAULT_INSPECTION_FEE', 5.000),

    /*
    |--------------------------------------------------------------------------
    | Sales tax rate applied to final invoice subtotal (0 = disabled).
    |--------------------------------------------------------------------------
    */
    'tax_rate' => env('ORDERS_TAX_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Fake payment link base URL (FakePaymentGateway).
    |--------------------------------------------------------------------------
    */
    'fake_payment_base_url' => env('ORDERS_FAKE_PAYMENT_BASE_URL', 'https://pay.fake.test'),

];
