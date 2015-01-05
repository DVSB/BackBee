BackBee core
============

[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/backbee/BackBee?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


**BackBee core** is the core part of an open source PHP CMS built on top of Symfony & Doctrine 2 components.
The main concept behind BackBee is that "your backend is your frontend", this CMS is
focused on the continuous improvement of content contribution.

See also [BackBee website](http://backbee.com/what-is-backbee/10-reasons-to-use-backbuilder5)

Requirements
------------

BackBee core is only supported on PHP 5.4 and up.
BackBee also need ``mbstring``, ``mcrypt``, ``pdo`` PHP extensions.

Installation
------------

The recommended way to install BackBee is through
[Composer](http://getcomposer.org/):

``` json
{
    "require": {
        "backbee/backbee": "@stable"
    }
}
```

**Protip:** you should browse the
[`backbee/backbee`](https://packagist.org/packages/backbee/backbee)
page to choose a stable version to use, avoid the `@stable` meta constraint.


Usage
-----



Unit Tests
----------

Setup the test suite using Composer:

    $ composer install --dev

Run it using PHPUnit:

    $ phpunit


Contributing
------------

See CONTRIBUTING file.


Credits
-------

* Some parts of this library are inspired by:

    * [Symfony](http://github.com/symfony/symfony) framework;


License
-------

BackBee is released under the GPL v3 License. See the bundled LICENSE file for details.