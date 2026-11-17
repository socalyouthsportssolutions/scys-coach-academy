# Coach Academy v0.3.1 — review addendum (integration and style)

**Date:** 17 November 2026
**Reviewer:** Claude
**Input:** `STYLE-RECOVERY-INPUT.zip` (current merged source, SHA-256 `489c487f2c38…`)
**Follows:** `CLAUDE-REVIEW-2026-11-11.md` and execution sheet action A149
**Companion files:** `STYLE-GUIDE.md`, `STYLE-GUIDE.html`

## Status update (later on 17 November 2026)

Codex reports applying recommendations 5.1–5.4 as a subsequent integration step. **These fixes are Codex-reported. Claude has not inspected the committed code or rerun any tests or screenshots for them.** Section 5 is kept unchanged below as the history of what was found.

| Finding | Codex-reported change |
|---|---|
| 5.1 Local file paths | `build.py` deep-copies the authoring catalogue and drops `sources[*].file` from both the generated plugin index and the preview manifest. The originals stay in the authoring source. |
| 5.2 Short fields | `hub.css` adds text, date, datetime-local and URL inputs to the existing padded, full-width, 44 px field rule. |
| 5.3 Field-border contrast | `brand.css` uses `#7d8b8d` field borders (3.53:1 on white) and `--scys-teal-700` on hover. |
| 5.4 Mobile navigation | The nav row has a thin visible scrollbar: a 6 px WebKit thumb in teal-700. |
| 5.5 Disabled cursor | Disabled buttons now use `cursor: not-allowed`. |

`STYLE-GUIDE.md` and the CSS embedded in `STYLE-GUIDE.html` are synced to these changes. Claude made the edits to its copies from Codex's description, so Codex's committed files are authoritative if they differ.

**Correction to 5.5:** the dashboard already shows "Learning check complete" beside the ✓ in `hub.js`, so completion is not conveyed by the glyph alone. No change is needed.

**Still open:** the empty "Take it to the field" box (5.5) and the retention question on erasing learning and approval records (5.6).

## 1. Scope

This addendum reviews how Codex merged Claude's 11 November v0.3.1 work (brand stylesheet, sign-in gate, privacy tools, uninstall) and records the style guide and component gallery that were lost when the earlier session's files could not be downloaded. Those working files were no longer available, so all three documents were recreated from the merged source.

Nothing in the plugin was changed. No features, notifications, policy wording or settings were added. Every item in section 5 is a recommendation for Mike and Codex to accept, defer or reject.

**Files reviewed:** `scys-coach-hub.php`, `includes/privacy.php`, `includes/content-store.php`, `includes/mentoring-store.php`, `uninstall.php`, `assets/hub.css`, `assets/brand.css`, `assets/hub.js`, `assets/mentoring.js`, `build.py`, `source/catalogue.json`, `source/preview.html`, `STYLE-INTEGRATION-NOTES.md`.

**Not in the ZIP, so not reviewed:** the built `content/` lesson, activity and media modules, the test suites and their logs, `source/start-preview.py`, and the per-item JSON under `source/content/`. `build.py` could not be run for the same reason.

## 2. Evidence: what was tested, and by whom

These are two different kinds of evidence and should be reported separately.

### 2.1 Reported by Codex (not rerun by Claude)

| Check | Reported result |
|---|---|
| Isolated WordPress assertions, WordPress 6.6.11 / PHP 8.1 | 111 passed |
| Isolated WordPress assertions, WordPress 6.9.11 / PHP 8.3 | 111 passed |
| PHP lint | 41 files clean on each of the two configurations |
| Workflow browser checks | 18 passed |
| Network and brand loading checks | 15 passed |

Claude has not seen the test logs and did not rerun these suites.

### 2.2 Claude's own checks

| Check | Result |
|---|---|
| Line-by-line reading of the files listed in section 1 | Findings below |
| JavaScript syntax (`node --check hub.js`, `node --check mentoring.js`) | Both pass |
| PHP lint | **Not run**: no PHP interpreter in Claude's environment. Relies on Codex's result above |
| Contrast ratios for every brand token pairing and gradient (WCAG relative-luminance formula) | Tables in `STYLE-GUIDE.md` section 7.3 |
| `STYLE-GUIDE.html` rendered in headless Chromium at 1280 px and 390 px | No network requests, no console errors, no sideways page scrolling |

