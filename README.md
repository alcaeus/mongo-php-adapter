# Mongo PHP Adapter

The Mongo PHP Adapter is a userland library designed to act as an adapter 
between applications relying on ext-mongo and the new driver (ext-mongodb).

It provides the API of ext-mongo built on top of mongo-php-library, thus being
compatible with PHP7.

# Stability

This library is not yet stable enough to be used in production. Use at your own
risk.

# Installation

This library requires you to have the mongodb extension installed and conflicts
with the legacy mongo extension.

The preferred method of installing this library is with
[Composer](https://getcomposer.org/) by running the following from your project
root:

    $ composer require "alcaeus/mongo-php-adapter=dev-master"

# Known issues
 - The [Mongo](https://secure.php.net/manual/en/class.mongo.php) class is
 deprecated and was not implemented in this library. If you are still using it
 please update your code to use the new classes.
