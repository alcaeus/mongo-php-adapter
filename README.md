# Mongo PHP Adapter

[![Build Status](https://travis-ci.org/alcaeus/mongo-php-adapter.svg?branch=master)](https://travis-ci.org/alcaeus/mongo-php-adapter)
[![Code Coverage](https://scrutinizer-ci.com/g/alcaeus/mongo-php-adapter/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/alcaeus/mongo-php-adapter/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alcaeus/mongo-php-adapter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alcaeus/mongo-php-adapter/?branch=master)

The Mongo PHP Adapter is a userland library designed to act as an adapter
between applications relying on ext-mongo and the new driver (`ext-mongodb`).

It provides the API of ext-mongo built on top of mongo-php-library, thus being
compatible with PHP 7.

# Goal

This library aims to provide a compatibility layer for applications that rely on
libraries using ext-mongo (e.g. [Doctrine ODM](https://github.com/doctrine/mongodb-odm))
but want to migrate to PHP 7 or HHVM on which `ext-mongo` will not run.

You should not be using this library if you do not rely on a library using
`ext-mongo`. If you are starting a new project, please check out [mongodb/mongodb](https://github.com/mongodb/mongo-php-library).

# Installation

This library requires you to have the `mongodb` extension installed, and it
conflicts with the legacy `mongo` extension.

The preferred method of installing this library is with
[Composer](https://getcomposer.org/) by running the following from your project
root:

    $ composer require alcaeus/mongo-php-adapter

If your project already has a dependency on `ext-mongo`, the command above may
not work. This is due to a bug in composer, see [composer/composer#5030](https://github.com/composer/composer/issues/5030).

To fix this, you can use the `--ignore-platform-reqs` switch when running the
above command, or when running `composer update` with no `composer.lock` file
present.

# Known issues

## Return values and exceptions

Some methods may not throw exceptions with the same exception messages as their
counterparts in `ext-mongo`. Do not rely on exception messages being the same.

Methods that return a result array containing a `connectionId` field will always
return `0` as connection ID.

## Errors

All errors and warnings triggered by `ext-mongo` are triggered as `E_USER_WARNING`
and `E_USER_ERROR` because `trigger_error` doesn't accept the `E_WARNING` and
`E_USER` codes. If you rely on these error codes in your error handling routines,
please update your code accordingly.

## Serialization of objects
Serialization of any Mongo* objects (e.g. MongoGridFSFile, MongoCursor, etc.)
will not work properly. The objects can be serialized but are not usable after
unserializing them.

## Mongo

 - The Mongo class is deprecated and was not implemented in this library. If you
 are still using it please update your code to use the new classes.

## MongoLog

 - The [MongoLog](http://php.net/manual/en/class.mongolog.php) class does not
 log anything because the underlying driver does not offer a method to retrieve
 this data.

## MongoClient

 - The [connect](https://php.net/manual/en/mongoclient.connect.php) and
 [close](https://secure.php.net/manual/en/mongoclient.close.php) methods are not
 implemented because the underlying driver connects lazily and does not offer
 methods for connecting disconnecting.
 - The [getConnections](https://secure.php.net/manual/en/mongoclient.getconnections.php)
 method is not implemented because the underlying driver does not offer a method
 to retrieve this data.
 - The [killCursor](https://php.net/manual/en/mongoclient.killcursor.php) method
 is not yet implemented.

## MongoDB
 - The [authenticate](https://secure.php.net/manual/en/mongodb.authenticate.php)
 method is not supported. To connect to a database with authentication, please
 supply the credentials using the connection string.
 - The `$cmd` collection cannot be used due to an issue in the underlying driver.
 To run commands, use the [command](https://secure.php.net/manual/en/mongodb.command.php)
 method instead of querying the virtual `$cmd` collection.

## MongoCollection

 - The [insert](https://php.net/manual/en/mongocollection.insert.php),
 [batchInsert](https://php.net/manual/en/mongocollection.batchinsert.php),
 and [save](https://php.net/manual/en/mongocollection.save.php)
 methods take the first argument by reference. While the original API does not
 explicitely specify by-reference arguments it does add an ID field to the
 objects and documents given.
 - The [parallelCollectionScan](https://php.net/manual/en/mongocollection.parallelcollectionscan.php)
 method is not yet implemented.

## MongoCursor
 - The [info](https://php.net/manual/en/mongocursor.info.php) method does not
 reliably fill all fields in the cursor information. This includes the `numReturned`
 and `server` keys once the cursor has started iterating. The `numReturned` field
 will always show the same value as the `at` field. The `server` field is lacking
 authentication information.
 - The [setFlag](https://php.net/manual/en/mongocursor.setflag.php)
 method is not yet implemented.
 - The [timeout](https://php.net/manual/en/mongocursor.timeout.php) method will
 not change any query options. Client-side timeouts are no longer supported by
 the new driver. Use the maxTimeMS setting as a replacement.

## MongoCommandCursor
 - The [createFromDocument](https://php.net/manual/en/mongocommandcursor.createfromdocument.php)
 method is not yet implemented.
 - The [info](https://php.net/manual/en/mongocommandcursor.info.php) method does not
 reliably fill all fields in the cursor information. This includes the `at`, `numReturned`,
 `firstBatchAt` and `firstBatchNumReturned` fields. The `at` and `numReturned`
 fields always return 0 for compatibility to MongoCursor. The `firstBatchAt` and
 `firstBatchNumReturned` fields will contain the same value, which is the internal
 position of the iterator.
