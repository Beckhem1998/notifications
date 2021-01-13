<?php

namespace cityfibre\notifications;

use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

/**
 * Class PortalNotificationsController
 *
 * @category
 * @package cityfibre\notifications
 */
class PortalNotificationsController extends Controller
{
    /**
     * Notification Service
     *
     * @var PortalNotificationService $service
     */
    public $service;

    /**
     * PortalNotificationsController constructor.
     *
     * @param PortalNotificationService $service - Notification Service
     *
     * @return void
     */
    public function __construct(PortalNotificationService $service)
    {
        $this->service = $service;
    }

    /**
     * Show a page of notifications for a user.
     *
     * @param int $page - Page number
     *
     * @throws GuzzleException
     *
     * @return string
     */
    public function show(int $page)
    {
       return $this->service->show($page);
    }

    public function getIcon(string $appName): string
    {
        return $this->service->getIcon($appName);
    }

    /**
     * Read a notification from the given notification ID
     *
     * @param Request $request - Notification IDs
     *
     * @return void
     */
    public function read(Request $request)
    {
       $this->service->read($request->notifications);
    }
}
