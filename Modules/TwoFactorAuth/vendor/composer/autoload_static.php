<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita33d5b3691088e3dc70535463442bc80
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'ParagonIE\\ConstantTime\\' => 23,
        ),
        'M' => 
        array (
            'Modules\\TwoFactorAuth\\' => 22,
        ),
        'D' => 
        array (
            'DarkGhostHunter\\Laraguard\\Listeners\\' => 36,
            'DarkGhostHunter\\Laraguard\\Eloquent\\' => 35,
            'DarkGhostHunter\\Laraguard\\' => 26,
            'DASPRiD\\Enum\\' => 13,
        ),
        'B' => 
        array (
            'BaconQrCode\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ParagonIE\\ConstantTime\\' => 
        array (
            0 => __DIR__ . '/..' . '/paragonie/constant_time_encoding/src',
        ),
        'Modules\\TwoFactorAuth\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
        'DarkGhostHunter\\Laraguard\\Listeners\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Overrides/DarkGhostHunter/Laraguard/Rules',
        ),
        'DarkGhostHunter\\Laraguard\\Eloquent\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Overrides/DarkGhostHunter/Laraguard/Eloquent',
        ),
        'DarkGhostHunter\\Laraguard\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Overrides/DarkGhostHunter/Laraguard',
            1 => __DIR__ . '/..' . '/darkghosthunter/laraguard/src',
        ),
        'DASPRiD\\Enum\\' => 
        array (
            0 => __DIR__ . '/..' . '/dasprid/enum/src',
        ),
        'BaconQrCode\\' => 
        array (
            0 => __DIR__ . '/..' . '/bacon/bacon-qr-code/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita33d5b3691088e3dc70535463442bc80::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita33d5b3691088e3dc70535463442bc80::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
