<?php

namespace cityfibre\notifications\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class PortalNotification
 *
 * @method static void publish(array $data)
 *
 * @category Notification
 *
 * @see PortalNotificationService
 *
 * @package cityfibre\notifications\Facade
 */
class PortalNotification extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'portal_notifications';
    }
}
