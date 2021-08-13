# Writing documentation

This design system built tool is based on the patterns used on the [GOVUK Design System](https://design-system.service.gov.uk/)
which displays content pages with HTML embedded as example code.

## Markdown files
Your documentation files are written in Markdown. You can organise your documentation in folders, 
for the main index page in each folder, name this `README.md`. This ensures it renders on websites such as
GitHub and is also used as the index page on the design system site.

This system supports [GitHub flavoured Markdown](https://guides.github.com/features/mastering-markdown/).

## Front matter
At the top of your Markdown file, you need to set the page title in the front matter:

```markdown
---
title: Page title
---

My documentation text here
```

Required fields are:

* `title` - sets the H1 title and navigation title for this page

## Outputting code examples

You can output code examples in your markdown files via the custom HTML `example` tag:

```markdown
<example title="Tables" src="components/tables.html.twig">
```

This renders the template, saves this to a file and embeds it in your doc page.

The 1st argument is the title of your code example, the 2nd argument is the path to the code Twig template, relative
to your templates folder (by default this is `templates/`).

You can also output the actual HTML to your page via:

```markdown
<exampleHtml src="components/tables.html.twig">
```

This displays the HTML in a `<pre><code>` tag and uses [HighlightJS](https://highlightjs.org/) for code formatting.
Please note, you have to first use the `example` tag for `exampleHtml` to work.

### Custom template for code examples
By default, the example code is outputted to the `_dist/code/` folder and is embedded within the `example-code.html.twig`
template found within the Design System code. You can override this template, for example to load the correct
styles. You can do this by saving your own example code template at `templates/design-system/example-code.html.twig`.
You can copy the default template to customise via:

```bash
cp vendor/studio24/design-system/templates/example-code.html.twig templates/example-code.html.twig
```

### Passing data to your code template
You can pass data to your code template via the 3rd argument:

#### Inline data variables
```markdown
<example title="Tables" src="components/tables.html.twig" data="foo: bar, name: value">
```

The `data` attribute takes data properties in the form `name: value, name2: value2`, etc.

#### Loading data from a JSON file
You can also pass a file path to a JSON file containing a data structure, the file path should be relative
to your templates folder.

```markdown
<example title="Tables" src="components/tables.html.twig" data-src="data/example.json">
```

#### Loading data from a PHP file
Or you can use a PHP file which must include a `$data` variable containing an array of data.

```markdown
<example title="Tables" src="components/tables.html.twig" data-src="data/example.php">
```

All of these methods pass the data to the Twig template, allowing you to construct code examples with data variables.

## Sibling navigation

Links to sibling pages (in a directory) are automatically outputted to the template in the sidebar.
Please note, this is not done for the root directory, since it's assumed you will have a `README.md` file here. You will 
need to add any links to root-level documentation pages yourself in your markdown documentation pages.