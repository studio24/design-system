# Design system

Simple documentation-first tool to build a static design system website based on Markdown documentation files and [Twig](https://twig.symfony.com/) templates.

Key features:

* Documentation-first approach, builds design system website based on Markdown docs
* Include code examples and templates via special tags
* Include sample data for code examples and templates
* Generate colour swatches in documentation
* Extensible via custom tags

## Requirements

* PHP 7.3+
* [Composer](https://getcomposer.org/)

## Installation

Load the library for local development only:

```bash
composer require --dev studio24/design-system
```

If you already have this in your project then just run `composer install` to download the files. To update your copy of
the the design system library files run `composer update`

To build the design system website files:

```
./vendor/bin/design-system
```

To see what files the design system is outputting pass the `-v` verbose option.

```
./vendor/bin/design-system -v 
```

You can then view the design system website via:

```bash
php -S localhost:8000 -t _dist
```

## Documentation

See [docs](docs/README.md) for further details.

## License

[MIT License](LICENSE) (MIT) Copyright (c) 2020 Studio 24 Ltd (www.studio24.net)

## Credits

Developed by [Simon R Jones](https://github.com/simonrjones/).

Inspired by [GOVUK Design System](https://design-system.service.gov.uk/) and [Drizzle](https://github.com/cloudfour/drizzle).