Testing
=======

* [Unit tests](#unit-tests)
* [Functional tests](#functional-tests)

Unit tests
----------

Clone this repository, then install its vendors, and invoke PHPUnit:

```bash
$ composer install --dev
$ phpunit --testsuite unit
```

Functional tests
----------------

The bundle also has functional tests against a Varnish instance. The functional
test suite uses PHP’s built-in web server, so it requires PHP 5.4 or higher.

Start a Varnish server and run the functional tests:

```bash
$ phpunit --testsuite functional
```