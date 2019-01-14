# "Clue" Client

The "Clue" client is a WordPress plugin that facilitates logging of basically every action that occurs within WordPress, and then sends that data to the sister node server to be stashed away in a database. This plugin was heavily based on the work of [Pär Thernström's WordPress Simple History](https://github.com/bonny/WordPress-Simple-History), but was refactored and re-architected enormously. Much of the original code has been stripped or re-written but I believe Pär should retain most of the credit.

I deprecated this plugin in favor of simply just using Sentry.io along with a utility plugin I wrote that acts as a wrapper for their PHP library and logs every error to the service. It's not as robust as this plugin out of the box, so I may ultimately repurpose this plugin to use alongside Sentry when I can find the time.


## Getting Started

1. Clone this repo into the `wp-content/plugins` folder of a WordPress installation.
2. If you already have the server running, place your endpoint into the `CLUE_API_ENDPOINT` constant value. This is defined in the root `clue-client.php` file.
3. Along with the endpoint, you'll need to generate an API key using the servers. Then place that key via the plugin admin page in the WordPress dashboard.


## Editing or Adding New Actions

Each action is contained within their own respective file. For example, all actions/events that occur related to users, are declared inside the `core/triggers` directory in a file named `user.class.php`.

Within the trigger classes there is a property called `$lexicon` which contains all of the available actions in this scope and their severity levels. This is where you define, modify, or remove actions.

For example, here is the `User` triggers' `$lexicon` definition..

```php
protected $lexicon= array(
    'description' => 'Triggers when changes are made to users such as logins, logouts, password changes, etc.',
    'capability'  => 'edit_users',
    'actions'     => array(
        'user_login_failed'                  => Severity::WARNING,
        'user_unknown_login_failed'          => Severity::WARNING,
        'user_logged_in'                     => Severity::INFO,
        'user_unknown_logged_in'             => Severity::INFO,
        'user_logged_out'                    => Severity::INFO,
        'user_updated_profile'               => Severity::INFO,
        'user_created'                       => Severity::WARNING,
        'user_deleted'                       => Severity::WARNING,
        'user_password_reset'                => Severity::WARNING,
        'user_requested_password_reset_link' => Severity::INFO,
        'user_session_destroy_others'        => Severity::WARNING,
        'user_session_destroy_everywhere'    => Severity::WARNING,
    ),
);
```
The next step that needs to be done is that the trigger needs to be defined in the `clue-client.php` files `run_clue_client` function. Add the name of the trigger to the `$triggers` array.


## Creating a New Trigger

Triggers are classes that are required to have several methods and a `$lexicon` property. Each trigger inherits a base `Trigger` class that must be extended to work correctly.

To start, it's probably easiest to just clone an existing trigger with minimal modifications...an easy class to do this with is `import.class.php`.

Next, edit the class name to something more appropriate and descriptive.

The `$lexicon` is where the actions and their severity levels are defined. These are important for when an action is send to the log queue. The actions are generally
the names of the WordPress hooks that get called. There are several severity levels you can assign to each action which include:

- EMERGENCY
- ALERT
- CRITICAL
- ERROR
- WARNING
- NOTICE
- INFO 
- DEBUG


## Event Payload

Currently, the Clue Server expects a very specific set of data for each `event` that gets sent. The following fields are required:

```php
array(
    'client'   => '',
    'date'     => '',
    'trigger'  => '',
    'action'   => '',
    'severity' => '',
    'details'  => array(),
    'meta'     => array(),
);
```
Failure to provide any of these fields will result in a `400` status error.


## Tests

There were fairly extensive tests written for this plugin, but since I have not maintained this codebase in around a year, I have forgotten where I "left off." Incidentally none of the tests are executing. I believe this is due to the refactoring efforts that were made towards renaming classes in their current format `.class.php`. 