<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Service URL
    |--------------------------------------------------------------------------
    |
    | URL for the notification service.
    |
    */
    'url' => env('NOTIFICATION_SERVICE_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Notification Service API Token
    |--------------------------------------------------------------------------
    |
    | Bearer API token to send across to the notification service.
    |
    */
    'token' => env('NOTIFICATION_API_TOKEN', null),

    /*
    |--------------------------------------------------------------------------
    | Notification Created Topic
    |--------------------------------------------------------------------------
    |
    | Topic to send new notifications across.
    |
    */
    'notification_created_topic' => 'notification_created',

    /*
    |--------------------------------------------------------------------------
    | Notification Read Topic
    |--------------------------------------------------------------------------
    |
    | Topic to send payload across when a notification has been read
    |
    */
    'notification_read_topic' => 'notification_read',

    /*
    |--------------------------------------------------------------------------
    | Notification From
    |--------------------------------------------------------------------------
    |
    | From string to show on the notification in the portal E.g Billing Portal
    |
    */
    'from' => env('UNIFY_APPLICATION_NAME', '')

];
