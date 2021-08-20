# Setup

## Example folder structure

An example folder structure for your project:

```
my-project-root
├── _dist               -- This is where static design system website files are saved to
├── docs                -- Markdown documentation, use any sub-folders you wish
├── templates           -- Twig templates, use any sub-folders you wish
│   └── components      -- Components 
│   └── examples        -- Full page template examples
│   └── layouts         -- Twig layouts
├── composer.json       -- Composer file (PHP packages) 
└── package.json        -- NPN packages (frontend assets build)
```

Place your documentation in the `docs` folder, your Twig templates in the `templates` folder.

The Design System tool outputs the static built website into the `_dist` folder.

## Initial setup

Create the required config files:

```
./vendor/bin/design-system init
```

This creates the following files (if they don't already exist):
* `design-system-config.php` - Configure your project
* `design-system-build.sh` - Your frontend asset build script

## .gitignore

It's recommended to add the following to your `.gitignore` file:

```
_dist
```

## Configuration

You can either use the default setup, or [customise the configuration file](configuration.md).

## Navigation

You can define top-level navigation via the [`navigation` configuration variable](configuration.md#configuration).

Secondary navigation is automatically generated for documentation pages in the left-hand column. A natural sort
order is used to sort the child pages.

## Assets build script

By default, the script `design-system-build.sh` is run to build your frontend assets so the HTML templates and components 
work. You need to customise this to include the correct commands.

Please note any built assets need to be saved in `_dist/assets/`. Please do not save built assets 
to the `_dist/assets/design-system/` folder, which is used for the design system assets.

## Documentation

You can [write documentation](writing-documentation.md) in Markdown format. 

## Custom design system templates

You can customise the design system templates by overriding them with templates saved in your local project at: `templates/design-system/`

Some notable templates worth customising are:

* `header-title.html.twig` - HTML content for the title section of the header
* `footer-text.html.twig` - HTML content for the footer
* `example-code.html.twig` - Template used to [output code examples](writing-documentation.md#custom-template-for-code-examples)

You can find the original templates in `vendor/studio24/design-system/templates/`

For the footer template you can use the `{{ date }}` to output the generation datetime. You can use the [Twig date filter](https://twig.symfony.com/doc/3.x/filters/date.html) 
to format your datetime. E.g.

```twig
{{ date|date("d M Y, H:i:s e")  }}
```