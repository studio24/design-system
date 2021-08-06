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

TODO

Array of name => template paths to render as full page templates. Template paths are relative to `templates_path`. 

Default value: 

```php
'twig_render' => [
    'Components' => 'components',
    'Templates'  => 'examples',
],
```

### navigation

TODO

Default value: 

```php
'navigation'        => [
    'Home'          => 'README.md',
    'Styles'        => 'styles/',
    'Components'    => '@twig_render:Components',
    'Templates'     => '@twig_render:Templates',
],
```


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