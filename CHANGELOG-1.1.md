CHANGELOG for 1.1.x
===================

This changelog references the relevant changes done in minor version updates.

1.1.11 (2019-11-11)
-------------------

All issues and pull requests under this release may be found under the
[1.1.11](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.11)
milestone.

 * [#263](https://github.com/alcaeus/mongo-php-adapter/pull/263) fixes test
 failures on PHP 7.4.
 * [#262](https://github.com/alcaeus/mongo-php-adapter/pull/262) fixes a memory
 leak due to generators that aren't freed.

1.1.10 (2019-11-06)
-------------------

All issues and pull requests under this release may be found under the
[1.1.10](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.10)
milestone.

 * [#261](https://github.com/alcaeus/mongo-php-adapter/pull/261) fixes missing
 interface implementations in cursor classes.
 * [#260](https://github.com/alcaeus/mongo-php-adapter/pull/260) fixes issues
 when running against MongoDB 4.2 or `ext-mongodb` 1.6.
 * [#259](https://github.com/alcaeus/mongo-php-adapter/pull/259) fixes issues on
 PHP 7.3 due to `MongoCursor` not implementing `Countable`.

1.1.9 (2019-08-07)
------------------

All issues and pull requests under this release may be found under the
[1.1.9](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.9)
milestone.

 * [#255](https://github.com/alcaeus/mongo-php-adapter/pull/255) fixes inserting
 documents with identifiers PHP considers empty.

1.1.8 (2019-07-14)
------------------

All issues and pull requests under this release may be found under the
[1.1.8](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.8)
milestone.

 * [#253](https://github.com/alcaeus/mongo-php-adapter/pull/253) fixes wrong
 handling of `ArrayObject` instances in `MongoCollection::insert`.

1.1.7 (2019-04-06)
------------------

All issues and pull requests under this release may be found under the
[1.1.7](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.7)
milestone.

 * [#250](https://github.com/alcaeus/mongo-php-adapter/pull/250) fixes type
 conversion when passing write concern to `MongoClient` via URL arguments.


1.1.6 (2019-02-08)
------------------

All issues and pull requests under this release may be found under the
[1.1.6](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.6)
milestone.

 * [#244](https://github.com/alcaeus/mongo-php-adapter/pull/244) fixes a null
 access when converting exceptions.
 * [#236](https://github.com/alcaeus/mongo-php-adapter/pull/236) allows using
 `0` as key in documents.
 * [#234](https://github.com/alcaeus/mongo-php-adapter/pull/234) removes an
 invalid attribute from phpunit.xml.


1.1.5 (2018-03-05)
-----------------

All issues and pull requests under this release may be found under the
[1.1.5](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.5)
milestone.

 * [#222](https://github.com/alcaeus/mongo-php-adapter/pull/222) fixes handling
 of `monodb+srv` URLs in `MongoClient`.

1.1.4 (2018-01-24)
------------------

All issues and pull requests under this release may be found under the
[1.1.4](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.4)
milestone.

 * [#214](https://github.com/alcaeus/mongo-php-adapter/pull/214) fixes the
return values of MongoBatch calls with unacknowledged write concerns.

1.1.3 (2017-09-24)
------------------

All issues and pull requests under this release may be found under the
[1.1.3](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.3)
milestone.

 * [#203](https://github.com/alcaeus/mongo-php-adapter/pull/203) fixes the
 detection of empty keys in update queries which were sometimes not properly
 handled.
 * [#187](https://github.com/alcaeus/mongo-php-adapter/pull/187) forces a
 primary read preference to certain commands that need to write data.
 * [#195](https://github.com/alcaeus/mongo-php-adapter/pull/195) fixes a wrong
 calculation leading to a wrong `updatedExisting` field in the result of an
 `update` query.
 * [#193](https://github.com/alcaeus/mongo-php-adapter/pull/193) fixes leaking
 new driver exceptions when calling `MongoClient::getHosts`. 
 * [#191](https://github.com/alcaeus/mongo-php-adapter/pull/191) fixes cursor
 iteration when calling `hasNext` before resetting the cursor.
 * [#189](https://github.com/alcaeus/mongo-php-adapter/pull/189) fixes type
 conversion for a `query` passed to the `explain` command. 
 * [#186](https://github.com/alcaeus/mongo-php-adapter/pull/186) fixes errors when
 using the 1.3 version of `ext-mongodb`. It also fixes an issue where new fields
 in `MongoDB::listCollections` were not properly reported.

1.1.2 (2017-08-04)
------------------

All issues and pull requests under this release may be found under the
[1.1.2](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.2)
milestone.

 * [#184](https://github.com/alcaeus/mongo-php-adapter/pull/184) fixes an invalid
 call to `count` which causes warnings on PHP 7.2.

1.1.1 (2017-06-30)
------------------

All issues and pull requests under this release may be found under the
[1.1.1](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.1)
milestone.

 * [#176](https://github.com/alcaeus/mongo-php-adapter/pull/176) fixes exception
 codes in `MongoGridFSException` exceptions that occur during GridFS operations.

1.1.0 (2017-05-13)
------------------

All issues and pull requests under this release may be found under the
[1.1.0](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.1.0)
milestone.

 * [#173](https://github.com/alcaeus/mongo-php-adapter/pull/173) adds tests for
 authentication options in `MongoClient`.
 * [#168](https://github.com/alcaeus/mongo-php-adapter/pull/168) adds support for
 `MongoCursor::explain()`.
 * [#128](https://github.com/alcaeus/mongo-php-adapter/pull/128) removes support
 for PHP 5.5.
 * [#127](https://github.com/alcaeus/mongo-php-adapter/pull/127) reads the `code`
 and `scope` properties of `MongoDB\BSON\Javascript` objects when converting them
 to `MongoCode` objects.
