# Design system

Simple tool to build a static design system website using [Twig](https://twig.symfony.com/) for page templating. 
Key features:

* Builds design system based on markdown documentation and Twig templates
* Use sample data to help build Twig templates
* Runs any local asset build process to build frontend assets

## Requirements

* PHP 7.3+
* [Composer](https://getcomposer.org/)

## Installation

Load the library for local development only:

```bash
composer require --dev studio24/design-system
```

If you already have this in your project then just run `composer install` to download the files.

To build the design system website files:

```
./vendor/bin design-system
```

You can then view the design system website via:

```bash
php -S localhost:8000 -t dist
```

## Documentation

See [docs](docs/README.md) for [setup](docs/setup.md) and [usage](docs/usage.md) instructions.

## License

[MIT License](LICENSE) (MIT) Copyright (c) 2020 Studio 24 Ltd (www.studio24.net)

## Credits

Developed by [Simon R Jones](https://github.com/simonrjones/).

Inspired by [GOVUK Design System](https://design-system.service.gov.uk/) and [Drizzle](https://github.com/cloudfour/drizzle).