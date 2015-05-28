# CakePHP Compressor
Helper to combine and minify javascript and css files. It also minifies html output.

## Dependencies ##
- PHP 5.4
- CakePHP 2.6.3
- Mrclay's Minify!

## Instalation ##
* Assuming you have at least PHP 5.4 (for the array bracket annotation)
* Assuming you already have at least CakePHP 2.6.3 running (2.6.3 is what i've tested with, might also work for previous versions)
* Run composer (as per **composer.json** included) to install [Mrclay's Minify](https://github.com/mrclay/minify)
* Assuming you have installed Minify in `your-project/lib/vendor/mrclay` open your `Controller/AppController.php` and include composer's autoload `include(ROOT . DS . 'lib/vendor/autoload.php');`

## Actual Usage ##
First, open up your `Controller/AppController.php` and add Compressor to your helper variable
```php
public $helpers = ['Compressor'];
```

#### Configuration ####
The default configuration of the helper is as follows:
```php
// default conf
public $settings = [
    'html' => [
        'compression' => true
    ], 'css' => [
        'path' => '/cache-css', // without trailing slash
        'compression' => true
    ], 'js' => [
        'path' => '/cache-js', // without trailing slash
        'compression' => true,
        'async' => true
]];
```
You can of course overwrite any of the options to when you include it in your `Controller/AppController.php` like so:
```php
public $helpers = ['Compressor' => [
    'html' => [
        'compression' => false
    ], 'css' => [
        'path' => '/my-custom-css-cache-directory',
    ], 'js' => [
        'async' => false
    ]
]];
```
#### Minify HTML output ####
Including the helper in your `Controller/AppController.php` will automatically minify your HTML output assuming you're in production mode: `Configure::write('debug', 0);`

#### Combine and Minify css files ####
The standard way of including css files in your cake project would be to use the HTML helper hence you would have to write something like this `$this->Html->css('site')` where `site.css` is located in `webroot/css/`. If you have multiple css files to include you would probably write something like `$this->Html->css(['site', 'store', 'pagination'])`.

To combine your css files into 1 minified file you need to replace your syntax a little bit:
```php
$this->Compressor->style(['site', 'store', 'pagination']);
$this->Compressor->fetch('style');
```
The `fetch` function actually outputs the generated css file. You should only call this function once in your layout file. You can however call the first function `style` how many times you want and wherever you like, in the layout, in a view file or in an element.

#### Combine and Minify js files ####
This is almost the same as combining and minifying css files with the exception of syntax change:
```php
$this->Compressor->script(['jquery.min', 'frontend']);
$this->Compressor->fetch('script');
```

## Auto-Versioning ##
Upon updating any css or js file in development, a new cache file will be generated on production so you can stop worrying about clients pestering you about not seeing any changes or asking you to explain again how to clear the cache of their outdated browser :P

## Important ##
The Compressor helper does not support combining and minifying off-site resources (such as those from CDNs). Those files are already minifed and you should include them using the standard cake way. For example including jquery from google's CDN should be done like this:
```php
$this->Html->script('//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js')
```

## Debug ##
Of course, by default the combination and minification of resources works only when you're in production mode: `Configure::write('debug', 0);`. But if you wish to see if the helper works without going in full fledge production mode you can use a little switch to simulate a live environment. All you have to do is to add a second parameter on the fetch function and set it to true, like so:
```php
$this->Compressor->fetch('style', true);
$this->Compressor->fetch('script', true);
```