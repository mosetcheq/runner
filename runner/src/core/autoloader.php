<?php
namespace Rnr\Core;

class Autoloader {

    static array $namespaceMap = [];

    /**
     * Autoload handler
     *
     * @param string $className Název požadované třídy
     * @return void
     */
    static function loadClass(string $className)
    {
        foreach(self::$namespaceMap as $prefix => $baseDir)
        {
            if(str_starts_with($className, $prefix))
            {
                $relative = substr($className, strlen($prefix));
                $file = rtrim($baseDir, '/') . '/' . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file))
                {
                    require $file;
                    return;
                }

            }
        }
    }


    /**
     * Nastavení namespace map z pole
     *
     * @param array $map
     * @return void
     */
    static function addMap(array $map) : void
    {
        self::$namespaceMap = array_merge(self::$namespaceMap, $map);
        uksort(self::$namespaceMap, fn($a, $b) => strlen($b) <=> strlen($a));
    }


    /**
     * Import namespace mapy - použít např. pro třídy managované composerem
     *
     * @param array $map
     * @return void
     */
    static function importMap(array $map) : void
    {
        foreach($map as $prefix => $dir)
        {
            $path = is_array($dir) ? ($dir[0] ?? null) : $dir;

            if (is_string($path) && $path !== '') {
                self::$namespaceMap[$prefix] = rtrim($path, '/');
            }
        }
        uksort(self::$namespaceMap, fn($a, $b) => strlen($b) <=> strlen($a));
    }

}

spl_autoload_register(['Rnr\Core\Autoloader', 'loadClass']);