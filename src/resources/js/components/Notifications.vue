<template>
    <!-- Notification Container -->
    <li class="dropdown top-nav__notifications">

        <!-- Notification Bell -->
        <a href="" data-toggle="dropdown" id="notification_bell">
            <i class="zmdi zmdi-notifications"></i>
        </a>

        <!-- Notifications -->
        <div class="dropdown-menu dropdown-menu-right dropdown-menu--block" id="dropdown">

            <!-- Header -->
            <div class="listview listview--hover">
                <div class="listview__header">
                    Notifications

                    <!-- Read All Icon-->
                    <div class="actions">
                        <a href="" id="read-all" class="actions__item zmdi zmdi-check-all" data-toggle="tooltip" data-placement="top" data-original-title="Set all unread notifications to read" data-ma-action="portal-notifications-clear"></a>
                    </div>
                </div>

                <!-- All Notifications -->
                <div class="listview__scroll scrollbar-inner" id="notification_list">

                    <!-- Notification Message -->
                    <div class="listview__item nohover__item" v-if="!notifications.length">
                        <div class="listview__content text-center">
                            <p id="notification_container_message"></p>
                        </div>
                    </div>

                    <!-- Notification -->
                    <a v-on:click="notificationLink(index)" class="listview__item" v-bind:style="has_read(notification.hasBeenRead)" v-for="(notification, index) in notifications">
                        <div class="avatar-img avatar-char bg-primary user__img"><i v-bind:class="notification.from_icon"></i></div>
                        <div class="listview__content">
                            <div class="listview__heading">{{notification.from}}</div>
                            <p>{{notification.message}}</p>
                        </div>
                        <div class="actions"><small class="text-gray">{{notification.formatted_datetime}}</small></div>
                    </a>

                    <!-- Show More Action -->
                    <a class="listview__item" v-if="hasNextPage" v-on:click="loadNextPage" id="show_more">
                        <div class="listview__content text-center">
                            <p>Show More</p>
                        </div>
                    </a>

                    <!-- Loading Page Spinner -->
                    <a class="listview__item nohover__item" v-if="loading_next_page">
                        <div class="listview__content text-center">
                            <i class="zmdi zmdi-spinner zmdi-hc-spin"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </li>
</template>

