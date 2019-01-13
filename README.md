# Pushpad - Web Push Notifications
 
[Pushpad](https://pushpad.xyz) is a service for sending push notifications from your web app. It supports the **Push API** (Chrome, Firefox, Opera, Edge) and **APNs** (Safari).

Features:

- notifications are delivered even when the user is not on your website
- users don't need to install any app or plugin
- you can target specific users or send bulk notifications

## Installation

### Composer

You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require pushpad/pushpad-php
```

To use the bindings, use Composer's autoload:

```php
require_once('vendor/autoload.php');
```

### Manual Installation

Download the latest version of this library:

    $ git clone https://github.com/pushpad/pushpad-php.git

Then add this line to your application:

```php
require_once('path/to/pushpad-php/init.php');

```

## Getting started

First you need to sign up to Pushpad and create a project there.

Then set your authentication credentials:

```php
Pushpad\Pushpad::$auth_token = '5374d7dfeffa2eb49965624ba7596a09';
Pushpad\Pushpad::$project_id = 123; # set it here or pass it as a param to methods later
```

- `auth_token` can be found in the user account settings. 
- `project_id` can be found in the project settings. If your application uses multiple projects, you can pass the `project_id` as a param to methods (e.g. `$notification->deliver_to(user_id, array('project_id' => 123))`).

## Collecting user subscriptions to push notifications

You can subscribe the users to your notifications using the Javascript SDK, as described in the [getting started guide](https://pushpad.xyz/docs/pushpad_pro_getting_started).

If you need to generate the HMAC signature for the `uid` you can use this helper:

```php
Pushpad\Pushpad::signature_for($current_user_id);
```

## Sending push notifications

```php
$notification = new Pushpad\Notification(array(
  'body' => "Hello world!",
  'title' => "Website Name", # optional, defaults to your project name
  'target_url' => "http://example.com", # optional, defaults to your project website
  'icon_url' => "http://example.com/assets/icon.png", # optional, defaults to the project icon
  'image_url' => "http://example.com/assets/image.png", # optional, an image to display in the notification content
  'ttl' => 604800, # optional, drop the notification after this number of seconds if a device is offline
  'require_interaction' => true, # optional, prevent Chrome on desktop from automatically closing the notification after a few seconds
  'urgent' => false, # optional, enable this option only for time-sensitive alerts (e.g. incoming phone call)
  'custom_data' => "123", # optional, a string that is passed as an argument to action button callbacks
  # optional, add some action buttons to the notification
  # see https://pushpad.xyz/docs/action_buttons
  'actions' => array(
    array(
      'title' => "My Button 1",
      'target_url' => "http://example.com/button-link", # optional
      'icon' => "http://example.com/assets/button-icon.png", # optional
      'action' => "myActionName" # optional
    )
  ),
  'starred' => true, # optional, bookmark the notification in the Pushpad dashboard (e.g. to highlight manual notifications)
  # optional, use this option only if you need to create scheduled notifications (max 5 days)
  # see https://pushpad.xyz/docs/schedule_notifications
  'send_at' => strtotime('2016-07-25 10:09'), # use a function like strtotime or time that returns a Unix timestamp
  # optional, add the notification to custom categories for stats aggregation
  # see https://pushpad.xyz/docs/monitoring
  'custom_metrics' => array('examples', 'another_metric') # up to 3 metrics per notification
));

# deliver to a user
$notification->deliver_to($user_id);

# deliver to a group of users
$notification->deliver_to($user_ids);

# deliver to some users only if they have a given preference
# e.g. only $users who have a interested in "events" will be reached
$notification->deliver_to($users, ["tags" => ["events"]]);

# deliver to segments
# e.g. any subscriber that has the tag "segment1" OR "segment2"
$notification->broadcast(["tags" => ["segment1", "segment2"]]);

# you can use boolean expressions 
# they must be in the disjunctive normal form (without parenthesis)
$notification->broadcast(["tags" => ["zip_code:28865 && !optout:local_events || friend_of:Organizer123"]]);
$notification->deliver_to($users, ["tags" => ["tag1 && tag2", "tag3"]]); # equal to "tag1 && tag2 || tag3"

# deliver to everyone
$notification->broadcast(); 
```

You can set the default values for most fields in the project settings. See also [the docs](https://pushpad.xyz/docs/rest_api#notifications_api_docs) for more information about notification fields.

If you try to send a notification to a user ID, but that user is not subscribed, that ID is simply ignored.

The methods above return an array: 

- `'id'` is the id of the notification on Pushpad
- `'scheduled'` is the estimated reach of the notification (i.e. the number of devices to which the notification will be sent, which can be different from the number of users, since a user may receive notifications on multiple devices)
- `'uids'` (`deliver_to` only) are the user IDs that will be actually reached by the notification because they are subscribed to your notifications. For example if you send a notification to `['uid1', 'uid2', 'uid3']`, but only `'uid1'` is subscribed, you will get `['uid1']` in response. Note that if a user has unsubscribed after the last notification sent to him, he may still be reported for one time as subscribed (this is due to the way the W3C Push API works).

## License

The library is available as open source under the terms of the [MIT License](http://opensource.org/licenses/MIT).

