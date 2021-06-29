# Design system

Simple PHP-powered tool to create a static design system website using [Twig](https://twig.symfony.com/) for page templating.

## Example usage 

This tool does the following:
* Runs asset build command
* Copies assets (or other files/folders) to dist folder 
* Copies Twig templates and parses these into HTML using sample data
* Adds Twig function `example()` to output example HTML component in markdown docs
* Copies markdown files and parses these into HTML

Example folder structure:

```
assets/
dist/
docs/
templates/
composer.json
config.php
```

Build design system HTML:

```
php build.php
```

This outputs documentation into the `dist/` folder. 

## Requirements

* PHP 7.3+
* [Composer](https://getcomposer.org/)

## Installation

```bash
composer require --dev studio24/design-system
```


```
composer install
nvm use
npm install
```

## Usage

Build static files from the current folder, saving files to `dist/`:

```bash
php apollo build 
```

You can also specify the path where source files come from:

```bash
php apollo build path/to/files
```

By default Apollo builds everything, but you can build one element by passing the command name: 

* delete - delete all files in dist folder
* assets - build static assets via config build_command
* pages - build markdown pages
* examples - build example HTML templates

```bash
php apollo build assets
```

View static design system website locally:

```bash
php -S localhost:8000 -t dist
```

TODO: Rebuild files on any changes and view static design system website locally:

```bash
# Please note this is not working yet and is in development
php -S localhost:8000 -t dist watch.php
```

## Directory structure

Design system projects contain the following directory structure:

```
my-project-root
├── docs
├── dist
├── templates
├── var
│   └── cache
├── composer.json
├── apollo.php
└── package.json
```

Build: 

```
./vendor/bin/apollo build
```

View built templates from `dist/` folder:

```
php -S localhost:8000 -t dist
```

OLD:

```
my-project-root
├── design-system
├── dist
├── php
├── source
│   ├── assets
│   ├── components
│   ├── examples
│   ├── guidelines
│   ├── templates
│   └── index.md
├── tests
├── var
│   └── cache
├── composer.json
├── config.php
└── package.json
```

| File | Description
| ---  | ---
| **design-system** | Design system CSS file and templates
| **dist** | Generated design system static HTML/CSS files
| **php** | PHP files used to generate design system (do not edit)
| **source/assets** | Static CSS and JavaScript files
| **source/components** | Markdown pages & Twig code fragments for components
| **source/examples** | Full-page HTML template examples
| **source/guidelines** | Markdown pages for guidelines
| **source/templates** | Twig templates
| **source/index.md** | Markdown file for index page
| **tests** | PHP unit tests for design system
| **var/cache** | Cache folder
| **composer.json** | Composer file
| **config.php** | Configuration file for building project files
| **package.json** | NPM build script for static assets (CSS, JS)

## Configuration

### source_path

Path to source files, relative to project root. 

Default value: `'source'`

The following special folders exist:

* assets - CSS, JS, fonts, etc
* examples - Example HTML templates
* templates - Twig page templates 

When pages for your design system are built they look for all Markdown files and all folders except those noted above. 

### destination_path  

Path to destination folder, relative to project root.

Default value: `'dist'`

### cache_path

Path to cache folder, relative to project root.

Default value: `'var/cache'`

### build_command

Build command for static assets. This is run from the project root folder.  

Default value: `'npm run build'`

### navigation

Navigation to display in the design system. 

Each item can either point to a file or a folder. See _Markdown and template files_ for more on how files are parsed.

The following two folders are required and are considered special:

* examples - contain full HTML page templates only
* templates - Twig page templates

Default value:

```php
    'navigation' => [
        'Home'          => 'index.md',
        'Get started'   => 'get-started.md',
        'Guidelines'    => 'guidelines/',
        'Components'    => 'components/',
        'Examples'      => 'examples/',
        'Support'       => 'support.md',
    ],
```

## Markdown and template files

This design system built tool is based on the patterns used on the [GOVUK Design System](https://design-system.service.gov.uk/)
which displays content pages with HTML embedded as example code. 

### Markdown

The default functionality is to parse files and folders and look for Markdown files and output these as HTML. This 
action happens across all folders except for _examples_ and _templates_.

This system supports [GitHub flavoured Markdown](https://guides.github.com/features/mastering-markdown/).

Please note this only supports one-level of files within a folder.
 
### Front Matter

You can add front matter to your markdown file which are converted to Twig data variables. At present the following 
front matter variables are supported:

* title - Page title

### Example code

To embed example code in a Markdown file:

```
{{ example('filename.html.twig') }}
```

You can pass data to your Twig template via:

```
{{ example('filename.html.twig', [key: value, key2: value2]) }}
```

### Twig templates

Twig templates are first loaded from `source/templates` and then from `source/_design_system/templates`. This means if you 
have a template with the same name as one used in the design system build tool, it will override it.

Required templates:

* `templates/_design_system/design-system.html.twig` - Base template for design system HTML
* `templates/_design_system/page.html.twig` - Base template for design system HTML

### Examples

The _examples_ folder is intended to hold full HTML page templates to illustrate examples of the usage of the design
system. Files must be Twig templates in the naming convention _name.html.twig_

An index page for HTML examples is automatically generated.

## Credits

Inspired by [GOVUK Design System](https://design-system.service.gov.uk/) and [Drizzle](https://github.com/cloudfour/drizzle).