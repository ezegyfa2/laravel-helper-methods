<?php

namespace Ezegyfa\LaravelHelperMethods;

use App\Http\ImageCache;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class ImageMethods
{
    public static function registerImageRoute() {
        Route::get('get-image', function(Request $request) {
            $imageFolderPath = $request->get('path');
            $imageWidths = static::getImageWidths($imageFolderPath);
            if ($imageWidths) {
                $imageWidth = static::getOptimalImageWidth($imageWidths, $request->get('width'));
                return response()->file(public_path('images/' . $imageFolderPath . '/' . $imageWidth . '.webp'));
            }
            else {
                return response()->json('Invalid file path: ' . $imageFolderPath, 400);
            }
        });
    }

    public static function getOptimalImageWidth(Array $imageWidths, $width) {
        if ($width) {
            foreach ($imageWidths as $imageWidth => $value) {
                if ($imageWidth >= $width) {
                    return $imageWidth;
                }
            }
            return end($imageWidths);
        }
        else {
            return end($imageWidths);
        }
    }

    public static function getImageWidths(string $folderPath) {
        $imagePathParts = explode('/', $folderPath);
        $imageWidths = ImageCache::getImageMap();
        foreach ($imagePathParts as $imagePathPart) {
            if (array_key_exists($imagePathPart, $imageWidths)) {
                $imageWidths = $imageWidths[$imagePathPart];
            }
            else {
                return null;
            }
        }
        return $imageWidths;
    }
}
