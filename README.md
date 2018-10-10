# WP Monolog

> WordPress plugin that enables logging capabilities for theme and/or plugin development.

## Description
WP Monolog is a very lightweight plugin which purpose is to integrate [Monolog](https://github.com/Seldaek/monolog) into WordPress, allowing theme or plugin developers to log messages to files, for debugging purposes, or to email, to alert on failures.

### Log Level Supported

Monolog supports the logging levels described by RFC 5424.

- DEBUG (100): Detailed debug information.
- INFO (200): Interesting events. Examples: User logs in, SQL logs.
- NOTICE (250): Normal but significant events.
- WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
- ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
- CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
- ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
- EMERGENCY (600): Emergency: system is unusable.

The log level logging can be adjusted from the WordPress Admin ***WordPress Admin > Tools > WP Monolog*** or define `WP_MONOLOG_LOG_LEVEL` in wp-config

## Usage
A global variable named `$logger` is available and it's an instance of `Monolog\Logger`, so you can use it as you would normally use monolog:

```php
<?php
global $logger;
$logger->addInfo("An info message");
$logger->addWarning("A warning message");
$logger->addError("An error message");
```

Another global variable exists which is `$wp_monolog`, this instance is helpful at the moment to get new `Monolog\Logger` instances on demand. This is useful for example when you have different features spread across your theme or plugins and you want to create specific log records per feature for better error/warning/info tracking.

**Example:**

```php
<?php
    
function greet(){
  global $wp_monolog;
  $greet_logger = $wp_monolog->getLoggerInstance('GreetLog');
  try{
    $greet_logger->addInfo('About to greet');
    sayHello();
  } catch ( Exception $e ){
    $greet_logger->addError('Oops, something went wrong with the greeting message');
  }
}
    
function replyGreeting(){
  global $wp_monolog;
  $reply_greeting_logger = $wp_monolog->getLoggerInstance('ReplyGreetingLog');
  $reply_greeting_logger->addInfo('Replying greeting');
  echo "Hello yourself!";
}
```

The above examples will result in a log record similar to this (in order of appearance):

```
[yyyy-mm-dd hh:ii:ss] GreetLog.INFO: Message: About to greet
[yyyy-mm-dd hh:ii:ss] GreetLog.ERROR: Message: Oops, something went wrong with the greeting message
[yyyy-mm-dd hh:ii:ss] ReplyGreetingLog.INFO: Message: Replying greeting
```
## Configuration

**Info** logs will be sent to the 'default' stream handler, which is a log file available at `wp-content/plugins/wp-monolog/log/yy-mm-dd_wp_monolog.log`.

**Error** logs will be sent by email to the site administrator. You can change this on the settings page of the plugin. ***WordPress Admin > Tools > WP Monolog***
