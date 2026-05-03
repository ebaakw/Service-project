<?php

namespace App\Enums;

enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Boolean = 'boolean';
    case Date = 'date';
    case Image = 'image';
}
