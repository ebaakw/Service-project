<?php

namespace App\Enums;

enum UserType: string
{
    case PlatformAdmin = 'platform_admin';
    case CompanyUser = 'company_user';
    case Technician = 'technician';
    case Customer = 'customer';
}
