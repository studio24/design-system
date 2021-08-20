# Configuration

You can set the following properties in your `design-system-config.php` config file.

## Recommended to change

### navigation

Primary navigation for the design system, an array of labels and links. Please note these
links need to work on every page, so make these relative to the site root `/`.

Default value:

```php
'navigation'        => [
    'Home'          => '/',
    'Templates'     => '/code/templates/',
],
```

### build_templates KILL THESE

Sometimes you want to render complete or partial Twig templates outside of the doc [code examples](writing-documentation.md#outputting-code-examples).

To do this pass an array of directory paths or individual templates (relative to your templates folder) to the
`build_templates` configuration variable and these will be rendered by Twig into HTML templates and stored in
`_dist/code/templates`.

Default value (no templates to render):

```php
'build_templates' => [
],
```

You can pass data to these templates by creating a PHP file with the same name as the template, suffixed with `.php`.
This PHP file must contain a `$data` variable which contains an array of data passed to the template.

For example:

```php
'build_templates' => [
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
│       └── one-col-page.html.twig.php
│       └── two-col-page.html.twig
```

The template `one-col-page.html.twig` will be passed the `$data` array found in `one-col-page.html.twig.php`

## Other configuration variables

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
