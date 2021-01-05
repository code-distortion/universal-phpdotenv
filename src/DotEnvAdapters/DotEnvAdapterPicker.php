<?php

namespace CodeDistortion\FluentDotEnv\DotEnvAdapters;

use CodeDistortion\FluentDotEnv\DotEnvAdapters\Symfony\SymfonyAdapter3;
use CodeDistortion\FluentDotEnv\DotEnvAdapters\Symfony\SymfonyAdapter4Plus;
use CodeDistortion\FluentDotEnv\DotEnvAdapters\VLucas\VLucasAdapterV1;
use CodeDistortion\FluentDotEnv\DotEnvAdapters\VLucas\VLucasAdapterV2;
use CodeDistortion\FluentDotEnv\DotEnvAdapters\VLucas\VLucasAdapterV3;
use CodeDistortion\FluentDotEnv\DotEnvAdapters\VLucas\VLucasAdapterV4;
use CodeDistortion\FluentDotEnv\DotEnvAdapters\VLucas\VLucasAdapterV5;
use CodeDistortion\FluentDotEnv\Exceptions\DependencyException;
use Dotenv as DotenvV1;
use Dotenv\Dotenv as DotenvV2;
use Dotenv\Dotenv as DotenvV4;
use Dotenv\Dotenv as DotenvV5;
use Dotenv\Environment\DotenvFactory as DotenvFactoryV3;
use ReflectionMethod;
use Symfony\Component\Dotenv\Dotenv as SymfonyDotenv;

/**
 * Determine which DotEnvAdapter to use.
 */
class DotEnvAdapterPicker
{
    /**
     * Create a new DotEnvAdapter object, corresponding to the library and version detected.
     *
     * @param string[] $order The order to look for packages in.
     * @return DotEnvAdapterInterface
     * @throws DependencyException When a supported dotenv package and version cannot be found.
     */
    public static function pickAdapter(array $order = ['vlucas', 'symfony']): DotEnvAdapterInterface
    {
        $adapter = null;
        foreach ($order as $type) {
            switch ($type) {
                // look for vlucas/phpdotenv
                case 'vlucas':
                    $adapter = static::detectVLucasPhpDotEnv();
                    break;
                // look for symfony/dotenv
                case 'symfony':
                    $adapter = static::detectSymfonyDotEnv();
                    break;
            }
            if ($adapter) {
                return $adapter;
            }
        }

        throw DependencyException::dotEnvReaderPackageNotDetected();
    }

    /**
     * Detect the version of vlucas/phpdotenv installed.
     *
     * @return DotEnvAdapterInterface|null
     * @throws DependencyException When the vlucas/phpdotenv package cannot be found.
     */
    public static function detectVLucasPhpDotEnv()
    {
        if (method_exists(DotenvV5::class, 'createUnsafeImmutable')) {
            return new VLucasAdapterV5();
        } elseif (method_exists(DotenvV4::class, 'createImmutable')) {
            return new VLucasAdapterV4();
        } elseif (class_exists(DotenvFactoryV3::class)) {
            return new VLucasAdapterV3();
        } elseif (class_exists(DotenvV2::class)) {
            return new VLucasAdapterV2();
        } elseif (class_exists(DotenvV1::class)) {
            return new VLucasAdapterV1();
        }
        return null;
    }

    /**
     * Detect the version of symfony/dotenv installed.
     *
     * @return DotEnvAdapterInterface|null
     * @throws DependencyException When the symfony/dotenv package cannot be found.
     */
    public static function detectSymfonyDotEnv()
    {
        if (class_exists(SymfonyDotenv::class)) {

            // before version 3.3.7, symfony/dotenv's Dotenv::populate(..) method checked to see if each value is
            // present in getenv(..) first before importing it. An extra step needs to be taken to compensate for this.

            // to try and detect a change after this so the extra work can be removed, this code looks for the 'void'
            // return type in the Dotenv::load(..) method which was added in version 4.0.0
            $reflectionMethod = new ReflectionMethod(SymfonyDotenv::class, 'load');
            if ((string) $reflectionMethod->getReturnType() == 'void') {
                return new SymfonyAdapter4Plus();
            }
            return new SymfonyAdapter3();
        }
        return null;
    }
}
