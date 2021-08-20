# Configuration

You can set the following properties in your `design-system-config.php` config file.

## navigation

Primary navigation for the design system, an array of labels and links. Please note these
links need to work on every page, so make these relative to the site root `/`.

Default value:

```php
'navigation'        => [
    'Home'          => '/',
    'Templates'     => '/code/templates/',
],
```

## assets_build_command

Command to run to build frontend assets for your templates, by default this calls a shell script
where you can add your frontend build commands.

Default value: `./design-system-build.sh`

## docs_path

Path where to build documentation pages from, relative to the project root.

Default value: `docs/`

## templates_path

Path where your template files are found, relative to the project root. Make sure all your code examples 
and other templates are in this location.

Default value: `templates/`
