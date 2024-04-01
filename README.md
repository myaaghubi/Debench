# Debench
<code style="color : #FFD700">Note: There is not much for this version yet, be patient.</code>

A small debug/benchmark helper for PHP

# How to use

Use composer:
```shell
composer require myaaghubi/debench
```
Then have it like:
```php
require __DIR__ . '/vendor/autoload.php';

$debench = new DEBENCH\Debench(true, 'theme');
for ($i=0; $i<3; $i++) {
    sleep(1);
    $debench->newPoint();
}
```

## License

You are allowed to use this plugin under the terms of the MIT License.

Copyright (C) 2024 Mohammad Yaaghubi
