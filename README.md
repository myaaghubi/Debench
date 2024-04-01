# Debench
<code style="color : #FFD700">Note: There is not much in this project yet, be patient.</code>

A small debug/benchmark helper for PHP

# How to use

Use composer:
```shell
composer require myaaghubi/debench
```
Then have it like:
```php
require __DIR__ . '/vendor/autoload.php';

// call it from your index.php
// before anything then check
// the webpage with your browser
$debench = new DEBENCH\Debench();

sleep(1);

// after a seconds
$debench->newPoint("step one");

sleep(2);

// after two more second
$debench->newPoint("step two");
```
For production mode just put a flag:
```php
$debench = new DEBENCH\Debench(false);
```

## License

You are allowed to use this plugin under the terms of the MIT License.

Copyright (C) 2024 Mohammad Yaaghubi
