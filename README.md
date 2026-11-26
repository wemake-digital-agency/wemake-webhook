# wemake-webhook

Wemake Webhook - WordPress plugin by Wemake that sends Contact Form 7 submissions to a webhook URL (a default URL or
one per form). Settings: Settings → `options-general.php?page=wm-webhook`.

- **Requires:** PHP 5.6.40+ (checked by the plugin).
- The repository is **public**: copies 1.2 read its releases to update.

This README is the living reference: updates, releases, updating a site. Source of truth is the code; update this file
in the same change.

## Docs

- `README.md` (this file) - updates, releases, updating a site.
- [docs/DECISIONS.md](docs/DECISIONS.md) - decisions and who made them, newest on top.
- [docs/CHANGELOG.md](docs/CHANGELOG.md) - releases and changes with Verify and Rollback, newest on top.
- [CLAUDE.md](CLAUDE.md) - rules for Claude in this repository.

## How updates work

From 1.3 the plugin updates through the Wemake update server and the standard WordPress updater (Dashboard > Updates,
the Plugins screen, WordPress auto-updates). It asks `https://repository.wemake.co.il/check.php` when WordPress reads its
update list (at most once per 12 hours, "Check again" skips the cache); the server reads the latest GitHub release of
this repository and returns a signed download link to `wemake-webhook.zip`.

- Client: `inc/class-wemake-update-client.php` (`Wemake_Update_Client_1_0`), started in `wemake-webhook.php` for every
  request (also cron). The canonical copy lives in `wemake-digital-agency/wemake-repository` (`client/`); copy it from
  there, don't edit it here.
- The `Update URI` header (`wemake-webhook.php`) keeps WordPress from offering a wordpress.org plugin with the same slug.
- Nothing is replaced unless the whole zip arrived intact and holds `wemake-webhook.php` with a plugin header; otherwise
  the installed version stays. After an update the plugin keeps its installed folder name.
- The server answers only while `wemake-webhook` is in `WM_CLIENT_UPDATES` (`wemake-repository`, `public_html/inc/github.php`).
- Sites whose host blocks outgoing requests to `repository.wemake.co.il` get no updates: install new versions there by hand.

Older copies:

| Installed version | How it gets the latest release |
|---|---|
| 1.2 | Its own GitHub updater (`inc/admin_update_plugin_github.php`, removed in 1.3) reads `releases/latest` and offers the first `.zip` asset in wp-admin; the admin clicks "Update now" (or WordPress auto-updates it). |
| 1.06 and older | No updater: install 1.3+ by hand once. Done on www.wemake.co.il and qa.wemake.co.il (2026-11-26); left: Genius and Ecovacs landings, lftech.co.il, laifentech.co.il. |

Never edit plugin files directly on a site: the next update overwrites them. Fix it here and release.

## Making a release

1. Bump the version in both places in `wemake-webhook.php`: the `Version:` header and `WMHK_PLUGIN_VERSION`. Add an
   entry to `docs/CHANGELOG.md`. The new number must be greater than the latest release (`1.3` → `1.3.1`, `1.4`).
2. Merge to `main`, then push a tag with the same version: `git tag v1.4 && git push origin v1.4`.
   Don't create the release in the GitHub web UI: it would be public for a minute without `wemake-webhook.zip`, and
   copies 1.2 that check in that minute fall back to GitHub's source archive (wrong folder name).
3. The `Release` workflow (`.github/workflows/release.yml`) checks that the tag matches both versions, lints the PHP,
   builds `wemake-webhook.zip` (top folder `wemake-webhook/`, plugin files only), creates the release as a draft with the
   zip and then publishes it as the latest release.
4. Sites see the update within 12 hours (plus at most one hour of server cache), or at once with "Check again".

## Updating a site by hand

WP Admin → Dashboard → Updates → **Wemake Webhook** (*Check again* forces a check), or upload the release zip:
Plugins → Add New → Upload Plugin → *Replace current with uploaded*. Then send a test form and check that the webhook
receives it.