### 2.3 Not established by anyone yet

Staging or live installation, a real theme, MySQL rather than the isolated test database, caching or CDN behaviour, screen-reader testing, and a full accessibility audit. Nothing in this addendum or the test results above claims the plugin is installed, published or WCAG-conformant.

## 3. Integration corrections: confirmed in the code

| Correction | What the code does | Status |
|---|---|---|
| Yellow only on primary buttons | `brand.css` sets the yellow fill only on `.hub-button-primary`. There is no `[type=submit]` or broad button selector. In the scripts, yellow-fill buttons are Check my answers, Submit for review / Update reflection, Save feedback, Save request and the main card action; other buttons are plain `.hub-button`. | Confirmed |
| Dark sign-in gradient for white text | `.scys-gate` gradient runs `#0d3d45` → `#17606c` (teal-700). White text is 11.86:1 at the dark end and 7.18:1 at the light end; 90 % white paragraph text is at least 6.16:1. | Confirmed |
| Honest privacy deletion-failure reporting | When the erase filter is on and `$wpdb->delete()` returns `false`, the eraser sets `items_retained`, does not set `items_removed`, and adds "Mentoring deletion could not be confirmed. Records were retained; ask an authorised administrator to investigate." | Confirmed |
| Retention defaults remain on | `scys_hub_erase_mentoring` defaults to `false`, so mentoring records are reported as retained with a count. `uninstall.php` returns immediately unless `scys_hub_delete_data_on_uninstall` is exactly `'1'`. Neither default is changed. | Confirmed |
| Not-enrolled visitors see no "Sign in" link | Signed-out visitors get Sign in and Reset password; signed-in but not enrolled visitors get only Sign out. | Confirmed |

Yellow still appears in five non-button places: the focus halo, the active-nav bar, the active-nav icon, the progress-bar tip and the end of the header stripe. These are accents rather than fills and none sits behind text other than ink. They are recorded in the style guide; no change is recommended.

## 4. Loading contract: confirmed in the code

| Requirement | Evidence in code |
|---|---|
| Start-up loads metadata only | `load()` requests only `catalogue` and `me` (preview: `catalogue.json`). The manifest is filtered to the person's pathway on the server. |
| Opening a course or category loads no bodies | Those views render from the in-memory manifest; no request call. |
| Only the opened lesson or activity body is fetched | `loadDetail(group, id)` requests `lessons/{id}` or `resources/{id}` for that one item, after checking it is in the person's catalogue. The server checks the pathway again and strips quiz answers from lesson bodies. |
| Diagrams load separately, only for that item | After the body arrives, each `mediaIds` entry is requested from `media/{id}`, and the server refuses media outside the person's lessons and activities. |
| No bulk preload | No `prefetch`, `preload`, `setInterval`, `requestIdleCallback` or loop over all items in `hub.js` or `mentoring.js`. Opened items are held in an in-memory `Map` for the page only. |
| Private responses | Manifest and detail responses are sent with `Cache-Control: private, no-store, max-age=0`. |

Separately, and as Codex's notes say, the mentor's reflection-review view still loads the assigned roster when opened, and the mentoring support list is paged. That is unchanged and out of scope here.

The style guide and gallery respect the contract: the gallery contains sample text and a placeholder drawing only, and the guide forbids style changes that would inline or preload lesson or diagram content.

## 5. Findings and recommendations

None of these blocks running Mike's isolated tests. Items 5.1–5.3 are worth deciding before a pilot with real coaches.

### 5.1 Local file paths are sent to every enrolled user (low, data hygiene)

`source/catalogue.json` lists each source guide with a `file` value such as `C:/Users/matth/Downloads/Coach Guide PDFs/…`. `build.py` copies the whole catalogue into `content/index.php`, and `catalogue()` returns `sources` unchanged, so every enrolled user's browser receives the paths, which include a Windows username and folder names. The interface only uses each source's `title`. The preview's `catalogue.json` contains the same paths.

**Recommendation:** drop `file` from `sources` when building (or when serving the manifest). This changes no behaviour and no content. It needs Mike's approval because it touches the build.

### 5.2 Text, date and URL fields are under the 44 px target (medium, usability)

