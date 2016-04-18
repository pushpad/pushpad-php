# Pushpad - Web Push Notifications

Add native push notifications to your web app using [Pushpad](https://pushpad.xyz).

Features:

- notifications are delivered even when the user is not on your website
- users don't need to install any app or plugin
- you can target specific users or send bulk notifications

Currently push notifications work on the following browsers:

- Chrome (Desktop and Android)
- Firefox (44+)
- Safari

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

Pushpad offers two different ways to collect subscriptions. [Learn more](https://pushpad.xyz/docs#simple_vs_custom_api_docs)

### Custom API

Choose the Custom API if you want to use Javascript for a seamless integration. [Read the docs](https://pushpad.xyz/docs#custom_api_docs)

If you need to generate the HMAC signature for the `uid` you can use this helper:

```php
Pushpad\Pushpad::signature_for($current_user_id);
```

### Simple API

Add a link to let users subscribe to push notifications: 

```php
<a href="<?= Pushpad\Pushpad::path() ?>">Subscribe anonymous to push notifications</a>

<a href="<?= Pushpad\Pushpad::path_for(current_user_id) ?>">Subscribe current user to push notifications</a>
```

`current_user_id` is an identifier (e.g. primary key in the database) of the user currently logged in on your website.

When a user clicks the link is sent to Pushpad, automatically asked to receive push notifications and redirected back to your website.

## Sending push notifications

```php
$notification = new Pushpad\Notification(array(
  'body' => "Hello world!", # max 90 characters
  'title' => "Website Name", # optional, defaults to your project name, max 30 characters
  'target_url' => "http://example.com" # optional, defaults to your project website
));

# deliver to a user
$notification->deliver_to($user_id);

# deliver to a group of users
$notification->deliver_to($user_ids);

# deliver to everyone
$notification->broadcast();
# => array('scheduled' => 12)
```

If no user with that id has subscribed to push notifications, that id is simply ignored.

The methods above return an array: `'scheduled'` is the number of devices to which the notification will be sent.

## License

The library is available as open source under the terms of the [MIT License](http://opensource.org/licenses/MIT).

