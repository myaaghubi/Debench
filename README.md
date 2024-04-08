# Debench

A small debug/benchmark helper for PHP

![myaaghubi/debench-debench-minimal](screenshot/screenshot-minimal.png)
![myaaghubi/debench-debench-fullsize](screenshot/screenshot-fullsize.png)

# How to use

Use composer:
```shell
composer require myaaghubi/debench
```
Then have it like:
```php
namespace DEBENCH;

require __DIR__ . '/vendor/autoload.php';

// call it from your index.php after autoload 
// then check the webpage with your browser
// $debench = new Debench();
Debench::getInstance();

$st = str_repeat("Debench!", 10000);

// step one
// $debench->newPoint("step one");
Debench::point('step one');

$st .= str_repeat("Debench!", 10000);

// step two
Debench::point("step two");
```
For `minimal` mode:
```php
$debench->setMinimal(false);
```
For `production` mode
```php
// this one is better
$debench = new Debench(false);
// or
$debench->setEnable(false);
```

## License

You are allowed to use this plugin under the terms of the MIT License.

Copyright (C) 2024 Mohammad Yaaghubi
