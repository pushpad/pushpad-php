# Pushpad: web push notifications

Add web push notifications to your web app using third-party service [Pushpad](https://pushpad.xyz).

Currently push notifications work on the following browsers:

- Chrome (Desktop and Android)
- Firefox (44+)
- Safari

Features:

- users don't need to install any app or plugin 
- notifications are delivered even when the user is not on your website
- you can target specific users or send bulk notifications

## Installation

Download the latest version of this library:

    $ git clone https://github.com/pushpad/pushpad-php.git

Then add this line to your application:

```php
require_once('path/to/pushpad-php/init.php');

```

## Usage

First you need to sign up to Pushpad and create a project there. It takes 1 minute.

Then set your authentication credentials:

```php
Pushpad\Pushpad::$auth_token = '5374d7dfeffa2eb49965624ba7596a09';
Pushpad\Pushpad::$project_id = 123; # set it here or pass it as a param to methods later
```

`auth_token` can be found in the user account settings. 
`project_id` can be found in the project settings on Pushpad. A project is a list of subscriptions. You can set it globally or pass it as a param to methods if your app uses multiple lists (e.g. `Pushpad\Pushpad::path_for($current_user_id, array('project_id' => 123))`, `$notification->deliver_to(user_id, array('project_id' => 123))`).

Let users subscribe to your push notifications: 

```php
<a href="<?= Pushpad\Pushpad::path() ?>">Subscribe anonymous to push notifications</a>

<a href="<?= Pushpad\Pushpad::path_for(current_user_id) ?>">Subscribe current user to push notifications</a>
```

`current_user_id` is an identifier (e.g. primary key in the database) of the user currently logged in on your website.

When a user clicks the link is sent to Pushpad, automatically asked to receive push notifications and redirected back to your website.

Then you can send notifications:

```php
$notification = new Pushpad\Notification(array(
  'body' => "Hello world!",
  'title' => "Website Name", # optional, defaults to your project name
  'target_url' => "http://example.com" # optional, defaults to your project website
));

$notification->deliver_to($user_id);
$notification->deliver_to($user_ids);
$notification->broadcast(); # deliver to everyone
# => array('scheduled' => 12)
```

If no user with that id has subscribed to push notifications, that id is simply ignored.

The methods above return an array: `$res['scheduled']` contains the number of notifications that will be sent. For example if you call `$notification->deliver_to(user)` but the user has never subscribed to push notifications the result will be `array('scheduled' => 0)`.

## License

The gem is available as open source under the terms of the [MIT License](http://opensource.org/licenses/MIT).

