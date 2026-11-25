# Wemake Webhook (wemake-webhook)

WordPress plugin that sends Contact Form 7 submissions to a webhook. **Public repository**: no secrets, internal notes or
client site names here. New versions reach sites through the Wemake update server (repository.wemake.co.il) and the
standard WordPress updater; a release is a `v*` tag that matches the `Version` header and `WMHK_PLUGIN_VERSION` in
`wemake-webhook.php` (see README "Making a release"). Pushing a tag publishes a release that every site with the plugin
will be offered: only with Vitalii's OK. Never create a release in the GitHub web UI (see README).
`inc/class-wemake-update-client.php` is a copy from `wemake-digital-agency/wemake-repository`; change it there, not here.
The plugin supports PHP 5.6.40+: no newer PHP syntax in its code.

## Documentation

All project docs are listed in `README.md`. Living docs describe the current state - edit them in place: `README.md`
(updates, releases, updating a site). `docs/DECISIONS.md` and `docs/CHANGELOG.md` are logs, newest on top.

At the end of every task:
- search `README.md` and `docs/` first and update the existing section; never add a second doc on the same topic
- rewrite living sections to match how things work now; name the source file for every number or rule you quote
- log changes (with Verify and Rollback) and decisions (with "Decided by": who proposed, who approved)
- add any new doc to the index in `README.md`
- commit and push the docs together with the work, unless this file restricts git
- no secrets in docs: write where credentials are kept, not the values
