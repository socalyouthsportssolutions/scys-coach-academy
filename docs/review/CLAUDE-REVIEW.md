# Coach Academy plugin review — v0.3.0 → 0.3.1

Nov 11, 2026 · @Matt

The Codex build is solid and safe to keep building on: server-side quiz grading, nonces, ownership checks and optimistic locking are all done properly. The main gaps were brand, privacy tooling and a few UX rough edges — 0.3.1 fixes those without touching data, routes or the content migration.

## What I changed in 0.3.1

Every change is additive. No REST routes, database schema, content migration or stored data changed. The PHP lints clean, and the new privacy and sign-in code passed a stub test. The full Playground suite still needs running, because it needs Node 24 and I only had Node 22.

| File | Change | Why |
| --- | --- | --- |
| assets/brand.css (new) | SoCal Youth Sports skin, loaded after hub.css | Matches the brand book; removing one file rolls it back |
| scys-coach-hub.php | Enqueues brand.css; logo from the theme custom logo or site icon (filter `scys_hub_logo_url`); branded sign-in / not-enrolled screen; version 0.3.1 | The signed-out page had no styling or logo, and logged-in but unenrolled users saw a Sign in link |
| includes/privacy.php (new) | Hooks into WordPress Tools › Export / Erase Personal Data, plus suggested privacy-policy text | The readme listed "no privacy export/erase" as a gap; coaches can now request their data |
| uninstall.php (new) | Cleanup only when the option `scys_hub_delete_data_on_uninstall` is 1 | Keeps data by default and gives a deliberate way to remove it |
| assets/hub.js | Completed lessons get an `is-done` class (filled teal tick) | Progress is visible when you scan the list |
| assets/mentoring.js | Dates show as "Mon, Nov 9 · 4:00 PM" instead of `2026-11-09T16:00` | Raw ISO dates were showing on visit cards and in follow-up lists |
| readme.txt | 0.3.1 changelog | — |

Erasure is cautious on purpose. Learning progress and the mentor assignment are erased. Mentoring visit records are kept and reported as "retained" until the retention procedure says otherwise (filter `scys_hub_erase_mentoring`).

## Code review findings

I found no security blockers. The rows below are what I would fix next, most important first. Items marked fixed are done in 0.3.1.

| # | Severity | Finding | Recommendation |
| --- | --- | --- | --- |
| 1 | High | Nothing tells mentors that a reflection or mentoring request is waiting. Reviews will stall. | Send a WordPress email (`wp_mail`) to the assigned mentor when work is submitted, and to the coach when it is reviewed. Add a weekly digest option. |
| 2 | High | No privacy export/erase existed. | **Fixed** (includes/privacy.php) |
| 3 | Medium | `/mentoring` returns every assigned learner's full state, including all reflection text, in one response (cap 200). It will be slow and heavy once real coaches use it. | Return summaries (counts and pending items only), with pagination. Load reflection text on demand. |
| 4 | Medium | The mentor review list only includes the coach and volunteer roles. A mentor who is also learning can't be reviewed. | Select by the `scys_access_hub` capability instead, and exclude the reviewer themself. |
| 5 | Medium | Age groups U6–U14 are hard-coded twice (PHP `preferences()` and hub.js). Programmes start at 18 months. | Move them to one filterable constant and add U4/U5 (toddler) once activities are tagged for those ages. |
| 6 | Medium | Brand teal #2c90a2 is only 3.74:1 on white, which fails WCAG AA for body text. | **Fixed**: text uses #17606c (7.2:1) and brand teal is used for fills and borders. |
| 7 | Low | Not-enrolled users who were signed in still saw a "Sign in" link. The gate was unstyled. | **Fixed** |
| 8 | Low | Raw ISO dates in the mentoring screens. | **Fixed** |
| 9 | Low | `listing()` builds SQL by placing one `prepare()` result inside another. It's safe today because only integer IDs go in, but it's fragile. | Build a single `prepare()` with all placeholders. |
| 10 | Low | No i18n (`__()`), and "Coach Academy" vs "Coach Education Hub" naming is mixed. | Pick one name for the UI. Wrap strings if Spanish support is wanted (see Ideas). |
| 11 | Low | `resource_detail` doesn't check the pathway, so volunteers can open any activity. | Fine if that's intended. Otherwise reuse `allowed()`. |

What's already strong: grading happens on the server and answer keys are never sent; saves use version-checked locking to prevent lost writes; ownership is re-checked on every read; submit tokens stop duplicate saves; collection is off until policy approval is recorded; and pages use no-store and noindex headers.

## Brand stylesheet

`assets/brand.css` follows the brand book's 60/30/10 split: teal for the structure, gray for lines and dividers, and yellow only for the main action on each screen. It changes appearance only. Layout, print and field-card rules stay in hub.css, so dequeuing `scys-hub-brand` rolls back to Codex's look.