`hub.css` gives padding, full width and a 44 px minimum height only to search, select and textarea. `brand.css` adds a border to text, date, date-time and URL fields but not the sizing, so those fields render at about 24–28 px tall with no inner padding (visible in the gallery's Forms section). The mentoring support form uses these types. A theme may hide this on the real site; the isolated preview will not.

**Recommendation:** add those input types to the existing `hub.css` field rule. Layout belongs in `hub.css`, not the brand layer.

### 5.3 Field borders are below 3:1 contrast (medium, accessibility)

The field border `#c9d2d3` is 1.54:1 against white (the base `#b8ccca` is 1.68:1). WCAG 2.2 success criterion 1.4.11 asks for 3:1 for the visual boundary that identifies a control.

**Recommendation:** a border of about `#7d8b8d` (3.53:1) or `--scys-slate-600` (5.64:1). This is a visible change to the brand look, so Matt should see it in the gallery first.

### 5.4 Mobile navigation hides items without a cue (low, usability)

At 700 px and below, `brand.css` changes the base layout (which wrapped the seven nav buttons onto two lines) to one row that scrolls sideways with its scrollbar hidden. At 375 px, "Activity pathways" is cut off and the remaining items are off-screen with no visual hint. Keyboard users are fine because focus scrolls the row.

**Recommendation:** show a thin scrollbar or a fade at the right edge, or go back to the base wrapping row.

### 5.5 Small items (low)

* **Header contrast margin.** The 90 % white identity text at the far right of the header is about 4.7:1, just above 4.5:1. Do not lighten the header gradient.
* **Done marker.** The white ✓ on brand teal is 3.74:1. That passes as a graphic, but in the dashboard lesson list the ✓ is the only sign a lesson is complete (the category list also says "Complete"). Adding visually hidden "Complete" text would help screen-reader users.
* **Disabled cursor.** `hub.css` gives disabled buttons `cursor: wait`. "Save request" is disabled when mentoring is switched off, which is not a wait. `not-allowed` or `default` would be clearer.
* **Fragile nav indicator.** The desktop active-nav bar sits at `left: -13px`, which depends on the sidebar's 13 px padding. If that padding changes, the bar needs moving too. This is noted in the style guide.
* **Empty "Take it to the field" box.** `hub.js` always renders the "Take it to the field" note. Lessons created in WordPress admin default `takeaway` to an empty string (`content-store.php`), so they show the heading over an empty box. Render the note only when `takeaway` has text.
* **Gate logo.** The gate logo uses `loading="lazy"` although it is at the top of the gate. This is harmless but may delay the logo slightly.

### 5.6 Questions for the retention policy (no code change)

* An erasure request always deletes the person's whole learning record (`_scys_hub_state`): completed lessons, quiz attempts, reflections and the mentor feedback and approvals attached to them. Only mentoring visit records are protected by the retention default. If SCYS needs to keep proof that a coach completed training, the retention procedure should say so before erasure requests are handled.
* Export and erase are keyed to the coach's own records. Visit notes a mentor wrote about another coach live in that coach's record, so they are neither exported nor erased when the mentor makes a request. That is consistent with the current retention approach but should be stated in the retention procedure.
* Turning on `scys_hub_erase_mentoring` or the uninstall cleanup option remains a separate, deliberate administrative decision. Nothing here enables either.

## 6. Decisions still with Matt

* Whether learning completion and mentor approvals must be kept when a coach asks for erasure (5.6), before any real erasure request is handled.

UK English and the child-centred header line ("The individual child at the centre.") are established choices, not pending decisions. The field-border colour is settled by the Codex-reported `#7d8b8d` change.

## 7. For Mike's isolated-test ZIP

Add `STYLE-GUIDE.md`, `STYLE-GUIDE.html` and `REVIEW-ADDENDUM.md` alongside `review/claude/CLAUDE-REVIEW-2026-11-11.md`. They are reviewer documents. Keep them out of the plugin folder and the release ZIP so they never become part of the plugin's start-up payload.

Suggested order: run the isolated suites as supplied, open `STYLE-GUIDE.html` offline to check the look, decide on 5.1–5.4, and only then install on a private, disposable staging site for the host-specific checks in section 2.3.
