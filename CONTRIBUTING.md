# Contributing to Repo Bridge Forge

Thanks for taking the time to help. This document covers local setup, coding
standards, how versioning works, and how a release gets published to the
WordPress plugin directory.

## Local setup

Clone into a WordPress install's plugins folder:

```bash
git clone https://github.com/gunjanjaswal/RepoBridgeForge.git wp-content/plugins/repobridgeforge
```

Repo Bridge Forge has no third-party dependencies and no build step. Markdown is
converted by a small built-in converter in `includes/class-markdown.php`.

## Project layout

```
repobridgeforge/
├── repobridgeforge.php              Main file: header, constants, bootstrap
├── uninstall.php              Removes options on uninstall (keeps your posts)
├── readme.txt                 WordPress.org readme
├── includes/
│   ├── class-plugin.php       Orchestrator, cron scheduling
│   ├── class-settings.php     Config and encrypted token storage
│   ├── class-github-client.php  GitHub REST client
│   ├── class-content-parser.php Front matter and Markdown parsing
│   ├── class-sync-engine.php  The pull loop and change detection
│   ├── class-logger.php       Activity log
│   └── class-markdown.php     Built-in Markdown to HTML converter
└── admin/
    ├── class-admin-page.php   Settings screen and handlers
    └── views/settings.php     The form and activity table
```

## Coding standards

- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).
- Escape on output, sanitize on input, and use nonces plus capability checks for every action.
- Keep changes surgical. Touch what the change needs and match the surrounding style.

Lint everything before you push:

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

If you have PHP_CodeSniffer with the WordPress standard installed:

```bash
phpcs --standard=WordPress .
```

## Versioning

Repo Bridge Forge uses [Semantic Versioning](https://semver.org): `MAJOR.MINOR.PATCH`.

- **PATCH** for backward-compatible fixes.
- **MINOR** for backward-compatible features.
- **MAJOR** for breaking changes.

The version number lives in four places and they must match on every release:

| Location | Field |
| --- | --- |
| `repobridgeforge.php` | `Version:` header |
| `repobridgeforge.php` | `REPOBRIDGEFORGE_VERSION` constant |
| `readme.txt` | `Stable tag:` |
| `CHANGELOG.md` | The new version heading |

## Release process

1. Update the four version locations above.
2. Move the relevant notes from `Unreleased` into a dated version section in `CHANGELOG.md`, and mirror them in the `readme.txt` changelog.
3. Commit the bump, for example `Release 0.2.0`.
4. Tag it and push the tag:

   ```bash
   git tag v0.2.0
   git push origin main --tags
   ```

5. Create a GitHub release from that tag so the source is tagged on GitHub.

Publishing the release to the WordPress plugin directory is handled by the
maintainer outside this repository. The [`.distignore`](.distignore) file lists
the development-only files to leave out of a build; runtime files, including the
built-in Markdown converter, always ship.

## Reporting issues

Open an issue at
[github.com/gunjanjaswal/RepoBridgeForge/issues](https://github.com/gunjanjaswal/RepoBridgeForge/issues).
A sample Markdown file and your plugin settings make bugs much faster to track down.
