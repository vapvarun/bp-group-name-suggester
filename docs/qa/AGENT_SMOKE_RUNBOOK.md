# Agent Smoke Runbook - BuddyPress Group Name Suggester

**Audience:** a browser-capable agent with Playwright MCP + WP-CLI Bash access, or a human QA person with the same access.

## How to read this runbook

Each C and E step is a **customer contract**: what the feature promises and what "working" looks like. It does not prescribe selectors or exact calls. Read the plugin code (one file, `bp-group-name-suggester.php`, plus `assets/js/script.js` and `assets/css/style.css`), pick the mechanism and verify the contract.

D rows are repros of past incidents; the fixture is the contract.

This plugin has one surface: a suggester panel on the BuddyPress Create Group details step, fed by one logged-in AJAX action `suggest_group_names` (its `wp_ajax_nopriv_` twin only answers the session-expired 403). It has no settings page, no REST routes, no tables, no options, no cron, no blocks, no shortcodes and no widgets.

## Global preconditions

- Working directory: `~/code/bp-group-name-suggester`
- Site URL: `http://buddypress-dev.local` (set `base_url` in `docs/qa/qa-config.json` to the lane's real URL before the first walk)
- WP-CLI: `wp --path="<lane WP root>" <cmd>`
- Admin auto-login: `?autologin=1`; per-user auto-login: `?autologin=<user_login>`
- Plugin version constant: `BP_Group_Name_Suggester::VERSION`
- Surface URL: `<groups directory>/create/step/group-details/` (members need group creation allowed in Settings > BuddyPress > Options)
- Personas: `journey_subscriber` (a member who can create groups) and the admin. Guests never reach the create screen.
- Pair plugin: none (standalone)

## Output contract

Write one JSON file to `~/code/bp-group-name-suggester/docs/qa/.last-smoke-pass.json` with the same shape as the portfolio runbook (`mode`, `release_version`, `ran_at`, `sections` A to F with pass/fail/skipped, `failures`, `debug_log_issues`, `manual_required`, `hard_fails`, `untested`, `coverage`, `layer1_delta`). Every authored row you did not run goes in `untested` with a reason.

## Fixture cleanup (before every walk)

The plugin stores nothing. Cleanup is only the groups a walk created:

```bash
# Delete the groups named "Smoke ..." that the walk created; list first, then delete by id.
wp --path="<lane WP root>" bp group list --fields=id,name
wp --path="<lane WP root>" bp group delete <id> --yes
```

## Debug log protocol

Enable WP_DEBUG + WP_DEBUG_LOG + WP_DEBUG_DISPLAY=false before Section A. Baseline `wp-content/debug.log`, diff after every section into `debug_log_issues[]`. Any new fatal, warning or notice from this plugin is a failure.

---

## A - Fresh install

### A1 - Activation
**What to verify:** with BuddyPress and its Groups component on, activation shows no notice and no debug.log entry, and the Create Group details step shows the suggester panel ("Need Help Naming Your Group?") with the 14 topic buttons.

### A2 - Nothing stored
**What to verify:** after activation and one suggestion request, no option, transient or meta with `bp_gns` or `group_name_suggester` in its name exists.

---

## B - Upgrade and uninstall

### B1 - Update
**What to verify:** updating from the previous zip gives no fatal and the panel still renders. There is no stored state to carry over.

### B2 - Migration resumes after a kill
na: owner policy. No migration or batch job.

### B3 - Uninstall
**What to verify:** delete the plugin from Plugins: no error, and options, usermeta and transients hold nothing of the plugin's (there was nothing to begin with). Groups created with a suggested name are untouched.

---

## C - Core customer flows

Exercise every row at desktop 1440px and mobile 390px.

### C.suggest.topic
**What to verify:** as `journey_subscriber`, click a topic: the button shows as pressed (`aria-pressed="true"`, not colour alone), a loading message is announced, and 20 suggestion cards appear, each with a name, a description and a "Use This Name" button. Names are distinct within one response.

### C.suggest.more
**What to verify:** "Get More Suggestions" appends 10 more for the same topic and never repeats a name already on screen (0 duplicates across 10+ clicks on every topic; the request sends the shown names in `exclude`, one per line). When a topic runs out (about 150 to 200 clicks) the panel says "That is every name for this topic. Try another topic for more ideas." and Get More is hidden. Switching topic clears the grid and loads that topic's names.

### C.suggest.use-name
**What to verify:** clicking a card or its button fills `#group-name` and `#group-desc`; finishing the create flow saves a group with that name and description.

### C.suggest.count-cap
**What to verify:** post `suggest_group_names` directly with a valid nonce and `count=500`: at most 50 suggestions return. `count=0` or a non-number returns at least one.

### C.suggest.failure
**What to verify:** with an expired or wrong nonce the AJAX answers HTTP 403 with "Your session has expired. Reload the page and try again." in `data.message`; the panel shows that message with no "Try again" and no "Get More", since only a reload can recover. Other failures (500, network) show "Suggestions could not be loaded. Please try again." plus a "Try again" button, never a silent empty grid. An empty result (for example `bp_gns_suggestions_output` filtered to an empty array) shows "No suggestions found for this topic. Try another topic." An unknown topic key falls back to the Random Mix vocabulary.

### C.suggest.logged-out
**What to verify:** with the panel open, log out in another tab, then click Get More: the request answers HTTP 403 with the session-expired message, and the panel shows it with Try again and Get More hidden. A bare 400 `0` (nopriv handler missing) is treated the same way by the script.

### C.admin
na: owner policy. The plugin adds no admin screens; its settings come from the `bp_gns_settings` filter only.

### C.notifications / C.cron
na: owner policy. No emails, notifications or cron.

---

## CP - Presentation

### CP.theme-fit
**What to verify:** on BuddyX, Reign and a Twenty-series theme, light and dark, 1440 and 390: the panel inherits the theme font and accent colours, unselected topic buttons stay neutral, only the pressed one carries the accent, no horizontal overflow at 390px, tap targets at least 40px.

### CP.states
**What to verify:** loading, empty, error (with Try again) and filled states each render and are announced through the `role="status"` live region.

### CP.entry-points
**What to verify:** the only frontend entry is the Create Group details step. No admin page and no REST route by design: record both as `na: owner policy` in `coverage`.

### CP.console-and-assets
**What to verify:** zero JS console errors; `assets/js/script.js` and `assets/css/style.css` load only on the Create Group screens, never on other pages.

---

## D - Known-regression guards

| ID | Bug | Fixture + assertion |
|----|-----|---------------------|
| D.groups-off | Plugin fataled or misbehaved with the Groups component off | Turn Groups off: no fatal, an admin sees the notice linking Settings > BuddyPress > Components, no front-end assets load. |
| D.no-bp | Plugin fataled without BuddyPress | Deactivate BuddyPress: the "requires BuddyPress" notice shows, no fatal. |
| D.count-cap | An unbounded `count` could request any number of suggestions | `count=500` returns at most 50. |
| D.silent-failure | A failed request left an empty grid | Force a 403 (bad nonce): error text and Try again show. |
| D.escaping | Heading filter and notice output were unescaped | Filter `bp_gns_heading_text` to return `<script>`: it renders as text. |

---

## E - Integrations

### E.buddypress
**What to verify:** on BuddyPress (Nouveau and Legacy template packs) the panel renders above the details form on the group-details step only, and "Use This Name" fills that pack's name and description fields.

### E.buddyboss
**What to verify:** on BuddyBoss Platform the panel renders on the Create Group details step and fills the name and description fields; with BuddyBoss Groups off the notice behaves as D.groups-off.

## E2 - Environment

| Row | Walk | Pass bar |
|---|---|---|
| E2.php-latest | C rows with WP_DEBUG on PHP 8.5 | No deprecations from this plugin |
| E2.cache | Page cache on, wait past nonce life, click a topic | Expired-session error with Try again, never a silent failure |
| E2.textdomain | WP_DEBUG on WP 6.7+ | No `_load_textdomain_just_in_time` notice for this plugin |
| E2.multisite | Network-activate on a BuddyPress multisite | Panel works on the root blog create screen, no fatal |

## F - Cross-browser, RTL, accessibility

- **F.firefox / F.safari-ios:** add the topic click and Use This Name to `manual_required[]`.
- **F.rtl:** on an RTL locale the topic buttons and cards flow right to left without overflow.
- **F.a11y:** topic buttons are reachable by keyboard with visible focus, the group of buttons is labelled, and every state is announced in the live region.