<script>
export default {
    name: "Notifications",
    data: function () {
        return {
            notifications: [],
            headerRgba: '',
            notification_page: 1,
            hasNextPage: false,
            loading_next_page: false
        }
    },
    mounted() {

        // Get the container
        const app = this;

        // Get the header colour as an RGBA
        var headerRgb = $(".header").css('background-color');
        headerRgb = headerRgb.replace('rgb(', '');
        headerRgb = headerRgb.replace(')', '');
        this.headerRgba = 'rgba(' + headerRgb + ', 0.1)';

        // Set the spinner
        $("#notification_container_message").html('<i class="zmdi zmdi-spinner zmdi-hc-spin"></i>');

        // Load all the notifications
        app.loadNotifications();

        // Get the notification bell element
        const notificationBell = $("#notification_bell");

        // Subscribe to Pusher channel
        Echo.channel('notifications').listen('.new', (e) => {

            app.getIcon(e, function (e) {

                // Add notification to top of list
                app.notifications.unshift(e.message);

                // Add the notification notifier
                notificationBell.addClass("top-nav__notify");
            });

        });

        // If the read all icon is clicked
        $("body").on("click", "[data-ma-action='portal-notifications-clear']", function(e) {

            // Stop the propagation
            e.stopPropagation();

            // Read all notifications
            app.readAll();
        });
    },
    methods: {
        readAll: function() {

            // Get the notification bell element
            const notificationBell = $("#notification_bell");

            // Get the read all icon element
            const readAllIcon = $("#read-all");

            // Swap to the spinning icon
            readAllIcon.removeClass('actions__item zmdi zmdi-check-all');
            readAllIcon.addClass('actions__item zmdi zmdi-spinner zmdi-hc-spin');

            // Get the container
            const app = this;

            // Init the notification ID array
            var notificationIds = [];

            // Foreach notification
            $.each(app.notifications, function(index, value) {

                // If the notification has not been read
                if (! app.notifications[index]['hasBeenRead']) {

                    // Add the ID to the array
                    notificationIds.push(app.notifications[index]['id'])
                }
            });

            // If there are no notifications to be read
            if (notificationIds.length === 0) {

                // Switch back to the original icon
                readAllIcon.removeClass('actions__item zmdi zmdi-spinner zmdi-hc-spin');
                readAllIcon.addClass('actions__item zmdi zmdi-check-all');

                // Stop processing
                return;
            }

            // Get the form data
            let formData = {
                _method: 'put',
                notifications: notificationIds
            };

            // POST to the read endpoint
            axios.post('/api/notifications/read', formData)
                .then(function () {

                    // Foreach notification
                    $.each(app.notifications, function(index, value) {

                        // Set has been read to true
                        app.notifications[index]['hasBeenRead'] = true;
                    });

                    // Remove notification notifier
                    notificationBell.removeClass("top-nav__notify");

                    // Switch back to the original icon
                    readAllIcon.removeClass('actions__item zmdi zmdi-spinner zmdi-hc-spin');
                    readAllIcon.addClass('actions__item zmdi zmdi-check-all');
                })
                .catch(function () {

                    // Console error
                    console.error("Could'nt set notification to read.")
                });

        },
        loadNextPage: function(e) {

            // Stop the propagation
            e.stopPropagation();

            // Set the has next page to false
            this.hasNextPage = false;

            // Set the loading next page to true
            this.loading_next_page = true;

            // Add 1 to the current page
            this.notification_page++;

            // Load the next page of notifications
            this.loadNotifications();
        },
        has_read: function(hasBeenRead) {

            // If the notification has not been read
            if (! hasBeenRead) {

                // Return the un-read notification color
                return "background-color:" + this.headerRgba + ';';
            }
        },
        notificationLink: function(index) {

            // Get the notification
            let notification = this.notifications[index];

            // If the notification has already been read
            if (notification['hasBeenRead']) {

                // Send user to endpoint
                window.location.href = notification.link;

                // Stop the processing
                return;
            }

            // Get the form data
            let formData = {
                _method: 'put',
                notifications: [notification.id]
            };

            // POST request to read endpoint
            axios.post('/api/notifications/read', formData)
                .then(function () {

                    // Send user to endpoint
                    window.location.href = notification.link;
                })
                .catch(function (e) {

                    // Console error
                    console.error("Could'nt set notification to read.")
                });
        },
        getIcon: function(e, callback) {

            // Get all paginated notifications for user
            axios.get('/api/notifications/getIcon/' + e.message.from)
                .then(function (response) {

                    e.message.from_icon = response.data;

                    callback(e);

                })
                .catch(function (e) {

                    e.message.from_icon = 'zmdi zmdi-account';

                    callback(e);
                });

        },
        loadNotifications: function() {

            // Get the container
            const app = this;

            // Get all paginated notifications for user
            axios.get('/api/notifications/' + this.notification_page)
                .then(function (response) {

                    // Get the notification bell element
                    const notificationBell = $("#notification_bell");

                    // If no notifications
                    if (! response.data.data.length && app.notification_page === 1) {

                        // Set the message
                        $("#notification_container_message").html('No Notifications');

                        // Stop processing
                        return;
                    }

                    // If there are more pages to load
                    if (response.data.next_page_url !== null) {

                        // Set the has next page to true
                        app.hasNextPage = true;
                    }

                    // Foreach notification from service
                    $.each(response.data.data, function(index, value) {

                        // If a notification is un-read
                        if (!value.hasBeenRead) {

                            // Add the notification notifier
                            notificationBell.addClass("top-nav__notify");
                        }

                        // Add the notifications from the service to the notifications array
                        app.notifications.push(value);
                    });

                    // Set the loading next page to false
                    app.loading_next_page = false;
                })
                .catch(function (e) {

                    // If any errors, set message
                    $("#notification_container_message").html('No Notifications');
                });
        }
    }
}
</script>

<style scoped>
.nohover__item:hover {
    background-color: white !important;
}
</style>
