## Plug-and-play logging for artisan commands and scheduled jobs

[![Latest Stable Version](https://poser.pugx.org/sofa/laravel-artisan-log/v/stable?format=flat-square)](https://packagist.org/packages/sofa/laravel-artisan-log)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/jarektkaczyk/artisan-log/test?label=tests)
[![Total Downloads](https://img.shields.io/packagist/dt/sofa/laravel-artisan-log.svg?style=flat-square)](https://packagist.org/packages/sofa/laravel-artisan-log)

### Installation

```shell script
composer require sofa/laravel-artisan-log

# Optionally publish configuration to customize behavior:
php artisan vendor:publish --provider="Sofa\ArtisanLog\ArtisanLogServiceProvider"
```

**Requires** PHP7.4+ and Laravel 7+

This package provides a super simple logging functionality for chosen artisan commands and scheduled jobs.

By default it will start logging in the default channel your app is using:
```
[2020-05-16 22:00:01] production.INFO: [artisan scheduled starting] reminders:some-reminder
[2020-05-16 22:00:01] production.INFO: [artisan scheduled finished] reminders:some-reminder
[2020-05-16 22:00:01] production.INFO: [artisan starting] reminders:another-reminder
[2020-05-16 22:00:02] production.INFO: [artisan finished] reminders:another-reminder
[2020-05-16 23:00:02] production.INFO: [artisan starting] reminders:another-reminder
[2020-05-16 23:00:02] production.INFO: [artisan failed with exit code: 12] reminders:another-reminder
...
```

[Configuration](https://github.com/jarektkaczyk/artisan-log/blob/master/config/artisan_log.php) file contains references and examples how you may want to customize its behavior.
