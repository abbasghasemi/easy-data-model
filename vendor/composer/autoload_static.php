<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5e118e32112ed44e4bbca97b81894665
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'AG\\DataModel\\Test\\' => 18,
            'AG\\DataModel\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'AG\\DataModel\\Test\\' => 
        array (
            0 => __DIR__ . '/../..' . '/test',
        ),
        'AG\\DataModel\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5e118e32112ed44e4bbca97b81894665::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5e118e32112ed44e4bbca97b81894665::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit5e118e32112ed44e4bbca97b81894665::$classMap;

        }, null, ClassLoader::class);
    }
}