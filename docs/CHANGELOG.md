# Wemake Webhook - Changelog

Releases and other changes, newest on top. Every entry: What, Why, Verify, Rollback, Task.
Versions before 1.3 have no entries here; see the git history.

## 2026-11-25 - 1.3: updates through the Wemake update server

What: Shared update client (`inc/class-wemake-update-client.php`, started for every request in `wemake-webhook.php`) and the `Update URI` header. Removed the GitHub updater (`inc/admin_update_plugin_github.php` and its require in `inc/admin-settings.php`). Release workflow `.github/workflows/release.yml` (tag-based, draft first, asset `wemake-webhook.zip`) instead of `build-release.yml`. README written, docs added. Version 1.3.
Why: Decision 2026-11-25 "Updates through the Wemake update server".
Verify: After the tag `v1.3` the GitHub release has `wemake-webhook.zip` with the top folder `wemake-webhook/`. Tested locally (WordPress 7.0, PHP 8.3, the update server code with `wemake-webhook` in `WM_CLIENT_UPDATES`): 1.2 with its GitHub updater installed 1.3 from the release zip; 1.3 updated to 1.3.1 through the update server; the settings page renders. PHP lint on 7.4 and 8.3.
Rollback: Publish a new release with the previous code and a higher version; on the update server remove `wemake-webhook` from `WM_CLIENT_UPDATES`.
Task: https://task.nexfield.day/t/1f244bcb-0313-55f0-8ec7-53ab75b7acfb
