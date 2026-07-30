# stimberfans.com

Custom WordPress theme and plugins for [stimberfans.com](https://stimberfans.com).

## What's in this repo

- `wp-content/themes/generatepress_child` — child theme
- `wp-content/plugins/oa-*` — custom site plugins
- `wp-content/plugins/chadgpt-gp-header-layout-mods`

WordPress core, uploads, and `wp-config.php` stay on the server only.

## Workflow

1. Edit files in Cursor
2. Commit and push to `main`
3. GitHub Actions deploys to the live server over SSH

## Local SSH

```powershell
ssh stimberfans
```
