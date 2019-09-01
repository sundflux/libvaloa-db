libvaloa - database component
========

[![webvaloa](https://github.com/sundflux/libvaloa/blob/master/.vendor.png)](https://github.com/sundflux/libvaloa/blob/master/.vendor.png)

Libvaloa is a set of standalone open source utility libraries, which make base for Webvaloa platform. 

Libvaloa components aim for top-tier code quality, security and modern design patterns. 

All Libvaloa components are licensed with permissive MIT license.

This package adds minimal abstraction layer for PDO connections.

http://libvaloa.webvaloa.com/

## Installation

Install the latest version with `composer require sundflux/libvaloa-db`

or include libvaloa in your composer.json

```json
{
    "require": {
        "sundflux/libvaloa-db": "^3.0.0"
    }
}
```

## Requirements

- PHP 7.2.19

## Features

- Chainable value setters
- Resultsets as objects
- Generate table models from yaml configs

## Copyright and license

Copyright (C) 2019 Tarmo Alexander Sundström & contributors.

Libvaloa is licensed under the MIT License - see the LICENSE file for details.

## Contact

- ta@sundstrom.io
- http://libvaloa.webvaloa.com/

## Change Log
All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

Changes are grouped by added, fixed or changed feature.

### [3.1.1] - 2019-09-01
- Hotfix, fix queryCount capitalization.

### [3.1.0] - 2019-09-01
- Small cleanups, doctag updates.
- Add \Model\Table for generating table schemas from yaml configs.
- Remove generate_documentation.sh (helper script for phpdoc)

### [3.0.2] - 2019-05-25
- Code and documentation cleanups.

### [3.0.1] - 2019-05-13
- Fix missing exception include in ResultSet.

### [3.0.0] - 2019-04-13
- Bumped version requirement to PHP 7.2
- First version separated from Libvaloa. See Libvaloa changelog for earlier changes.
