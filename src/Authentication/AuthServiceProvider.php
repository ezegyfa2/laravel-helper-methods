<?php
namespace Ezegyfa\LaravelHelperMethods\Authentication;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AuthServiceProvider extends ServiceProvider
{
    public function register() {}

    public function boot() {
        $this->registerConfig();
        $this->registerMiddlewares();
        $this->registerRoutes();
    }

    public function registerConfig() {
        $this->app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => __NAMESPACE__ . '\Admin\Admin']);
        $this->app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        $this->app['config']->set('auth.guards.providers.users', ['model' => __NAMESPACE__ . '\User\User']);
    }

    public function registerMiddlewares() {
        $router = $this->app['router'];
        $router->aliasMiddleware('adminAuth', __NAMESPACE__ . '\Admin\AdminAuthMiddleware');
        $spaieMiddlewares = [
            'role' => 'RoleMiddleware',
            'permission' => 'PermissionMiddleware',
            'role_or_permission' => 'RoleOrPermissionMiddleware',
        ];
        foreach($spaieMiddlewares as $alias => $className) {
            $router->aliasMiddleware($alias, '\Spatie\Permission\Middlewares\\' . $className);
        }
    }

    protected function registerRoutes() {
        Route::group(['middleware'=> 'web', 'namespace' => __NAMESPACE__], function () {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        });
    }
}