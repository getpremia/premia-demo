<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit866d0f3705af9674d385a0b83c242af6
{
    public static $files = array (
        '49a1299791c25c6fd83542c6fedacddd' => __DIR__ . '/..' . '/yahnis-elsts/plugin-update-checker/load-v4p11.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit866d0f3705af9674d385a0b83c242af6::$classMap;

        }, null, ClassLoader::class);
    }
}
