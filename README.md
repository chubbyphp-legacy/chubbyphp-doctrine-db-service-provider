# chubbyphp-doctrine-db-service-provider

[![Build Status](https://api.travis-ci.org/chubbyphp/chubbyphp-doctrine-db-service-provider.png?branch=master)](https://travis-ci.org/chubbyphp/chubbyphp-doctrine-db-service-provider)
[![Coverage Status](https://coveralls.io/repos/github/chubbyphp/chubbyphp-doctrine-db-service-provider/badge.svg?branch=master)](https://coveralls.io/github/chubbyphp/chubbyphp-doctrine-db-service-provider?branch=master)
[![Total Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-doctrine-db-service-provider/downloads.png)](https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider)
[![Monthly Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-doctrine-db-service-provider/d/monthly)](https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider)
[![Latest Stable Version](https://poser.pugx.org/chubbyphp/chubbyphp-doctrine-db-service-provider/v/stable.png)](https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider)
[![Latest Unstable Version](https://poser.pugx.org/chubbyphp/chubbyphp-doctrine-db-service-provider/v/unstable)](https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider)

## Description

Doctrine database service providers for doctrine dbal and orm.

## Requirements

 * php: ^7.2
 * doctrine/cache: ^1.6
 * doctrine/dbal: ^2.5

## Suggest

 * doctrine/orm: ^2.5

## Installation

Through [Composer](http://getcomposer.org) as [chubbyphp/chubbyphp-doctrine-db-service-provider][1].

```sh
composer require chubbyphp/chubbyphp-doctrine-db-service-provider "^1.6"
```

## Usage

### ServiceFactory (chubbyphp/chubbyphp-container)

 * [DoctrineDbalServiceFactory][2]
 * [DoctrineOrmServiceFactory][3]

### ServiceProvider (pimple/pimple)

 * [DoctrineDbalServiceProvider][4]
 * [DoctrineOrmServiceProvider][5]

## Copyright

Dominik Zogg 2019

*There is some code with @see, copied with small modifications by from thirdparties.*

[1]: https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider

[2]: doc/ServiceFactory/DoctrineDbalServiceFactory.md
[3]: doc/ServiceFactory/DoctrineOrmServiceFactory.md

[4]: doc/ServiceProvider/DoctrineDbalServiceProvider.md
[5]: doc/ServiceProvider/DoctrineOrmServiceProvider.md
