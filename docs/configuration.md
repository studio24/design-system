# Configuration

You can set the following properties in your `design-system-config.php` config file.

## debug

Enable or disable debug mode for Twig templating.

Default value: `false` 

## cache_path

Cache path for Twig templates. If null, then uses the system temp directory.

Default value: `null`

### assets_build_command

Command to run to build frontend assets for your templates, by default this calls a shell script
where you can add your frontend build commands.

Default value: `./design-system-build.sh`

### docs_path

Path where to build documentation pages from, relative to the project root.

Default value: `docs/`

### templates_path

Path where your template files are found, relative to the project root. Make sure all your code examples 
and other templates are in this location.

Default value: `templates/`

### twig_render

Sometimes you want to render complete or partial Twig templates outside of the doc [code examples](writing-documentation.md#outputting-code-examples).  

To do this pass an array of directory paths or individual templates (relative to your templates folder) to the 
`twig_render` configuration variable and these will be rendered by Twig into HTML templates and stored in 
`_dist/code/templates`.

Default value (no templates to render):

```php
'twig_render' => [
],
```

You can pass data to these templates by creating a PHP file with the same name as the template, suffixed with `.php`. 
This PHP file must contain a `$data` variable which contains an array of data passed to the template.

For example:

```php
'twig_render' => [
    'examples'
],
```

This will render all `*.twig` templates found in the directory path `templates/examples`

With the following example files:

```
my-project-root
├── templates   
│   └── examples 
│       └── one-col-page.html.twig
│       └── two-col-page.html.twig
│       └── two-col-page.html.twig.php
```

The template `two-col-page.html.twig` will be passed the `$data` array found in `two-col-page.html.twig.php`  

### navigation

Primary navigation for the design system, an array of labels and links. Please note these 
links need to work on every page, so make these relative to the site root (/).

Default value: 

```php
'navigation'        => [
    'Home'          => '/',
    'Styles'        => '/styles/',
    'Components'    => '/components/',
    'Templates'     => '/templates/',
],
```

Please note sibling navigation is automatically displayed on pages in the left-hand column. A natural sort 
order is used to sort the child pages.

---



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