CHANGELOG for 1.0.x
===================

This changelog references the relevant changes done in minor version updates.

1.0.11 (2017-04-27)
-------------------

All issues and pull requests under this release may be found under the
[1.0.11](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.11)
milestone.

 * [#170](https://github.com/alcaeus/mongo-php-adapter/pull/170) fixes a 
 `MongoCursor` object passing a `batchSize` of 0 by default, causing errors in
 sharded setups.

1.0.10 (2017-03-29)
-------------------

All issues and pull requests under this release may be found under the
[1.0.10](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.10)
milestone.

 * [#163](https://github.com/alcaeus/mongo-php-adapter/pull/163) fixes an error
 when dealing with milliseconds with leading zeroes in `MongoDate` objects.

1.0.9 (2017-01-29)
------------------

All issues and pull requests under this release may be found under the
[1.0.9](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.9)
milestone.

 * [#157](https://github.com/alcaeus/mongo-php-adapter/pull/157) fixes a regression
 introduced in 1.0.8 when using query projection with numeric field names.
 * [#155](https://github.com/alcaeus/mongo-php-adapter/pull/155) fixes the handling
 of BSON types when converting legacy types to BSON types.
 * [#154](https://github.com/alcaeus/mongo-php-adapter/pull/154) makes the `options`
 parameter in `MongoDB::createCollection` optional.

1.0.8 (2017-01-11)
------------------

All issues and pull requests under this release may be found under the
[1.0.8](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.8)
milestone.

 * [#151](https://github.com/alcaeus/mongo-php-adapter/pull/151) allows using
 boolean values when passing a write concern option to an insert or update. While
 never documented, this was happily accepted by the legacy driver.
 * [#150](https://github.com/alcaeus/mongo-php-adapter/pull/150) adds missing
 options to `MongoCollection::indexInfo`.
 * [#149](https://github.com/alcaeus/mongo-php-adapter/pull/149) fixes calls to
 `MongoDB::getDBRef` with references containing a `$db` field with a different
 value than the current database.
 * [#145](https://github.com/alcaeus/mongo-php-adapter/pull/145) fixes query
 projections in the legacy syntax. While never documented, this was happily
 accepted by the legacy driver.

1.0.7 (2016-12-18)
------------------

All issues and pull requests under this release may be found under the
[1.0.7](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.7)
milestone.

 * [#139](https://github.com/alcaeus/mongo-php-adapter/pull/139) fixes a wrong
 error code in calls to `trigger_error`.

1.0.6 (2016-10-07)
------------------

All issues and pull requests under this release may be found under the
[1.0.6](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.6)
milestone.

 * [#126](https://github.com/alcaeus/mongo-php-adapter/pull/126) fixes a class
 name that was improperly capitalized.
 * [#130](https://github.com/alcaeus/mongo-php-adapter/pull/130) fixes JSON
 serialization of `MongoId` objects.

1.0.5 (2016-07-03)
------------------

All issues and pull requests under this release may be found under the
[1.0.5](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.5)
milestone.

 * [#117](https://github.com/alcaeus/mongo-php-adapter/pull/117) adds a missing
 flag to indexes when calling `MongoCollection::getIndexInfo`.
 * [#120](https://github.com/alcaeus/mongo-php-adapter/pull/120) throws the proper
 `MongoWriteConcernException` when encountering bulk write errors.
 * [#122](https://github.com/alcaeus/mongo-php-adapter/pull/122) fixes an error in
 `MongoCollection::findAndModify` when specifying both the `update` parameter as
 well as the `update` option.

1.0.4 (2016-06-22)
------------------

All issues and pull requests under this release may be found under the
[1.0.4](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.4)
milestone.

 * [#115](https://github.com/alcaeus/mongo-php-adapter/pull/115) fixes an error
 where using the alternate syntax for `MongoCollection::aggregate` would lead to
 empty aggregation pipelines
 * [#116](https://github.com/alcaeus/mongo-php-adapter/pull/116) fixes a bug
 where read preference and write concern was not applied if it was passed in the
 constructor.

1.0.3 (2016-04-13)
------------------

All issues and pull requests under this release may be found under the
[1.0.3](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.3)
milestone.

 * [#96](https://github.com/alcaeus/mongo-php-adapter/pull/96) fixes errors when
 calling `count` on a cursor that has been iterated fully. The fix removes a
 performance improvement when calling `count` on a cursor that has been opened.
 `MongoCursor::count` now always re-issues a `count` command to the server.
 * [#98](https://github.com/alcaeus/mongo-php-adapter/pull/98) fixes an error
 where using BSON types in a query projection would result in wrong results.
 * [#99](https://github.com/alcaeus/mongo-php-adapter/pull/99) ensures that the
 `sec` and `usec` properties for `MongoDate` are cast to int.

1.0.2 (2016-04-08)
------------------

All issues and pull requests under this release may be found under the
[1.0.2](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.2)
milestone.

 * [#90](https://github.com/alcaeus/mongo-php-adapter/pull/90) ensures that database
 and collection names are properly cast to string on creation.
 * [#94](https://github.com/alcaeus/mongo-php-adapter/pull/94) fixes an error in
 `MongoCursor::hasNext` that led to wrong data being returned.

1.0.1 (2016-04-01)
------------------

All issues and pull requests under this release may be found under the
[1.0.1](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.1)
milestone.

 * [#85](https://github.com/alcaeus/mongo-php-adapter/pull/85) fixes calls to
 `MongoCollection::count` using the legacy syntax of providing `skip` and `limit`
 arguments instead of an `options` array.
 * [#88](https://github.com/alcaeus/mongo-php-adapter/pull/88) fixes an error
 where a call to `MongoCollection::distinct` with a query did not convert legacy
 BSON types to the new driver types.


1.0.0 (2016-03-18)
------------------

All issues and pull requests under this release may be found under the
[1.0.0](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.0)
milestone.

 * [#74](https://github.com/alcaeus/mongo-php-adapter/pull/74) fixes running an
 aggregation command and returning a result document instead of a result cursor.
 This bug was fixed in the underlying mongo-php-library.
 * [#71](https://github.com/alcaeus/mongo-php-adapter/pull/71) adds checks to
 all class files to prevent class declarations when `ext-mongo` is already
 loaded and not using an autoloader.
 * [#72](https://github.com/alcaeus/mongo-php-adapter/pull/72) fixes wrong
 argument order in the constructor for the `Timestamp` type.
 * [#75](https://github.com/alcaeus/mongo-php-adapter/pull/75) adds a warning to
 `MongoCursor::timeout` to let people now cursor timeouts are no longer supported.
 * [#77](https://github.com/alcaeus/mongo-php-adapter/pull/77) adds support for
 the `update` option in `findAndModify` calls.

1.0.0-BETA1 (2016-02-17)
------------------------

All issues and pull requests under this release may be found under the
[1.0.0-BETA1](https://github.com/alcaeus/mongo-php-adapter/issues?q=milestone%3A1.0.0-BETA1)
milestone.

 * [#52](https://github.com/alcaeus/mongo-php-adapter/pull/52) fixes behavior of
 `MongoCollection::update` when no update operators have been given.
 * [#53](https://github.com/alcaeus/mongo-php-adapter/pull/53) fixes an error
 where some operations would send an invalid query to the MongoDB server,
 causing command failures.
 * [#54](https://github.com/alcaeus/mongo-php-adapter/pull/54) and
 [#55](https://github.com/alcaeus/mongo-php-adapter/pull/55) fix the handling of
 documents with numeric keys.
 * [#56](https://github.com/alcaeus/mongo-php-adapter/pull/56) fixes the
 behavior of `MongoGridFS::findOne` when no results are found.
 * [#59](https://github.com/alcaeus/mongo-php-adapter/pull/59) adds handling for
 the `includeSystemCollections` parameter in `MongoDB::getCollectionInfo` and
 `MongoDB::getCollectionNames`.
 * [#62](https://github.com/alcaeus/mongo-php-adapter/pull/62) removes the
 manual comparison of index options to rely on the MongoDB server to decide
 whether an index already exists.
 * [#63](https://github.com/alcaeus/mongo-php-adapter/pull/63) prevents
 serialization of driver classes which are not serializable.

0.1.0 (2016-02-06)
------------------

Initial development release.
