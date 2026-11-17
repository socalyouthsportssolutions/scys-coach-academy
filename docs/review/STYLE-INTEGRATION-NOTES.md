# Claude review and style integration — 17 November 2026

The execution sheet's Actions row 136, A149, records Claude's existing 11 November plugin review and v0.3.1 deliverables. The original source conversation is [WordPress plugin review and branding](https://claude.ai/chat/3f318cde-d2c5-4265-abe6-20d186c137ad); the [original review document](https://claude.ai/artifact/6uboEEgjnGHTyRzdcQHRkH) and downloaded ZIP are preserved under evidence/claude. A delivered review was not evidence that its files had already been merged or installed. This update retrieves and merges that work, then independently tests it.

## Included and preserved

- Claude's separate `assets/brand.css`, completed-lesson ticks, friendly date display, branded signed-out/not-enrolled gate, theme-logo fallback, WordPress privacy tools and default-retaining uninstall routine.
- The proper style-guide Markdown reference, standalone HTML gallery and scoped loading/privacy review addendum requested from Claude on 17 November. These are reviewer documents, not application startup payloads.
- `hub.css` remains the layout, responsive and print foundation. `brand.css` loads afterwards as the presentation layer in WordPress and the local preview; it adds no external font or JavaScript dependency.
- All original lesson/resource bodies and diagrams, permanent IDs, foundation memberships, quizzes and completion rules remain unchanged. No real accounts, notes or external permissions are changed.
- Content migration and operational database schema remain at their existing versions. The package/asset version is 0.3.1; a cosmetic or privacy-hook addition is not a new curriculum migration.

## Codex integration corrections

The original Claude deliverables are retained unchanged as evidence. The merged source includes these small corrections:

1. The build now copies `brand.css` and the preview links it after `hub.css`. Installing only the original plugin ZIP would not have updated the developer preview by itself.
2. Yellow applies to `.hub-button-primary` only. The original broad submit-button selector coloured Save draft and secondary submissions yellow too. Use the primary class for the intended main action; use the plain button class for secondary actions.
3. The sign-in gate gradient ends at text teal `#17606c`, rather than the lighter brand teal `#2c90a2`, to keep white copy on dark surfaces throughout the gradient. This is an integration override to the original gallery/stylesheet where they show the original endpoint.
4. Privacy erasure reports a failed database deletion as retained data with an error message, rather than claiming success. Retention wording describes adult-development operational records and does not imply that safeguarding allegations belong in these notes or that an organisational retention policy has already been approved.
5. Fresh isolated WordPress assertions cover the new privacy and sign-in code. The synthetic erasure/uninstall tests deliberately affect only the disposable in-memory test database.

## Loading contract

The startup manifest contains headings and metadata. Opening a course/category does not retrieve its lesson bodies. A lesson or activity request retrieves only that opened module; original diagrams load separately for that module. Already opened modules may be cached in memory for the current page. Reloading/signing out/persona changes clear the relevant page/session context as described in the handoff. No eager module prefetch, automatic polling or bundled all-in-one lesson HTML was added.

The automated browser trace includes a zero-body startup, category navigation with no body requests, one mentoring lesson, reopening from memory, one illustrated original lesson, the activity-summary library and one opened activity. The existing learner reflection-review endpoint still returns a mentor's assigned roster when that view opens; Claude's proposed roster pagination/detail split remains future work. This is not a claim that every account response has been redesigned.

## Decisions and work kept separate

UK English and the child-centred header wording are retained. A US-spelling or marketing-tagline switch needs Matt's decision. Proposed activity dimensions, new age bands, notifications, reminders, compliance tracking, badges, integrations and session planning are not introduced by a code/style review. Existing source uncertainty is not replaced with invented field measurements.

Real pilot collection remains disabled by default and still needs content, technical/privacy, reporting-procedure, retention/deletion/backup and capacity acceptance. Privacy tools are invoked through WordPress's authorised, confirmed personal-data request workflow; no new learner/public export or erase API is added. Deactivation and default uninstall retain data. The optional erasure filter and uninstall option require a separate deliberate administrative policy decision; this update does not enable them on any real site.

The style guide and passing tests do not establish full accessibility conformance, real-host/MySQL/CDN compatibility, a production security certification or staging/live publication. Mike should run the supplied isolated tests, then review those host-specific items on a private disposable/staging site.

## Final response to Claude’s new findings

The 17 November addendum records the inspected snapshot; its subsequent status note distinguishes these Codex integration changes from Claude’s independent checks. Runtime manifests now omit authoring source file paths, while source originals remain intact. Text/date/date-time/URL controls share the padded 44px field layout. The field border is #7d8b8d with dark teal hover, mobile navigation exposes a thin scrollbar, and disabled controls use a not-allowed cursor. The style guide/gallery reflect these changes. Browser checks verify field dimensions, padding and border colour alongside the existing loading trace; generated manifests are checked for source-path removal.

The dashboard already includes the text “Learning check complete” beside completed lessons. UK English and the child-centred header are established project choices. The minor empty-takeaway presentation on newly admin-authored lessons remains documented for later work; supplied lessons have takeaway content. Before real personal-data erasure, the retention procedure must explicitly address learning completions, reflections and mentor approvals (which the native eraser removes) as well as the separately retained operational mentoring records. No policy or real data was changed.
