<?php

namespace cityfibre\notifications;

use Carbon\Carbon;
use cityfibre\notifications\resources\InvalidNotificationLinkException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
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
    public PubSubAdapterInterface $pubSubAdapter;

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
     * @throws InvalidNotificationLinkException
     */
    public function publish(array $data): void
    {
        // Validate the given payload
        $this->validatePayload($data);

        // Set the portal the notification is coming form
        $data['from'] = $this->processFrom();

        // Parse the link to a full URL based on App URL
        $data['link'] = url(config('app.url') . $data['link']);

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
        $user = auth()->user();

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
        $user = auth()->user();

        // Get the read timestamp
        $read_timestamp = Carbon::now()->format("y-m-d H:i:s");

        // Foreach notification ID
        foreach ($notifications as $notification) {
            // Send the topic with the notification id, user email and the timestamp
            $this->pubSubAdapter->publish(
                config('notifications.notification_read_topic'),
                [
                    'notification_id' => $notification,
                    'user_email' => $user->email,
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
        if (! strlen($appName)) {
            // Throw the exception for invalid app name
            throw new InvalidAppNameForNotificationFromException(
                'The notification from is not set.'
            );
        }

        // Return the app name
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
     * @throws InvalidNotificationLinkException
     *
     */
    private function validatePayload(array $payload)
    {
        // Set the needed fields
        $neededFields = ['message', 'link', 'customer_id', 'icon'];

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

        // If the link doesnt start with a forward slash
        if (! Str::startsWith($payload['link'], '/')) {
            // Throw invalid link exception
            throw new InvalidNotificationLinkException(
                sprintf('Notification link [%s] is invalid, link must start with /', $payload['link'])
            );
        }

        // Return true for success
        return true;
    }
}
