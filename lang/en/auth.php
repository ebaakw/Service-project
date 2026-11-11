<?php

return array_merge(
    require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/auth.php'),
    [
        'failed' => 'The email or password is incorrect.',
        'web_login_forbidden' => 'This account type cannot sign in to the web dashboard.',
        'company_not_active' => 'Your account is not linked to an active company. Please contact platform administration.',
        'technician_not_approved' => 'Your technician account is pending review or not approved.',
        'account_deleted' => 'Your account has been deleted.',
        'cannot_delete_with_active_orders' => 'You cannot delete your account while you have active orders. Please wait until they are completed or cancelled.',
        'login_title' => 'Sign in',
        'login_hint' => 'Enter your credentials to access the dashboard.',
        'hero_title' => 'Operations, simplified.',
        'hero_subtitle' => 'Manage companies, orders, and field teams from one place.',
        'brand_subtitle' => 'Service operations',
        'switch_language' => 'Language',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
        'sign_in' => 'Sign in',
        'remember_me' => 'Remember me',
        'need_language_help' => 'Use the language switcher above to change the interface.',
        'email_placeholder_short' => 'Email address',
        'password_placeholder_short' => 'Password',
        'forgot_password' => 'Forgot your password?',
    ]
);
