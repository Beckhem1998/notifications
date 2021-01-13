<?php

Route::group(['middleware' => ['auth']], function () {

    Route::put('/api/notifications/read', 'cityfibre\notifications\PortalNotificationsController@read');

    Route::resource('/api/notifications', 'cityfibre\notifications\PortalNotificationsController');

    Route::get('/api/notifications/getIcon/{appName}', 'cityfibre\notifications\PortalNotificationsController@getIcon');
});
