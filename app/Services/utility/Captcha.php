<?php

namespace App\Services\utility;

use Gregwar\Captcha\CaptchaBuilder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Captcha
{
    public static function Build(): string
    {
        $width = 300;
        $height = 50;
        $char_number = 8;
        $builder = new CaptchaBuilder($char_number);
        $builder->build($width, $height);
        session()->put('captcha', $builder->getPhrase());
        return $builder->inline();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function Verify($input): bool
    {
        if (!session()->has('captcha'))
        {
            return false;
        }
        if (session()->get('captcha') !== $input)
        {
            return false;
        }
        return true;
    }
}
