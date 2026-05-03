<?php

namespace App\Enums;

enum SocialLoginProvider: string
{
    case Google = 'google';
    case Apple = 'apple';
}
