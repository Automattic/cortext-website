# Cortext Website

The dependency-free WordPress block theme for [cortext.digital](https://cortext.digital). It contains the public templates and visual system; pages and posts live in WordPress.com after the initial launch.

## Theme structure

- `theme.json`, `style.css`, and `functions.php` define the design system and public metadata.
- `templates/`, `parts/`, and `patterns/` contain the versioned block theme.
- `content/` and `scripts/bootstrap-content.php` hold the initial editorial copy and local bootstrap. `.deployignore` keeps both off the server.
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

Theme files reach WordPress.com through the WordPress Studio CLI. GitHub holds the code; Studio does the deploying. The site has no GitHub Deployments connection, so nothing ships by merging alone.

Pull first, then push. Studio sends whatever is in the local working copy, so a merged change that has not been pulled will quietly fail to ship.

```sh
cd ~/Studio/cortextdigital/wp-content/themes/cortext-website
git pull origin main

cd ~/Studio/cortextdigital
studio push --remote-site https://staging-589f-cortextdigital.wpcomstaging.com --options themes
studio push --remote-site https://cortext.digital --options themes
```

`--options themes` syncs `wp-content/themes` and leaves pages, posts and the database alone. Studio takes a remote backup before applying.

The initial launch also ran `--options sqls,uploads` to carry the database and media across. That one replaces the whole database, so it is not for routine updates.

`.deployignore` is honoured, so `content/`, `scripts/` and `README.md` stay off the server. Check it anyway after a push, because the unpublished release post lives in `content/`:

```sh
curl -s -o /dev/null -w "%{http_code}\n" \
  https://cortext.digital/wp-content/themes/cortext-website/content/introducing-cortext-0-2.html
```

404 is correct. A 200 means the draft is readable at a direct URL.

A push adds and overwrites; it never deletes. A file removed from the repo stays on the server until someone removes it there.

Content is edited on WordPress.com directly. Do not edit theme templates or Global Styles in the production Site Editor; database overrides would take precedence over these versioned files.

## License

GPL-2.0-or-later. See `LICENSE`.
