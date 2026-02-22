<?php

namespace App\Services;

use App\Models\Order;
use Random\RandomException;

class OrderNumber
{
    /**
     * @throws RandomException
     */
    public static function uniqueCode($length): string
    {
        do
        {
            $code = self::randomCode($length);
        }
        while(Order::where('code', $code)->exists());

        return $code;
    }
    /**
     * @throws RandomException
     */
    private static function randomCode($length): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i<$length; $i++)
        {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $result;
    }
}
