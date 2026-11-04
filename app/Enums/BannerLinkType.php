<?php

namespace App\Enums;

enum BannerLinkType: string
{
    case None = 'none';
    case Url = 'url';
    case Category = 'category';
    case Company = 'company';
    case Screen = 'screen';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
