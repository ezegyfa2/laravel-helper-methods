<?php

namespace Ezegyfa\LaravelHelperMethods;

class FolderMethods
{
    public static function getFolderFilePathsRecoursively($folderPath)
    {
        $folderFilePaths = static::getFolderFilePaths($folderPath);
        $subFolders = static::getFolderSubFolders($folderPath);
        $folderFilePaths = array_diff($folderFilePaths, $subFolders);
        $folderFilePaths = array_map(function($folderFilePath) use($folderPath) {
            return static::combinePaths($folderPath, $folderFilePath);
        }, $folderFilePaths);
        foreach ($subFolders as $subFolder) {
            $subFolderPath = static::combinePaths($folderPath, $subFolder);
            $folderFilePaths = array_merge($folderFilePaths, static::getFolderFilePathsRecoursively($subFolderPath));
        }
        return array_values($folderFilePaths);
    }

    public static function getFolderFilePaths($folderPath)
    {
        $folderFiles = scandir($folderPath);
        unset($folderFiles[array_search('.', $folderFiles, true)]);
        unset($folderFiles[array_search('..', $folderFiles, true)]);
        return array_values($folderFiles);
    }

    public static function getFolderFilesRecoursively($folderPath)
    {
        $folderFiles = static::getFolderFiles($folderPath);
        $subFolders = static::getFolderSubFolders($folderPath);
        $folderFiles = array_diff($folderFiles, $subFolders);
        foreach ($subFolders as $subFolder) {
            $subFolderPath = static::combinePaths($folderPath, $subFolder);
            $folderFiles = array_merge($folderFiles, static::getFolderFilesRecoursively($subFolderPath));
        }
        return array_values($folderFiles);
    }

    public static function getFolderSubFolders($folderPath)
    {
        $folderFiles = static::getFolderFiles($folderPath);
        $subFolders = array_values(array_filter($folderFiles, function($nodeFolderFile) use($folderPath) {
            return is_dir(static::combinePaths($folderPath, $nodeFolderFile));
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

    public static function copyFolder($sourceFolderPath, $destinationFolderPath, $exceptions = []) {
        $dir = opendir($sourceFolderPath);
        @mkdir($destinationFolderPath);
        while($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && !in_array($file, $exceptions)) {
                $sourcePath = static::combinePaths($sourceFolderPath, $file);
                $destinationPath = static::combinePaths($destinationFolderPath, $file);
                if (is_dir($sourcePath)) {
                    static::copyFolder($sourcePath, $destinationPath);
                } else {
                    copy($sourcePath, $destinationPath);
                }
            }
        }
        closedir($dir);
    }

    public static function deleteFolder($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            $path = static::combinePaths($dir, $file);
            if (is_dir($path)) {
                static::deleteFolder($path);
            }
            else {
                chmod($path, 0777);
                unlink($path);
            }
        }
        return rmdir($dir);
    }

    public static function combinePaths(...$paths) {
        $standardPaths = array_map(function ($path) {
            $path = str_replace('\\', '/', $path);
            if (substr($path, -1) == '/') {
                return substr($path, 0, strlen($path) - 1);
            }
            else {
                return $path;
            }
        }, $paths);
        return StringMethods::concatenateStrings($standardPaths, '/');
    }

    public static function createIfNotExists(string $path) {
        if (!file_exists($path)) {
            mkdir($path);
        }
    }
}
