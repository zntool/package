<?php

namespace ZnTool\Package\Domain\Helpers;

class VendorHelper
{

    /*const VENDOR_DOWNLOAD_URL = 'https://tlg-assistant.000webhostapp.com/telegram-client/vendor.phar.gz';
    public static function makeVendorDir() {
        echo PHP_EOL . PHP_EOL . 'make vendor dir...' . PHP_EOL . PHP_EOL;
        if( ! is_dir(__DIR__ . '/../../../vendor')) {
            mkdir(__DIR__ . '/../../../vendor');
        }
    }

    public static function removeVendorDir() {
        echo PHP_EOL . PHP_EOL . 'remove vendor dir...' . PHP_EOL . PHP_EOL;
        if(is_dir(__DIR__ . '/../../../vendor')) {
            //rmdir(__DIR__ . '/../../../vendor');
        }
    }

    public static function removeVendorPhar() {
        echo PHP_EOL . PHP_EOL . 'remove vendor phar...' . PHP_EOL . PHP_EOL;
        if(is_file(__DIR__ . '/../../../vendor/vendor.phar')) {
            unlink(__DIR__ . '/../../../vendor/vendor.phar');
        }
    }

    public static function removeVendorPharGz() {
        echo PHP_EOL . PHP_EOL . 'remove vendor phar gz...' . PHP_EOL . PHP_EOL;
        if(is_file(__DIR__ . '/../../../vendor/vendor.phar.gz')) {
            unlink(__DIR__ . '/../../../vendor/vendor.phar.gz');
        }
    }

    public static function autoload($rootDir) {
        self::makeVendorDir();

        if (!file_exists('madeline.php')) {
            copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
        }
        require_once 'madeline.php';

    }

    public static function rootDir(): string {
        $rootDir = realpath(__DIR__ . '/../../..');
        return $rootDir;
    }

    public static function compressPhar() {
        echo PHP_EOL . PHP_EOL . 'Compress phar...' . PHP_EOL . PHP_EOL;
        if(file_exists(__DIR__ . '/../../../vendor/vendor.phar')) {
            $content = file_get_contents(__DIR__ . '/../../../vendor/vendor.phar');
            file_put_contents(__DIR__ . '/../../../vendor/vendor.phar.gz', gzencode($content));
        }
    }

    private static function unGzPhar() {
        echo PHP_EOL . PHP_EOL . 'UnCompress phar...' . PHP_EOL . PHP_EOL;
        $gzContent = file_get_contents(__DIR__ . '/../../../vendor/vendor.phar.gz');
        file_put_contents(__DIR__ . '/../../../vendor/vendor.phar', gzdecode($gzContent));
        unlink(__DIR__ . '/../../../vendor/vendor.phar.gz');
    }

    private static function downloadPhar($rootDir) {
        echo PHP_EOL . PHP_EOL . 'Download phar gz...' . PHP_EOL . PHP_EOL;
        $gzContent = file_get_contents($_ENV['VENDOR_DOWNLOAD_URL'] ?? self::VENDOR_DOWNLOAD_URL);
        if($gzContent) {
            file_put_contents(__DIR__ . '/../../../vendor/vendor.phar.gz', $gzContent);
        }
    }

    public static function buildPhar($rootDir) {
        echo PHP_EOL . PHP_EOL . 'Building phar...' . PHP_EOL . PHP_EOL;
        require_once __DIR__ . '/../Helpers/Packager.php';
        $packager = new Packager($rootDir . '/vendor');
        $packager->export($rootDir);
        //echo PHP_EOL . PHP_EOL . 'Building completed!' . PHP_EOL . PHP_EOL;
    }

    private static function nativeAutoload($rootDir) {
        if(file_exists($rootDir . '/vendor/autoload.php')) {
            include_once $rootDir . '/vendor/autoload.php';
            return true;
        }
        return false;
    }*/
}