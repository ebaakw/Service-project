<?php

namespace App\Enums;

enum CompanyMemberStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
}
