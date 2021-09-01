# Contributing to this repo

## Principles

This repo is intended to be a simple tool to generate a static website to document a design system. This is made up of
HTML pages generated from Markdown files and code fragments & example full page templates.

It is intended to be documentation-first, which means the design system site generation starts from building documentation 
pages (from Markdown files) and generates code examples/page templates from code snippets found within these documentation 
pages. 

It is intended to be simple to use with minimal configuration.

The HTML/CSS to layout the design system site itself (and documentation pages) uses the [Apollo CSS starter kit](https://github.com/studio24/apollo). 

## Making changes via pull requests 

Please work on branches when making changes. 

Please create a Pull Request to merge changes into master, all Pull Requests need at least one approval from the Studio 24 development team.

## HTML/CSS of design system site

The design system templates  and front-end assets are stored in:

* `assets/design-system` - Design system front-end assets (CSS, JS) 
* `templates` - Design system Twig templates

At present there is no build system for the front-end CSS and JS. Simply edit files in the assets directory. 

## PHP code to generate design system site

The PHP code to generate the design system static site is in:

* `docs` - Documentation on usage
* `src` - Source code
  * `Command` - Symfony console commands
  * `Exception` - Custom exceptions to help with error handling
  * `Parser` - Parsing related PHP classes
* `tests` - Unit tests

Most of the business logic of the site build process is in the `src/Build.php` file. This loads the design system 
configuration via the `src/Config.php` class. 
