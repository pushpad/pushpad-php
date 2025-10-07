# Pushpad - Web Push Notifications

![Build Status](https://github.com/pushpad/pushpad-php/workflows/CI/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/pushpad/pushpad-php/v)](//packagist.org/packages/pushpad/pushpad-php)
[![Total Downloads](https://poser.pugx.org/pushpad/pushpad-php/downloads)](//packagist.org/packages/pushpad/pushpad-php)
[![License](https://poser.pugx.org/pushpad/pushpad-php/license)](//packagist.org/packages/pushpad/pushpad-php)

[Pushpad](https://pushpad.xyz) is a service for sending push notifications from websites and web apps. It uses the **Push API**, which is supported by all major browsers (Chrome, Firefox, Opera, Edge, Safari).

Notifications are delivered in real time even when the users are not on your website and you can target specific users or send bulk notifications.

## Installation

### Composer

This package requires PHP 8.0+ with the `curl` extension.

Install the SDK via [Composer](https://getcomposer.org/):

```bash
composer require pushpad/pushpad-php
```

Then include Composer's autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

### Manual installation

Clone the repository and require the bootstrap file in your project:

```bash
git clone https://github.com/pushpad/pushpad-php.git
```

```php
require_once __DIR__ . '/path/to/pushpad-php/init.php';
```

## Getting started

First sign up to Pushpad and create a project.

Configure the SDK with your credentials before you make any API calls:

```php
Pushpad\Pushpad::$authToken = '5374d7dfeffa2eb49965624ba7596a09';
Pushpad\Pushpad::$projectId = 123; // set a default project (optional)
```

- `authToken` can be created in the account settings.
- `projectId` is shown in the project settings. If you work with multiple projects you can pass a different project id to individual method calls instead of configuring the global default.

## Collecting user subscriptions

Use the JavaScript SDK to subscribe users to push notifications (see the [getting started guide](https://pushpad.xyz/docs/pushpad_pro_getting_started)).

When you need to sign a `uid`, generate the HMAC signature with:

```php
$signature = Pushpad\Pushpad::signatureFor((string) $currentUserId);
```

## Sending push notifications

Use `Pushpad\Notification::create()` (or the `send()` alias) to create and send a notification:

```php
$response = Pushpad\Notification::create([
    // required content
    'body' => 'Hello world!',

    // optional fields
    'title' => 'Website Name',
    'target_url' => 'https://example.com',
    'icon_url' => 'https://example.com/assets/icon.png',
    'badge_url' => 'https://example.com/assets/badge.png',
    'image_url' => 'https://example.com/assets/image.png',
    'ttl' => 604800,
    'require_interaction' => true,
    'silent' => false,
    'urgent' => false,
    'custom_data' => '123',
    'actions' => [
        [
            'title' => 'My Button 1',
            'target_url' => 'https://example.com/button-link',
            'icon' => 'https://example.com/assets/button-icon.png',
            'action' => 'myActionName',
        ],
    ],
    'starred' => true,
    'send_at' => (new DateTimeImmutable('+1 hour'))->format(DATE_ATOM),
    'custom_metrics' => ['examples', 'another_metric'],

    // targeting options
    'uids' => ['user-1', 'user-2'],
    'tags' => ['segment1', 'segment2'],
]);
```

- Omit `uids` and `tags` to broadcast to everyone.
- If you set `uids` and some users are not subscribed to notifications, Pushpad ignores them.
- Use boolean expressions inside `tags` for complex segments (e.g. `'zip_code:28865 && !optout:local_events'`).
- Scheduled notifications require an ISO 8601 timestamp in `send_at` (as produced by `DateTimeInterface::format(DATE_ATOM)`).
- You can set default values for most notification fields in the project settings.
- Refer to the [REST API docs](https://pushpad.xyz/docs/rest_api#notifications_api_docs) for more details about the notification fields and their usage.

The response includes useful information:

```php
// Notification ID
$notificationId = $response['id'];

// Estimated number of devices that will receive the notification
// Not available for notifications that use send_at
$estimatedReach = $response['scheduled'];

// Available only if you specify some user IDs (uids) in the request:
// it indicates which of those users are subscribed to notifications.
// Not available for notifications that use send_at
$reachedUids = $response['uids'];

// The time when the notification will be sent.
// Available for notifications that use send_at
$scheduledAt = $response['send_at'];
```

## Getting push notification data

Fetch a single notification and inspect its attributes:

```php
$notification = Pushpad\Notification::find(42);

echo $notification->title; // "Foo Bar"
echo $notification->target_url; // "https://example.com"
echo $notification->ttl; // 604800
echo $notification->created_at; // ISO 8601 string
echo $notification->successfully_sent_count; // 4
echo $notification->opened_count; // 2

// ... and many other attributes
print_r($notification->toArray());
```

List notifications for a project (pagination supported through the `page` query parameter):

```php
$notifications = Pushpad\Notification::findAll(['page' => 1]);

foreach ($notifications as $item) {
    printf("Notification %d: %s\n", $item->id, $item->title);
}
```

Pass the project id as the second argument when you prefer not to rely on the globally configured `Pushpad\Pushpad::$projectId`:

```
Pushpad\Notification::findAll([], projectId: 456);
```

If you need to refresh a previously loaded notification, call `$notification->refresh()` to hydrate the latest data from the API.

## Scheduled notifications

Create a notification that will be sent later:

```php
Pushpad\Notification::create([
    'body' => 'This notification will be sent after 60 seconds',
    'send_at' => (new DateTimeImmutable('+60 seconds'))->format(DATE_ATOM),
]);
```

Cancel a scheduled notification when it is still pending:

```php
$notification = Pushpad\Notification::find(5);
$notification->cancel();
```

## Getting subscription count

Retrieve the number of subscriptions associated with a project, optionally filtered by user IDs or tags:

```php
$total = Pushpad\Subscription::count();
$byUser = Pushpad\Subscription::count(['uids' => ['user1']]);
$byTags = Pushpad\Subscription::count(['tags' => ['sports && travel']]);
$combined = Pushpad\Subscription::count(['uids' => ['user1'], 'tags' => ['sports && travel']], 5);
```

The second argument lets you override the project id if you did not configure `Pushpad\Pushpad::$projectId` or you need to switch project on the fly.

## Getting push subscription data

Fetch subscriptions with optional filters and pagination:

```php
$subscriptions = Pushpad\Subscription::findAll(['tags' => ['sports'], 'page' => 2]);

foreach ($subscriptions as $subscription) {
    echo $subscription->id . PHP_EOL;
    // ...
}
```

Load a specific subscription when you already know its id:

```php
$subscription = Pushpad\Subscription::find(123);

echo $subscription->id;
echo $subscription->endpoint;
echo $subscription->uid;
echo $subscription->tags;
echo $subscription->last_click_at;
echo $subscription->created_at;

// ... and many other attributes
print_r($subscription->toArray());
```

## Updating push subscription data

Although tags and user IDs are usually managed from the JavaScript SDK, you can also update them from server:

```php
$subscriptions = Pushpad\Subscription::findAll(['uids' => ['user1']]);

foreach ($subscriptions as $subscription) {
    $tags = $subscription->tags ?? [];
    $tags[] = 'another_tag';

    $subscription->update([
        'uid' => 'myuser1',
        'tags' => array_values(array_unique($tags)),
    ]);
}
```

## Importing push subscriptions

To import existing subscriptions or seed test data use `Pushpad\Subscription::create()`:

```php
$subscription = Pushpad\Subscription::create([
    'endpoint' => 'https://example.com/push/f7Q1Eyf7EyfAb1',
    'p256dh' => 'BCQVDTlYWdl05lal3lG5SKr3VxTrEWpZErbkxWrzknHrIKFwihDoZpc_2sH6Sh08h-CacUYI-H8gW4jH-uMYZQ4=',
    'auth' => 'cdKMlhgVeSPzCXZ3V7FtgQ==',
    'uid' => 'exampleUid',
    'tags' => ['exampleTag1', 'exampleTag2'],
]);
```

Typically subscriptions are collected from the browser using the [JavaScript SDK](https://pushpad.xyz/docs/javascript_sdk_reference); server-side creation should be reserved for migrations and special workflows.

## Deleting push subscriptions

Delete subscriptions programmatically (use with care, the operation is irreversible):

```php
$subscription = Pushpad\Subscription::find(123);
$subscription->delete();
```

## Managing projects

Projects can also be managed via the API for automation use cases:

```php
$project = Pushpad\Project::create([
    'sender_id' => 123,
    'name' => 'My project',
    'website' => 'https://example.com',
    'icon_url' => 'https://example.com/icon.png',
    'badge_url' => 'https://example.com/badge.png',
    'notifications_ttl' => 604800,
    'notifications_require_interaction' => false,
    'notifications_silent' => false,
]);

$projects = Pushpad\Project::findAll();

$project = Pushpad\Project::find(123);
$project->update(['name' => 'The New Project Name']);
$project->delete();
```

## Managing senders

Senders hold the VAPID credentials used for Web Push:

```php
$sender = Pushpad\Sender::create([
    'name' => 'My sender',
    // omit the keys below to let Pushpad generate them automatically
    // 'vapid_private_key' => '-----BEGIN EC PRIVATE KEY----- ...',
    // 'vapid_public_key' => '-----BEGIN PUBLIC KEY----- ...',
]);

$senders = Pushpad\Sender::findAll();

$sender = Pushpad\Sender::find(987);
$sender->update(['name' => 'The New Sender Name']);
$sender->delete();
```

## License

The library is available as open source under the terms of the [MIT License](http://opensource.org/licenses/MIT).
