<?php

namespace Ezegyfa\LaravelHelperMethods;

class FolderMethods
{
    public static function getFolderFilesRecoursively($folderPath)
    {
        $folderFiles = static::getFolderFiles($folderPath);
        $subFolders = static::getFolderSubFolders($folderPath);
        $folderFiles = array_diff($folderFiles, $subFolders);
        foreach ($subFolders as $subFolder) {
            $folderFiles = array_merge($folderFiles, static::getFolderFilesRecoursively($folderPath . '\\' . $subFolder));
        }
        return array_values($folderFiles);
    }

    public static function getFolderSubFolders($folderPath)
    {
        $folderFiles = static::getFolderFiles($folderPath);
        $subFolders = array_values(array_filter($folderFiles, function($nodeFolderFile) use($folderPath) {
            return is_dir($folderPath . '\\' . $nodeFolderFile);
        }));
        return $subFolders;
    }

    public static function getFolderFiles($folderPath)
    {
        $folderFiles = scandir($folderPath);
        unset($folderFiles[array_search('.', $folderFiles, true)]);
        unset($folderFiles[array_search('..', $folderFiles, true)]);
        return array_values($folderFiles);
    }

    public static function copyFolder($src, $dst, $exceptions = []) {
        $dir = opendir($src);
        @mkdir($dst);
        while($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && !in_array($file, $exceptions)) {
                if (is_dir($src . '\\' . $file)) {
                    static::copyFolder($src .'\\'. $file, $dst .'\\'. $file);
                } else {
                    copy($src .'/'. $file, $dst .'/'. $file);
                }
            }
        }
        closedir($dir);
    }

    public static function deleteFolder($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            if (is_dir("$dir\\$file")) {
                static::deleteFolder("$dir\\$file");
            }
            else {
                chmod("$dir\\$file", 0777);
                unlink("$dir\\$file");
            }
        }
        return rmdir($dir);
    }
}
