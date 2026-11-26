# Wemake Webhook - Decisions

Newest on top. Never delete an entry; mark replaced ones `Status: superseded by <date> <title>`.

## 2026-11-25 - Updates through the Wemake update server

Decided by: Vitalii
Context: Only copies 1.2 had an updater (their own GitHub updater); most copies on our server are 1.05-1.06 with no updater at all. Same plan as the other Wemake plugins the same day (wemake-acs, wemake-cf7-alert, wemake-whatsapp).
Decision: From 1.3 the plugin ships the shared Wemake update client (`inc/class-wemake-update-client.php`) with the `Update URI` header; the GitHub updater is removed. Releases are built by `.github/workflows/release.yml` from a `v*` tag, published only after `wemake-webhook.zip` is attached. Copies 1.2 reach 1.3 through their GitHub updater; copies 1.06 and older get 1.3 by hand once. `wemake-webhook` goes into `WM_CLIENT_UPDATES` on the update server after the first release.
Why: One safe update path for all copies; installs recorded on the update server.
Affects: The repository stays public while copies 1.2 exist. Every release needs a tag that matches the `Version` header and `WMHK_PLUGIN_VERSION`.
Status: active
Task: https://task.nexfield.day/t/1f244bcb-0313-55f0-8ec7-53ab75b7acfb
