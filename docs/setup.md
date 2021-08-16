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

It's recommended you update the following configuration properties to ensure the Design System builds properly:

* [navigation](configuration.md#navigation) - top-level navigation links
* [twig_render](configuration.md#twig_render) - full page templates to render

## Assets build script

By default, the script `design-system-build.sh` is run to build your frontend assets so the HTML templates and components 
work. You need to customise this to include the correct commands.

Please note any built assets need to be saved in `_dist/assets/`. Please do not save built assets 
to the `_dist/assets/design-system/` folder, which is used for the design system assets.

## Documentation

You can [write documentation](writing-documentation.md) in Markdown format. You can create any number of sub-folders to 
organise your docs. Please remember to include a `README.md` file within each folder which acts as the index file. 

## Code examples

You can include [code examples](writing-documentation.md#outputting-code-examples) directly in your documentation.

## Build full-page templates

You can include example full-page templates by defining files or folders to build these from in the 
[`build_templates` configuration variable](configuration.md#build_templates). 

Make sure you also remember to add a link to your templates in the navigation. You can link to the automatically 
created template index page at `/code/templates/index.html` or include links in your own documentation page.

## Navigation

You can define top-level navigation via the [`navigation` configuration variable](configuration.md#configuration).

Secondary navigation is automatically generated for documentation pages in the left-hand column. A natural sort
order is used to sort the child pages.

If you wish to build full-page templates links to all templates is automatically generated on the template index page.

## Custom design system templates

You can customise the design system templates by overriding them with templates saved in your local project at: `templates/design-system/`

Some notable templates worth customising are:

* `header-title.html.twig` - HTML content for the title section of the header
* `footer-text.html.twig` - HTML content for the footer
* `example-code.html.twig` - Template used to [output code examples](writing-documentation.md#custom-template-for-code-examples)

You can find the original templates in `vendor/studio24/design-system/templates/`