# Usage

## Writing documentation

You need to [write your documentation](writing-documentation.md) in Markdown format. 

## Build design system website

You can run the build process via:

```
./vendor/bin design-system
```

This outputs the design system website into the `_dist/` folder.

### Verbose mode

You can also enable verbose mode, which outputs more information about what is happening.

```
./vendor/bin design-system -v
```

### Options

By default, the design system is built from the current folder. You can set a specific root path 
to use via the `--path` or `-p` option.

By default, we look for a config file called `design-system-config.php`. You can set a different config file 
via the `--config` or `-c` option.

You can also run only specific actions via the `--actions` or `-a` option. Available actions are:
* `c` - Clean destination directory and copy design assets 
* `a` - Build frontend assets
* `d` - Build documentation pages

You can specify one or many actions by concatenating them together, e.g.

```
./vendor/bin design-system -a=c
```

or:

```
./vendor/bin design-system -a=ad
```

## View the design system website
You can view this via the in-built PHP server at http://localhost:8000 via:

```bash
php -S localhost:8000 -t _dist
```
