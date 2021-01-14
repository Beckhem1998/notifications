<?php

namespace cityfibre\notifications;

use Carbon\Carbon;
use cityfibre\defaultPortalTemplate\Models\ApplicationModel;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Superbalist\PubSub\PubSubAdapterInterface;
use GuzzleHttp\Client;

/**
 * Class PortalNotificationService
 *
 * @category Notification
 * @package cityfibre\notifications
 */
class PortalNotificationService
{
    /**
     * Kafka Adapter
     *
     * @var PubSubAdapterInterface $pubSubAdapter
     */
    public $pubSubAdapter;

    /**
     * PortalNotificationService constructor.
     *
     * @param PubSubAdapterInterface $pubSubAdapter - Kafka Adapter
     *
     * @return void
     */
    public function __construct(PubSubAdapterInterface $pubSubAdapter)
    {
        $this->pubSubAdapter = $pubSubAdapter;
    }

    /**
     * Publish a notification from the given data
     *
     * @param array $data - Notification data
     *
     * @return void
     * @throws PortalNotificationFieldNotSetException
     *
     * @throws InvalidAppNameForNotificationFromException
     */
    public function publish(array $data): void
    {
        // Validate the given payload
        $this->validatePayload($data);

        // Set the portal the notification is coming form
        $data['from'] = $this->processFrom();

        // Publish the notification_created event
        $this->pubSubAdapter->publish(
            config('notifications.notification_created_topic'),
            $data
        );
    }

    /**
     * Get paginated notifications from the notification service
     *
     * @param int $page - Page Number
     *
     * @return false|string
     * @throws GuzzleException
     *
     */
    public function show(int $page)
    {
        // JSON decode the notifications
        $notifications = json_decode($this->fetch($page));

        // Foreach notification
        foreach ($notifications->data as $notification) {
            $application = ApplicationModel::where('name', $notification->from)->first();

            // Set the from icon
            $notification->from_icon = $application->application_icon ?? 'zmdi zmdi-account';

            // Set the application URL
            $notification->link = env($application->application_url) . $notification->link;
        }

        // Return the JSON encoded notifications
        return json_encode($notifications);
    }

    /**
     * Fetch the notifications for the user that is logged in.
     *
     * @param int $page - Page number
     *
     * @return string
     * @throws GuzzleException
     *
     */
    public function fetch(int $page): string
    {
        // get the user model from the logged in user
        $user = Auth::user();

        // Get the guzzle client
        $guzzleClient = new Client();

        // Send the post request to the notification service
        $response = $guzzleClient->request(
            'GET',
            config(
                'notifications.url'
            ) . '/api/notifications/' . $user->customer_id . '/' . $user->email . '?page=' . $page,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('notifications.token'),
                    'Accept' => 'application/json'
                ],
            ]
        );

        // Return the JSON encoded paginated notifications
        return $response->getBody()->getContents();
    }

    /**
     * Send the notification read topic to the notification service
     * across Kafka.
     *
     * @param array $notifications - Notifications to read
     *
     * @return void
     */
    public function read(array $notifications): void
    {
        // Get the user
        $user = Auth::user()->email;

        // Get the read timestamp
        $read_timestamp = Carbon::now()->format("y-m-d H:i:s");

        // Foreach notification ID
        foreach ($notifications as $notification) {
            // Send the topic with the notification id, user email and the timestamp
            $this->pubSubAdapter->publish(
                config('notifications.notification_read_topic'),
                [
                    'notification_id' => $notification,
                    'user_email' => $user,
                    'read_timestamp' => $read_timestamp
                ]
            );
        }
    }

    /**
     * Process the from parameter from the app name config.
     *
     * @private
     *
     * @return string
     * @throws InvalidAppNameForNotificationFromException
     *
     */
    private function processFrom(): string
    {
        // Get the from name
        $appName = config('notifications.from');

        // If the app name isn't set
        if (empty($appName)) {
            // Throw the exception for invalid app name
            throw new InvalidAppNameForNotificationFromException(
                sprintf(
                    "The notification from [%s] is invalid, correct example: Billing Portal.",
                    $appName
                )
            );
        }

        // Return the lowercase app name
        return $appName;
    }

    /**
     * Validate the new notification payload for the needed fields.
     *
     * @private
     *
     * @param array $payload - new notification payload
     *
     * @return bool
     * @throws PortalNotificationFieldNotSetException
     *
     */
    private function validatePayload(array $payload)
    {
        // Set the needed fields
        $neededFields = ['message', 'link', 'customer_id'];

        // Foreach needed field
        foreach ($neededFields as $neededField) {
            // If the field is not set or is empty
            if (!isset($payload[$neededField]) || !strlen($payload[$neededField])) {
                // Throw exception for needed field
                throw new PortalNotificationFieldNotSetException(
                    sprintf(
                        "The %s field needs to be set to send a notification",
                        ucwords(str_replace(" ", "_", $neededField))
                    )
                );
            }
        }

        // Return true for success
        return true;
    }
}
