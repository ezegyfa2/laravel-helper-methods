<?php

namespace Ezegyfa\LaravelHelperMethods\ServerCommands;

use Illuminate\Support\Facades\Route;

class ServerCommandMethods
{
    public static function registerServerCommandRoutes() {
        Route::get('/git-pull', function () {
            return static::executeCommand('git_pull');
        });
        Route::get('/git-reset', function () {
            return static::executeCommand('git_reset');
        });
        Route::get('/clear', function () {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
        });
    }

    public static function executeCommand(string $commandName) {
        $wrongPasswordCountFilePath = base_path('storage/wrongPasswordCount');
        if (!file_exists($wrongPasswordCountFilePath)) {
            $wrongPasswordCountFile = fopen($wrongPasswordCountFilePath, 'w');
            fwrite($wrongPasswordCountFile, 0);
            fclose($wrongPasswordCountFile);
        }
        $wrongPasswordCount = intval(file_get_contents($wrongPasswordCountFilePath));
        if ($wrongPasswordCount > 4) {
            $message = 'Too many wrong password';
        }
        else if (request()->get('password') == static::getPassword()) {
            file_put_contents($wrongPasswordCountFilePath, 0);
            $message = file_get_contents('http://127.0.0.1:8222/dynamic_web/command.php?command=' . $commandName);
        }
        else {
            ++$wrongPasswordCount;
            file_put_contents($wrongPasswordCountFilePath, $wrongPasswordCount);
            $message = 'Wrong password ' . (5 - $wrongPasswordCount) . ' try remaining.';
        }
        return response()->json([
            'message' => $message,
        ]);
    }

    public static function getPassword() {
        $date = new \DateTimeImmutable();
        return (int)($date->getTimestamp() / 100) * 12345;
    }
}