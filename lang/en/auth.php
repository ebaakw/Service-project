<?php

return array_merge(
    require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/auth.php'),
    [
        'web_login_forbidden' => 'This account type cannot sign in to the web dashboard.',
    ]
);
