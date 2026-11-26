# Wemake Webhook - Changelog

Releases and other changes, newest on top. Every entry: What, Why, Verify, Rollback, Task.
Versions before 1.3 have no entries here; see the git history.

## 2026-11-26 - qa.wemake.co.il and www.wemake.co.il: 1.06 → 1.3 by hand

What: Replaced `wp-content/plugins/wemake-webhook` on https://qa.wemake.co.il (JetServer, account `wemakeco`, docroot `public_html/devqa`) and https://www.wemake.co.il (`public_html`) with the v1.3 release (zip checked by md5). The installed 1.06 differed from 1.3 only by the update client and the header: the form sending code (`inc/webhook.php`), the settings save (`inc/action.php`) and the settings page are the same, and so are the option names. Old folders: `~/backups/qa-wemake-webhook-1.06-20261126` and `~/backups/prod-wemake-webhook-1.06-20261126` (outside the web root).
Why: 1.06 has no updater, so these copies could not reach the update client by themselves.
Verify: On both sites the plugin is active with 1.3, the update client is loaded, the webhook settings are kept (5 `wmhk_` options, 3 filled), no update is offered, no new lines in `error_log`; home page, /contact/ and wp-login answer 200. No test form was sent (it would create a lead).
Rollback: As `wemakeco`: move `wp-content/plugins/wemake-webhook` aside and move the backup folder back in its place.
Task: https://task.nexfield.day/t/1f244bcb-0313-55f0-8ec7-53ab75b7acfb

## 2026-11-25 - 1.3: updates through the Wemake update server

What: Shared update client (`inc/class-wemake-update-client.php`, started for every request in `wemake-webhook.php`) and the `Update URI` header. Removed the GitHub updater (`inc/admin_update_plugin_github.php` and its require in `inc/admin-settings.php`). Release workflow `.github/workflows/release.yml` (tag-based, draft first, asset `wemake-webhook.zip`) instead of `build-release.yml`. README written, docs added. Version 1.3.
Why: Decision 2026-11-25 "Updates through the Wemake update server".
Verify: After the tag `v1.3` the GitHub release has `wemake-webhook.zip` with the top folder `wemake-webhook/`. Tested locally (WordPress 7.0, PHP 8.3, the update server code with `wemake-webhook` in `WM_CLIENT_UPDATES`): 1.2 with its GitHub updater installed 1.3 from the release zip; 1.3 updated to 1.3.1 through the update server; the settings page renders. PHP lint on 7.4 and 8.3.
Rollback: Publish a new release with the previous code and a higher version; on the update server remove `wemake-webhook` from `WM_CLIENT_UPDATES`.
Task: https://task.nexfield.day/t/1f244bcb-0313-55f0-8ec7-53ab75b7acfb
