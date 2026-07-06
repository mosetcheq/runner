<?php

namespace Rnr;

class FileSystem {

        public static function RemoveDirectory($directory_name) {
                $directory_name = trim($directory_name, '/');
                if(!is_dir($directory_name)) return false;
                $dir = new \DirectoryIterator($directory_name);
                foreach($dir as $item) if(!$item->isDot()) {
                        if($item->isDir()) self::RemoveDirectory($directory_name.'/'.$item->getFilename());
                        else unlink($directory_name.'/'.$item->getFilename());
                }
                if(rmdir($directory_name)) return true; else return false;
        }

}