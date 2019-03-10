libvaloa - database component
========

[![webvaloa](https://github.com/sundflux/libvaloa/blob/master/.vendor.png)](https://github.com/sundflux/libvaloa/blob/master/.vendor.png)

Libvaloa is a set of standalone open source utility libraries, which make base for Webvaloa platform. All Libvaloa components aim for quality code, modern design patterns, good documentation and highest possible security. All Libvaloa components are licensed with permissive MIT license.

This package adds minimal abstraction layer for PDO connections. The original design goal of libvaloa-db was to work as drop-in PDO-based replacement for AdoDB query API, but now it offers minimal abstraction for PDO and few utilities for common database tasks. 

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

- PHP 7.2

## Features

- Chainable value setters
- ...

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

### [3.0.0] - 2019-03-xx
- Bumped version requirement to PHP 7.2.
- Db package is now separated to its own component, Libvaloa-db. Libvaloa will only concentrate on XSL UI and related utilities.
- Remove I18n package completely. There are better translation libs out there, so it doesn't really bring any value to Libvaloa.
- Move all debug classes under Debug package. Effectively: Debug -> Debug\Debug, Debugtimer -> Debug\Debugtimer, Xml\DomDebug -> Debug\DomDebug.

### [2.0.0] - 2018-06-04
#### Added
- Rename Db\Object as Db\Item, this is necessary for PHP 7.2 compatibility (Object is now reserved word).

### [1.3.0] - 2018-03-22
#### Added
- Set and bind in Db\ResultSet now allow PDO type constants to be passed as a parameter.

### [1.2.3] - 2018-03-21
#### Fixed
- Readme fix.

### [1.2.2] - 2018-03-21
#### Fixed
- Gettext now returns original string if translation is not found.

### [1.2.1] - 2018-03-13
#### Fixed
- Gettext was always forced as default translator backend, oops.

### [1.2.0] - 2018-02-27
#### Added
- Gettext translation support, \I18n\Translate\Gettext.
- Composer dependency to Gettext/gettext.
- More inline documentation.

#### Changed
- Separated Db\ResultSet to its own file.

### [1.1.2] - 2018-02-23
- Fixes illegal string offset/missing array initializing in Db/Object.

### [1.1.1] - 2017-09-05
- Fixes Array to string crash in Xml/Conversion.php.

### [1.1.0] - 2017-07-05
#### Fixed
- Updated project urls.
- Code cleanups for Xml/Conversion.

#### Added
- Xml/Conversion now exposes more private methods as public.
- Xml/Xsl now uses Xml/Conversion instead of doing direct transformation
- Xml/Xml now uses Xml/Conversion when including view data with addObject. 
- Attributes are now possible from the UI since we use Xml/Conversion
- Updated PHP requirement to 5.6 to simplify maintainance.
- Memory requirements are slightly higher after Xml/Conversion migration.

### [1.0.1] - 2015-05-14
#### Fixed
- Libvaloa\Debug: Debug prints should output only when error level is E_ALL.
- Libvaloa\Ui\Xml: Fix session_status() check in addMessage().

### [1.0.0] - 2015-05-12
#### Added
- Initial release with stable API.

