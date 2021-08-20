# Writing documentation

This design system built tool is based on the patterns used on the [GOVUK Design System](https://design-system.service.gov.uk/)
which is primarily documentation pages, with HTML embedded as example code.

The idea is you write documentation files, and for all code examples you include these using custom `<example>` tags that 
generate code examples.

## Markdown files

Your documentation files are written in Markdown. This system supports [GitHub flavoured Markdown](https://guides.github.com/features/mastering-markdown/).

Please note the following rules for building documentation pages:
* Only builds `.md` files
* Any Markdown links to `.md` files are converted to links to `.html` pages
* Your first heading is used as the navigation title in sibling navigation (or the filename is used if no headings exist)
* If a page appears in the top navigation it does not appear in the sibling navigation
* Add a `README.md` as your index page in each directory, if one is not included a simple HTML page with sibling navigation will be generated

## Sibling navigation

Links to sibling pages (in a directory) are automatically outputted to the template in the sidebar.

Please note, this is not done for the root directory, since it's assumed you will have a `README.md` file here. You will
need to add any links to root-level documentation pages yourself in your markdown documentation pages.

## Outputting code examples

See [code examples](code-examples.md).

## Outputting color swatches

See [color swatches](colors.md).