# Code examples

To output code examples you need to use the custom `<example>` tag. This generates the HTML template, saves these to the
`code/` folder in your design system website, and adds code examples and links in your documentation page.

## Code fragments

To display code fragments or components add the following custom HTML tag to your Markdown file, ensuring you close the
HTML tag (e.g. `/>`):

```markdown
<example title="Tables" src="components/tables.html.twig" />
```

This renders the template, saves this to a file and embeds it in your doc page. On your doc page a live preview of the template
is displayed, a link to view this in a new tab, and the HTML source code.

In order for this to work you need to include the following attributes:

* `title` - title of your code example
* `src` - path to the code Twig template, relative to your templates folder (by default this is `templates/`)

Optional attributes for the `<example>` tag are:
* `data`
* `data-src`

See [passing data to your code template](#passing-data-to-your-code-template) below.

## Full page templates

To display a standalone full-page template:

```markdown
<example title="Default template" src="examples/default.html.twig" standalone />
```

This works in exactly the same way as code components, you just need to add the `standalone` attribute. This renders
the template as a standalone page.

## Template to embed code fragments

When a code fragment is displayed it is embedded in the template `templates/design-system/example-code.html.twig` so it
is a valid HTML page.

You will likely need to include the correct styles for the code fragment to work. To do this, you can copy this template
file locally and override it.

You can copy the default template to customise via:

```bash
cp vendor/studio24/design-system/templates/example-code.html.twig templates/design-system/example-code.html.twig
```

You can then edit this as you wish.

## Passing data to your code template
You can pass data to your code template via the `data` or `data-src` attribute. These methods pass the data to the
Twig template, allowing you to construct code examples with data variables.

### Inline data variables

You can pass simple key, value data pairs inline:

```markdown
<example title="Tables" src="components/tables.html.twig" data="foo: bar, name: value" />
```

The `data` attribute takes data properties in the form `name: value, name2: value2`, etc.

### Loading data from a file

You can pass data via a JSON or PHP file. The file path must be relative to your templates folder.

JSON:

```markdown
<example title="Tables" src="components/tables.html.twig" data-src="data/example.json" />
```

PHP (you must include a `$data` variable containing an array of data):

```markdown
<example title="Tables" src="components/tables.html.twig" data-src="data/example.php" />
```
