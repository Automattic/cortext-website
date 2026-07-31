# Cortext Website

The dependency-free WordPress block theme for [cortext.digital](https://cortext.digital). It contains the public templates and visual system; pages and posts live in WordPress.com after the initial launch.

## Theme structure

- `theme.json`, `style.css`, and `functions.php` define the design system and public metadata.
- `templates/`, `parts/`, and `patterns/` contain the versioned block theme.
- `content/` and `scripts/bootstrap-content.php` hold the initial editorial copy and local bootstrap. `.deployignore` keeps both out of WordPress.com theme deployments.
- `assets/images/` contains the Cortext icon, product screenshot, and social banner.

There is no JavaScript dependency, package manager, or build step.

## Bootstrap a fresh Studio site

Make a site backup first. Then run:

```sh
cd ~/Studio/cortextdigital
studio wp theme activate cortext-website
studio wp eval-file wp-content/themes/cortext-website/scripts/bootstrap-content.php
```

The script is idempotent. It creates the homepage, blog page, privacy page, first release post, media, primary navigation, and site settings. If a managed page has been edited since the previous run, its editorial changes are preserved.

Run the read-only checks with:

```sh
studio wp eval-file wp-content/themes/cortext-website/scripts/validate-content.php
```

The bootstrap is only for the initial local-to-staging sync. Once the site launches, WordPress.com is the source of truth for pages and posts.

## Deployment

WordPress.com GitHub Deployments uses Simple mode with this repository as the theme root and `/wp-content/themes/cortext-website` as the destination:

- Staging deploys `main` automatically for review.
- Production deploys `main` manually after staging approval.

Do not edit theme templates or Global Styles in the production Site Editor. Database overrides would take precedence over these versioned files.

## License

GPL-2.0-or-later. See `LICENSE`.
