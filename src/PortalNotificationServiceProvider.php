<?php

namespace cityfibre\notifications;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

/**
 * Class PortalNotificationServiceProvider
 *
 * @category Notification
 * @package cityfibre\notifications
 */
class PortalNotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @throws BindingResolutionException
     *
     * @return void
     */
    public function register()
    {
        $this->app->make(PortalNotificationsController::class);

        $this->app->singleton('portal_notifications', function () {
            return $this->app->make(PortalNotificationService::class);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        $this->publishes([
            __DIR__ . '/resources/js/components' => resource_path('js/components/'
        )], 'vue-components');

        $this->publishes([
            __DIR__ . '/config' => config_path()
        ], 'config');
    }
}
