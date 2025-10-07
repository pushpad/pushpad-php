# Upgrading to version 3.x

This version is a major rewrite of the library and adds support for the full REST API, including Notifications, Subscriptions, Projects and Senders.

This version has some breaking changes:

- Use camelCase for configurations and methods, in particular you should rename `Pushpad\Pushpad::$auth_token` to `Pushpad\Pushpad::$authToken`, `Pushpad\Pushpad::$project_id` to `Pushpad\Pushpad::$projectId`, `Pushpad\Pushpad::signature_for` to `Pushpad\Pushpad::signatureFor`.
- `$notification->deliver_to` and `$notification->broadcast` were removed. Instead you should use `Pushpad\Notification::create()` (or the `send()` alias).
- When you call `Pushpad\Notification::create()` with the `send_at` option, you should pass a ISO 8601 string. For example, you can use `(new DateTimeImmutable('+1 hour'))->format(DATE_ATOM)`.
