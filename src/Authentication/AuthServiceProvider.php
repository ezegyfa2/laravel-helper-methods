<?php
namespace Ezegyfa\LaravelHelperMethods\Authentication;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AuthServiceProvider extends ServiceProvider
{
    public function register() {
    }

    public function boot() {
        $this->registerConfig();
        $this->registerMiddlewares();
        $this->registerRoutes();  
    }

    public function registerConfig() {
        $this->app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => __NAMESPACE__ . '\Admin']);
        $this->app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        $this->app['config']->set('auth.guards.providers.users', ['model' => __NAMESPACE__ . '\User']);
    }

    public function registerMiddlewares() {
        $router = $this->app['router'];
        $router->aliasMiddleware('adminAuth', __NAMESPACE__ . '\Admin\AdminAuthMiddleware');
    }

    protected function registerRoutes() {
        Route::group(['middleware'=> 'web', 'namespace' => __NAMESPACE__], function () {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        });
    }
}