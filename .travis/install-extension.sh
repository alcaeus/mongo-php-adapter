#!/bin/sh

# This file was copied from the MongoDB library at https://github.com/mongodb/mongo-php-library.
# Copyright is (c) MongoDB, Inc.

INI=~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

if [ "x${DRIVER_BRANCH}" != "x" ] || [ "x${DRIVER_REPO}" != "x" ]; then
  CLONE_REPO=${DRIVER_REPO:-https://github.com/mongodb/mongo-php-driver}
  CHECKOUT_BRANCH=${DRIVER_BRANCH:-master}

  echo "Compiling driver branch ${CHECKOUT_BRANCH} from repository ${CLONE_REPO}"

  mkdir -p /tmp/compile
  git clone ${CLONE_REPO} /tmp/compile/mongo-php-driver
  cd /tmp/compile/mongo-php-driver

  git checkout ${CHECKOUT_BRANCH}
  git submodule update --init
  phpize
  ./configure --enable-mongodb-developer-flags
  make all -j20 > /dev/null
  make install

  echo "extension=mongodb.so" >> `php --ini | grep "Scan for additional .ini files in" | sed -e "s|.*:\s*||"`/mongodb.ini
elif [ "x${DRIVER_VERSION}" != "x" ]; then
  echo "Installing driver version ${DRIVER_VERSION} from PECL"
  pecl install -f mongodb-${DRIVER_VERSION}
else
  echo "Installing latest driver version from PECL"
  pecl install -f mongodb
fi