| Token | Value | Use | Contrast on white |
| --- | --- | --- | --- |
| Brand teal | #2c90a2 | Fills, borders, icons, progress | 3.74:1, so not for body text |
| Text teal | #17606c | Links, headings, active nav | 7.18:1 |
| Deep teal | #0d3d45 | Header band | — |
| Brand gray | #a6a6a6 | Dividers and the stripe only | 2.43:1, so never for text |
| Muted text | #5b6a6c | Secondary copy | 5.64:1 |
| CTA yellow | #fff619 | Primary button background, with dark ink #0f2a2f | 13.25:1 |

What it adds:

- A deep-teal header with a faint centre-circle pitch motif and a teal/gray/yellow brand stripe.
- The crest logo, pulled from the site's custom logo.
- Yellow pill buttons for the primary action.
- Active nav shown in solid teal with a yellow marker.
- Teal-filled ticks on completed lessons.
- Quiz options that highlight when selected.
- A dark ring with a yellow halo on keyboard focus.
- On mobile, nav tabs scroll sideways instead of wrapping.
- Support for reduced motion and high-contrast mode.

Fonts come from the theme (`--wp--preset--font-family--heading/body`). No external font is loaded, which keeps the private page fast and avoids third-party requests.

Before vs after (dashboard, preview mode): the screenshots are attached in chat.

## Content fixes before pilot

All 16 activities still show draft placeholders to coaches. This is the most visible issue in the preview.

- **"(source)" and "Dimensions not specified / unspecified"** appear on every activity card (Soccer tag, Sharks & Surfers, Home & Away…). Replace them with real SCYS field sizes and player numbers per age, e.g. "U8: 20×15 yd, 4–6 players".
- **Sharks & Surfers** says "as shown in the original diagram" but has no diagram. Add one, or rewrite the setup.
- **Spelling**: UI and lessons use UK spellings (centre, programme, organisation). Switch to US spelling for SoCal families and coaches, or confirm UK is deliberate.
- **Tagline**: the header says "The individual child at the centre." Consider the brand tagline "Redesigning Youth Sports", or keep both (tagline in the header, the child-centred line on the dashboard).
- **Quiz balance is good**: the 39 questions split their correct answers 13 / 13 / 13 across positions, so learners can't game them.

## Ideas to add

These are ranked by value to SCYS: fewer coach no-shows, consistent session quality, and less admin for Matt, Jess and Sammy. "Now" means it is worth building before or during the pilot.

| Idea | What it does | Impact | Effort | When |
| --- | --- | --- | --- | --- |
| Mentor and coach notifications | Email or Slack when a reflection or request is submitted, reviewed or overdue, plus a weekly digest | High | S | Now |
| Compliance tracker | Per-coach checklist with expiry dates: waiver, code of conduct, concussion course, background check, Never Alone sign-off. Coordinators see red/amber/green. | High | M | Now |
| Session planner | Pick 3–4 activities by age and theme, add timings, then print or share a one-page plan. Reuses the existing library and print CSS. | High | M | Now |
| QR codes on field cards | Each printed card links to its activity and session plan, so coaches scan it at the field | Medium | S | Now |
| Coach levels and badges | Levels 1–3 (Assistant → Lead → Mentor) unlocked by lessons plus approved practicals. Can tie to pay rates and staffing decisions. | High | M | Next |
| Observation rubric | Mentors score 4–5 things on a visit (child engagement, touches, talk time, adaptation), then chart progress over time | High | M | Next |
| Short video clips per activity | 20–40 second demo clips (OpusClip from session footage), hosted privately | Medium | M | Next |
| NanoSoccer pathway | A dedicated course on the NanoSoccer 3v3 format with its approved wording | Medium | S | Next |
| New-coach onboarding flow | An invite link creates the account, assigns role and mentor, and starts the pathway. This replaces manual setup in Users. | Medium | M | Next |
| Spanish interface | Wrap strings with i18n and translate the core UI and volunteer track | Medium | M | Later |
| Parent-facing coach profiles | Public "Meet your coach" cards showing completed level and badges (opt-in). Builds trust and helps conversion. | Medium | M | Later |
| Coordinator dashboard | Completion %, overdue reviews and compliance gaps across all coaches on one screen | Medium | M | Later |

Avoid paid LMS add-ons and AI APIs inside the plugin. The current "no third-party" stance is a strength for child-safety and privacy approval.

## Handoff checklist for Codex / Mike

- [ ] Merge `changes-0.3.0-to-0.3.1.diff` into the source repo. Add brand.css to the `build.py` copy list and to the preview `index.html`.
- [ ] Run `Test WordPress in isolation.cmd` (Node 24) and add assertions for the privacy exporter/eraser and the not-enrolled gate.
- [ ] Install 0.3.1 on a disposable or staging site. Check that the theme logo appears, the yellow CTA is visible, and keyboard focus is clear.
- [ ] Run Tools › Export Personal Data for a test coach and confirm the export lists learning, reflections and mentoring.
- [ ] Build finding #1, mentor/coach notifications. It is the biggest pilot risk.
- [ ] Replace the activity placeholders (Content fixes) before any real coach sees the library.
- [ ] Decide on spelling (US/UK) and the header tagline.
