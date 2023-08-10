<?php
namespace Ezegyfa\LaravelHelperMethods\Authentication;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AuthServiceProvider extends ServiceProvider
{
    public static $namespace = 'Ezegyfa\LaravelHelperMethods\Authentication';

    public function register() {
    }

    public function boot() {
        $this->registerConfig();
        $this->registerMiddlewares();
        $this->registerRoutes();  
    }

    public function registerConfig() {
        $this->app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => static::$namespace . '\Admin']);
        $this->app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        $this->app['config']->set('auth.guards.providers.users', ['model' => static::$namespace . '\User']);
    }

    public function registerMiddlewares() {
        $router = $this->app['router'];
        $router->aliasMiddleware('adminAuth', static::$namespace . '\AdminAuthMiddleware');
    }

    protected function registerRoutes() {
        Route::group(['middleware'=> 'web', 'namespace' => static::$namespace], function () {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        });
    }
}