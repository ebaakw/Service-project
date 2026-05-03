<?php

namespace App\Enums;

enum SettingValueType: string
{
    case String = 'string';
    case Number = 'number';
    case Boolean = 'boolean';
    case Json = 'json';
}
