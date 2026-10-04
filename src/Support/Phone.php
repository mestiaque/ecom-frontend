<?php

namespace ME\Efront\Support;

class Phone
{
    /** Bangladeshi mobile number, with or without +88. */
    public const RULE = 'regex:/^(\+?88)?01[3-9]\d{8}$/';

    public const MESSAGE = 'Enter a valid Bangladeshi mobile number (01XXXXXXXXX).';

    /**
     * 01XXXXXXXXX (drops +88 / 88 and anything that is not a digit).
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '88') && strlen($digits) === 13 ? substr($digits, 2) : $digits;
    }
}
