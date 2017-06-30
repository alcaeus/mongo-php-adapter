CHANGELOG for 1.1.x
===================

This changelog references the relevant changes done in minor version updates.

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
