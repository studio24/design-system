# Setup

## Example folder structure

An example folder structure for your project:

```
my-project-root
├── _dist               -- Static design system website files
├── docs                -- Markdown documentation
├── templates           -- Twig templates
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

This creates the files:
* `design-system-config.php` - Configure your project
* `design-system-build.sh` - Your frontend asset build script

## .gitignore

It's recommended to add the following to your `.gitignore` file:

```
_dist
```

## Configuration

You can either use the default setup, or [customise the configuration file](configuration.md).

It's recommended you update the following configuration properties to ensure the Design System builds properly:

* [navigation](configuration.md#navigation)
* [twig_render](configuration.md#twig_render)

## Assets build script

By default, the script `design-system-build.sh` is run to build your frontend assets so the HTML templates and components 
work. You need to customise this to include the correct commands.

Please note any built assets need to be saved in `_dist/assets/`. Please do not save built assets 
to the `_dist/assets/design-system/` folder, which is used for the design system assets.

## Build templates

To build any other templates, outside of code examples in your docs, you can use the 
[`twig_render` configuration setting](configuration.md#twig_render).

## Custom design system templates

You can customise the design system templates by overriding them with templates saved in your local project at: `templates/design-system/`

Some notable templates worth customising are:

* `header-title.html.twig` - HTML content for the title section of the header
* `footer-text.html.twig` - HTML content for the footer
* `example-code.html.twig` - Template used to [output code examples](writing-documentation.md#custom-template-for-code-examples)

You can find the original templates in `vendor/studio24/design-system/templates/`