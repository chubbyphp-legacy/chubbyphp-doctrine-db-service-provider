# chubbyphp-doctrine-db-service-provider

[![CI](https://github.com/chubbyphp/chubbyphp-doctrine-db-service-provider/workflows/CI/badge.svg?branch=master)](https://github.com/chubbyphp/chubbyphp-doctrine-db-service-provider/actions?query=workflow%3ACI)
[![Coverage Status](https://coveralls.io/repos/github/chubbyphp/chubbyphp-doctrine-db-service-provider/badge.svg?branch=master)](https://coveralls.io/github/chubbyphp/chubbyphp-doctrine-db-service-provider?branch=master)
[![Infection MSI](https://badge.stryker-mutator.io/github.com/chubbyphp/chubbyphp-doctrine-db-service-provider/master)](https://dashboard.stryker-mutator.io/reports/github.com/chubbyphp/chubbyphp-doctrine-db-service-provider/master)
[![Latest Stable Version](https://poser.pugx.org/chubbyphp/chubbyphp-doctrine-db-service-provider/v/stable.png)](https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider)
[![Total Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-doctrine-db-service-provider/downloads.png)](https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider)
[![Monthly Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-doctrine-db-service-provider/d/monthly)](https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider)

[![bugs](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=bugs)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![code_smells](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=code_smells)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![coverage](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=coverage)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![duplicated_lines_density](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=duplicated_lines_density)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![ncloc](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=ncloc)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![sqale_rating](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![alert_status](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=alert_status)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![reliability_rating](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![security_rating](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=security_rating)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![sqale_index](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=sqale_index)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)
[![vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-doctrine-db-service-provider&metric=vulnerabilities)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-doctrine-db-service-provider)

## Description

Doctrine database service providers for doctrine dbal and orm.

## Requirements

 * php: ^7.4|^8.0
 * doctrine/cache: ^1.11.3
 * doctrine/common: ^3.1.2
 * doctrine/dbal: ^2.11.3
 * doctrine/persistence: ^2.2.1

## Suggest

 * doctrine/orm: ^2.9.1

## Installation

Through [Composer](http://getcomposer.org) as [chubbyphp/chubbyphp-doctrine-db-service-provider][1].

```sh
composer require chubbyphp/chubbyphp-doctrine-db-service-provider "^2.1"
```

## Usage

### ServiceFactory (chubbyphp/chubbyphp-container)

 * [DoctrineDbalServiceFactory][2]
 * [DoctrineOrmServiceFactory][3]

### ServiceProvider (pimple/pimple)

 * [DoctrineDbalServiceProvider][4]
 * [DoctrineOrmServiceProvider][5]

## Copyright

Dominik Zogg 2021

*There is some code with @see, copied with small modifications by from thirdparties.*

[1]: https://packagist.org/packages/chubbyphp/chubbyphp-doctrine-db-service-provider

[2]: doc/ServiceFactory/DoctrineDbalServiceFactory.md
[3]: doc/ServiceFactory/DoctrineOrmServiceFactory.md

[4]: doc/ServiceProvider/DoctrineDbalServiceProvider.md
[5]: doc/ServiceProvider/DoctrineOrmServiceProvider.md
