# Edge Module

Edge is a library of useful classes designed to aid the creation of Web Applications.

## Installation
The recommended way to install
[`outeredge/edge-module`](https://packagist.org/packages/outeredge/edge-module) is through
[composer](http://getcomposer.org/):

```sh
php composer.phar require outeredge/edge-module
```

You can then enable this module in your `config/application.config.php` by adding
`Edge` to the `modules` key:

```php
// ...
    'modules' => array(
        // ...
        'Edge'
    ),
```

### Requirements
 - PHP 7.0 or higher
