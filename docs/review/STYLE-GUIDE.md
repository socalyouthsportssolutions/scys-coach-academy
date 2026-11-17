# SCYS Coach Academy — style guide

**Plugin:** SCYS Coach Hub (Coach Academy), package/asset version 0.3.1
**Prepared by:** Claude, 17 November 2026, from the current merged source (`STYLE-RECOVERY-INPUT.zip`)
**Companion files:** `STYLE-GUIDE.html` (offline component gallery) and `REVIEW-ADDENDUM.md`
**Codex packaging note:** The gallery now embeds the final packaged CSS byte-for-byte (apart from surrounding newlines), and the hashes below refer to the packaged source. Claude’s original downloadable files are preserved in `evidence/claude/CLAUDE-STYLE-GUIDE-ORIGINAL.zip`. No Git commit is claimed by this note.

**Sync note:** updated the same day for the changes Codex reports applying after the addendum (recommendations 5.1–5.4). Claude applied those CSS edits to its copies from Codex's description and did not rerun any tests. If the hashes below differ from Codex's committed files, the committed files are authoritative.

This guide describes the stylesheets as they exist in the merged source. It does not add tokens, components or behaviour. Where it recommends something, the recommendation is marked as such and is a suggestion for a later, separately approved change.

Reviewed files (SHA-256, first 12 characters):

| File | Bytes | SHA-256 |
|---|---|---|
| `assets/hub.css` | 13,030 | `b4e8d5a8b481…` |
| `assets/brand.css` | 15,344 | `708e777fa5dc…` |
| `assets/hub.js` | 34,340 | `11891be8eb61…` |
| `assets/mentoring.js` | 20,983 | `5f1087c39526…` |

If either CSS hash changes, this guide and the HTML gallery need refreshing (see "Maintaining this guide").

---

## 1. How the two stylesheets fit together

| Layer | File | Owns | Rollback |
|---|---|---|---|
| Foundation | `hub.css` | Layout grid, sidebar shell, spacing, base type scale, responsive breakpoints, print and printable field cards, base `--hub-*` tokens | Required. Do not remove. |
| Brand | `brand.css` | `--scys-*` brand tokens, remapping of `--hub-*` tokens, colour, radius, shadow, focus style, header, buttons, chips, forms, quiz states, sign-in gate | Dequeue the `scys-hub-brand` handle (or delete the file) to return to the base look. |

Load order is fixed: WordPress enqueues `scys-hub` then `scys-hub-brand` with `scys-hub` as a dependency, and the local preview links `hub.css` then `brand.css`. `brand.css` re-skins only; it must never take on layout, breakpoint or print responsibilities that `hub.css` owns.

Everything is scoped to `#scys-hub` except the sign-in gate, which is scoped to `.scys-gate` because PHP renders it outside the app container. No styles leak to the theme, and neither file loads fonts, images or scripts from outside the plugin. The only external value is the optional logo URL, injected inline from the theme's custom logo or site icon as `--hub-logo`.

---

## 2. Colour tokens

### 2.1 Brand book

The brand proportions are teal 60 %, grey 30 %, yellow 10 %. In the interface this means: teal and its tints carry structure, grey carries borders and quiet surfaces, and yellow is reserved for emphasis.

### 2.2 `--scys-*` tokens (defined on `#scys-hub`)

Contrast ratios below were calculated against the stated background using the WCAG 2.x relative-luminance formula.

| Token | Value | Use | Contrast |
|---|---|---|---|
| `--scys-teal` | `#2c90a2` | Brand primary: fills, borders, icons, progress, kicker rule, card top edges | 3.74:1 on white. Graphics and large/bold text only, **never body-size text** |
| `--scys-teal-50` | `#eef7f9` | Soft surfaces: chips, notes, status, hover, selected quiz option | Surface |
| `--scys-teal-100` | `#d6edf1` | Chip border | Decorative |
| `--scys-teal-200` | `#a9d6df` | Secondary button border, link underline, hover borders | Decorative (1.57:1 on white) |
| `--scys-teal-700` | `#17606c` | **Accessible text teal**: links, kicker, chip text, secondary button text, active nav fill | 7.18:1 on white, 6.60:1 on teal-50 |
| `--scys-teal-900` | `#0d3d45` | Deep surfaces: header start, gate start, logo mark text, primary button underline | White on it 11.86:1 |
| `--scys-gray` | `#a6a6a6` | Brand secondary: header stripe, dashed sidebar divider | 2.43:1 on white. Decoration only |
| `--scys-gray-100` | `#f1f2f2` | Nav hover, progress track | Surface |
| `--scys-gray-200` | `#e2e4e4` | Card and list borders | Decorative |
| `--scys-slate-600` | `#5b6a6c` | Muted text (`--hub-muted`) | 5.64:1 on white, 5.29:1 on page background |
| `--scys-ink` | `#0f2a2f` | Body text, headings, text on yellow, focus outline | 15.08:1 on white |
| `--scys-yellow` | `#fff619` | **Primary button fill**; also focus halo and small decorative accents (see 2.4) | Ink on it 13.25:1 |
| `--scys-yellow-press` | `#f2e600` | Primary button hover | Ink on it 11.56:1 |
| `--scys-success` | `#1d7a52` | Correct-answer feedback edge | 5.31:1 on white |
| `--scys-success-50` | `#e8f5ee` | Correct-answer feedback surface | Surface |
| `--scys-warn-50` | `#fff8d6` | Preview banner, warm chip, "try again" feedback | Surface |
| `--scys-danger` | `#a3283a` | Error text and edge | 6.39:1 on danger-50 |
| `--scys-danger-50` | `#fdeef0` | Error surface | Surface |

Literal values used in `brand.css` outside the token set: `#f6f8f8` (page background, `--hub-bg`), `#125460` (header gradient midpoint), `#7d8b8d` (field border, 3.53:1 on white), `#f0e3a0` (warm chip and banner border), `#4d4210` and `#5b4c0c` (text on warm surfaces, 9.35:1 and 7.91:1), `#c9a400` ("try again" feedback edge). If you touch any of these, consider promoting them to tokens in the same change.

### 2.3 How the brand maps onto the base tokens

`brand.css` overrides the foundation tokens so `hub.css` rules pick up the brand automatically:

| Base token | Brand value |
|---|---|
| `--hub-bg` | `#f6f8f8` |
| `--hub-paper` | `#fff` |
| `--hub-ink` | `--scys-ink` |
| `--hub-muted` | `--scys-slate-600` |
| `--hub-line` | `--scys-gray-200` |
| `--hub-teal` | `--scys-teal` |
| `--hub-dark` | `--scys-teal-700` |
| `--hub-soft` | `--scys-teal-50` |
| `--hub-warm` | `--scys-warn-50` |
| `--hub-red` | `--scys-danger` |

New component CSS should use the `--hub-*` names where a base equivalent exists, so it still works if the brand layer is rolled back.

### 2.4 Colour rules

1. **Yellow fills only the primary button.** `.hub-button-primary` is the only button with a yellow background. Secondary actions (Save draft, Back, Cancel, secondary submits) use plain `.hub-button`. This was an integration correction: an earlier broad submit-button selector also turned secondary submits yellow. Use at most one primary button per form or decision point.
2. Yellow also appears, never as a text colour and never behind anything except ink, in: the focus halo, the active-nav indicator bar, the active-nav icon (6.30:1 on teal-700), the last 6 px of the progress bar, and the final 10 % of the header stripe. These are accents, not calls to action. Do not add new yellow surfaces.
3. Brand teal `#2c90a2` is never used for body-size text. Use `--scys-teal-700`.
4. Brand grey `#a6a6a6` is never used for text. Use `--scys-slate-600` for muted text.
5. White text sits only on teal-700 or darker. The sign-in gate gradient therefore runs teal-900 to **teal-700** (`#0d3d45` → `#17606c`), keeping white copy at 7.18:1 or better across the whole surface. Do not change the gate's end colour back to brand teal.
6. Status is never shown by colour alone: every feedback and status block also has text, and quiz results are announced in a live region.

---

## 3. Typography

| Role | Family token | Size / weight | Source |
|---|---|---|---|
| Body | `--scys-font-body`: theme `--wp--preset--font-family--body`, then theme system font, then `system-ui, -apple-system, "Segoe UI", Roboto, sans-serif` | 15 px / 1.6 | `hub.css` sets size; `brand.css` sets family |
| Display (h1, h2, brand name) | `--scys-font-display`: theme `--wp--preset--font-family--heading`, then Montserrat, Poppins, system UI | h1 `clamp(25px, 3vw, 36px)`, weight 800, -0.02em; h2 22 px, weight 750, -0.01em | Both |
| h3 | Body family | 17 px / 1.45, weight 700; teal-700 inside lessons | `hub.css`, `brand.css` |
| Lesson title (`.hub-reading h2`) | Display | 28 px (25 px on mobile) | `hub.css` |
| Kicker (`.hub-kicker`) | Body | 11 px, weight 800, uppercase, 0.14em tracking, teal-700, with an 18 × 3 px teal rule | Both |
| Small / meta (`.hub-small`, `.hub-sub`) | Body | 12–14 px, muted | `hub.css` |
| Chip | Body | 11 px, weight 750 | Both |
| Textarea | Body | 16 px, weight 400 (prevents iOS zoom) | `hub.css` |

The plugin loads no web fonts. Montserrat and Poppins only apply if the visitor's device or the theme already provides them; otherwise the system stack is used. That is deliberate (no third-party font request, no layout shift) and should stay that way unless a font is self-hosted through the theme.

Writing headings: sentence case (Session library, My learning, Our foundations). The kicker is the only uppercase treatment.

---

## 4. Spacing, radius, elevation and motion

The spacing scale is set in `hub.css` and is not tokenised. The values in use are:

| Context | Desktop | ≤ 1000 px | ≤ 700 px |
|---|---|---|---|
| Main content padding | 30 px | 24 px | 20 px × 14 px |
| Card padding | 24 px | 24 px | 20 px |
| Lesson card padding | 32 px | 32 px | 21 px |
| Grid / stack gap | 20 px | 20 px | 20 px |
| Resource grid gap | 16 px | 16 px | 16 px |
| Action row gap | 10 px, 18 px above | same | same |
| Header padding (brand) | 20 px × 28 px | same | 16 px × 18 px |

Keep new spacing on the existing steps (4, 8, 10, 12, 14, 16, 18, 20, 24, 28, 32 px) rather than inventing new values.

| Token | Value | Use |
|---|---|---|
| `--scys-radius-sm` | 8 px | Note corners |
| `--scys-radius` | 14 px | Cards |
| `--scys-radius-lg` | 20 px | App container, gate |
| (literal) | 10–12 px | Inputs, quiz options, nav items, details panels |
| (literal) | 999 px | Buttons and chips (pill shape) |
| `--scys-shadow-1` | Two very soft teal-tinted shadows | Cards, active nav |
| `--scys-shadow-2` | `0 10px 30px -12px` teal-tinted | App container, hovered resource card |
| `--scys-ease` | `cubic-bezier(.2, .7, .2, 1)` | All transitions |

Motion is limited to short responses to user action: colour and border transitions (0.15 s), a 2 px lift on hovered resource cards (0.18 s) and a 1 px press on buttons. There is no entrance animation, autoplay or looping motion. View changes jump instantly (`scrollIntoView({behavior: 'instant'})`).

---

## 5. Components and states

Every component below is rendered in `STYLE-GUIDE.html` using the real CSS.

### 5.1 App shell

`#scys-hub` → `.hub-top` (header) → optional `.hub-demo` (preview banner) → `.hub-shell` (205 px sidebar + main column).

**Header (`.hub-top`).** Deep teal gradient (teal-900 → `#125460` at 55 % → brand teal at 140 %, so the visible right edge is a mid teal), faint centre-circle "pitch" lines, and a 4 px stripe in teal / grey / yellow. Contents: `.hub-mark` (logo, or the "SCYS" text mark when no logo), `.hub-brand` "Coach Academy", `.hub-tagline` "The individual child at the centre.", and `.hub-user` (identity and sign-out) pushed right. With `data-has-logo` on the container, the mark becomes a 52 px white circle showing `--hub-logo`.

**Preview banner (`.hub-demo`).** Warm surface, explains that preview progress stays in the browser and personas are not real logins. Only appears in preview mode.

**Sidebar navigation (`.hub-nav`).** Buttons with an `aria-hidden` symbol and a text label. States:

| State | Treatment |
|---|---|
| Default | Muted text, transparent |
| Hover | Grey-100 surface, ink text |
| Current (`aria-current="page"`) | Teal-700 fill, white bold text, yellow left bar, yellow symbol |
| Focus | Ink outline + yellow halo |

Lesson, category and course views keep "My learning" current; an opened activity keeps "Session library" current; tasks keep the practical-tasks item current.

### 5.2 Headings block

`.hub-heading` holds `.hub-kicker`, `h1` (programmatically focusable with `tabindex="-1"`, focused on every view change) and `.hub-sub`.

### 5.3 Cards

`.hub-card`: white, grey-200 border, 14 px radius, shadow-1. The first card of the dashboard grid and every lesson card get a 4 px brand-teal top edge. Cards in `.hub-resource-grid` lift 2 px on hover (disabled under reduced motion).

### 5.4 Buttons

| Class | Look | When to use |
|---|---|---|
| `.hub-button.hub-button-primary` | Yellow pill, ink text, teal-900 2 px underline | The one main action: Check my answers, Submit for review / Update reflection, Save feedback, Save request, the main card action |
| `.hub-button` | White pill, teal-200 border, teal-700 text | Every other action, including secondary submits |
| `.hub-link-button` | Text-only, teal-700 | Inline navigation such as Back |

States: hover (secondary gets teal border and teal-50 fill; primary darkens to yellow-press), active (1 px press), focus (ink outline + yellow halo), disabled (65 % opacity, `not-allowed` cursor, from `hub.css`). All buttons are at least 44 px tall.

### 5.5 Chips, numbers and progress

* `.hub-chip`: teal-50 pill with teal-700 text, for age bands and themes. `.hub-chip-warm` for cautions.
* `.hub-number`: 25 px circle with the lesson number; `.is-done` fills it brand teal and shows ✓. The dashboard also says "Learning check complete" beside the tick, so completion is not shown by the glyph alone.
* `progress`: 10 px grey-100 track, teal fill with a yellow tip, paired with a `.hub-progress-label` text line so progress is never shown by the bar alone.

### 5.6 Lists and rows

`.hub-list` (numbered lessons with `.hub-list-title` buttons) and `.hub-row-link` (full-width row buttons with a bold title and muted meta line). Hover turns the title teal-700.

### 5.7 Forms

Search, text, date, date-time, URL, select and textarea share one `hub.css` field rule: 10 × 12 px padding, full width and 44 px minimum height. `brand.css` gives them a 1.5 px `#7d8b8d` border (3.53:1 on white), 10 px radius and white background; hover turns the border `--scys-teal-700`. Search, select and support-form fields use 16 px text on small screens. Checkboxes and radios use `accent-color: teal-700`. Labels sit above fields (`label > input`), never as placeholder-only text. The mentoring support form uses `<details>` panels that gain a teal-200 border when open.

### 5.8 Lessons and quizzes

* `.hub-reading` limits lessons to 840 px. h3s are teal-700.
* `.hub-note`: teal-50 callout with a 4 px brand-teal left edge.
* `.hub-figure`: original diagram, max 620 px wide, grey border, caption below.
* `.hub-question` is a `fieldset` with a `legend`; `.hub-option` labels wrap each radio. The selected option gets a teal border, teal-50 fill and inset left bar (`:has(input:checked)`).
* Feedback: `.hub-feedback` (green edge, correct), `.hub-feedback-wrong` (warm surface, gold edge, try again), `.hub-feedback-error` (danger). The result line is `aria-live="polite"`.
* `.hub-sources`: muted source line showing guide title and page.

### 5.9 Status messages

`.hub-status` (`role="status"`, `aria-live="polite"`) shows a teal-50 surface with a teal edge; `data-error="true"` switches to danger. It is hidden when empty.

### 5.10 Mentoring and field cards

`.hub-stage` headings get a 10 px teal dot. `.hub-field-card` is the printable blank card; print rules in `hub.css` give each card its own page and hide everything else when blank cards are printed.

### 5.11 Sign-in gate (`.scys-gate`)

Rendered by PHP when the visitor cannot open the hub. Dark teal-900 → teal-700 gradient, optional 76 px logo circle (`alt=""`, decorative), h2 "Coach Academy", one line of explanation and pill links at least 44 px tall.

| Visitor | Message | Actions |
|---|---|---|
| Signed out | "Sign in with your invited account to open your learning pathway." | Sign in (yellow, primary), Reset password (outline) |
| Signed in, not enrolled | "Your account has not been enrolled yet. Please contact your programme coordinator." | Sign out (outline) only; no misleading "Sign in" link |

---

## 6. Responsive behaviour

| Width | Behaviour |
|---|---|
| > 1000 px | 205 px sidebar; dashboard two columns (1.5 : 1); library toolbar three columns |
| ≤ 1000 px | 175 px sidebar; dashboard single column; toolbar two columns with search full width |
| ≤ 700 px | Sidebar becomes a single horizontal row of nav buttons that scrolls sideways with a thin visible scrollbar (6 px WebKit thumb in teal-700; `scrollbar-width: thin` elsewhere), symbols and sidebar footer hidden, the active marker becomes a 3 px underline; container loses radius and shadow; resource grid single column; inputs 16 px |
| ≤ 600 px | Mentoring support grid single column |
| Print | Header, sidebar, banner, actions and status hidden; blank field cards one per page |

Rules for new work: keep `minmax(0, 1fr)` and `min-width: 0` on grid children so long words cannot force horizontal scrolling; keep 44 px targets; keep 16 px text on mobile form fields.

---

## 7. Accessibility

### 7.1 Keyboard and focus

* Every interactive element is a native `button`, `a`, `input`, `select`, `textarea` or `summary`. Keep it that way: do not attach click handlers to `div` or `span`.
* Focus indicator (brand layer): 3 px ink outline, 2 px offset, plus a 6 px yellow halo. The two parts cover each other's weak backgrounds: the ink ring is 15:1 on white, and the yellow halo stands out on the dark header and the teal-700 current nav item, where ink alone would be faint (1.27:1 and 2.1:1). Do not remove either half.
* On view change, focus moves to the new `h1` (`tabindex="-1"`) without smooth scrolling, so keyboard and screen-reader users land on the new content.
* In forced-colours mode the halo disappears (box-shadows are dropped), the outline remains, the primary button gets a `ButtonText` border and the current nav item a `Highlight` outline.

### 7.2 Reduced motion

`@media (prefers-reduced-motion: reduce)` removes transitions inside `#scys-hub` and `.scys-gate` and cancels the resource-card lift. View changes already use instant scrolling. Any new animation must be wrapped in the same media query or be removed by it.

### 7.3 Contrast summary

| Pairing | Ratio | Status |
|---|---|---|
| Ink on white | 15.08:1 | Pass |
| Teal-700 text on white / teal-50 | 7.18:1 / 6.60:1 | Pass |
| Muted slate on white / page background | 5.64:1 / 5.29:1 | Pass |
| Ink on yellow (primary button) | 13.25:1 | Pass |
| White on gate gradient (darkest → lightest) | 11.86:1 → 7.18:1 | Pass |
| White on header at visible right edge | ≈ 5.4:1 | Pass |
| Identity text (white 90 %) at header right edge | ≈ 4.7:1 | Pass, narrowly. Do not lighten the header gradient further |
| White ✓ on brand-teal done marker | 3.74:1 | Pass as a graphic (3:1); the dashboard also shows "Learning check complete" text |
| Field border `#7d8b8d` on white | 3.53:1 | Pass (3:1 non-text guideline) |

These are calculated values, not a full accessibility audit (see addendum).

### 7.4 Structure and announcements

Navigation is a labelled `nav`; quiz questions are `fieldset`/`legend`; status, quiz result, save and review labels are polite live regions; decorative symbols are `aria-hidden`; the gate is a `section` labelled by its heading.

---

## 8. Voice and language

* **UK English is the established standard** (centre, programme, organisation, colour). Write all new copy in UK English and do not mix spellings.
* **Child-centred.** The header line "The individual child at the centre." is the established header and stays. Write about what the child experiences and what the coach does for them: "Give every child lots of touches", not "Run the drill". Never shame children or coaches; frame mistakes as learning.
* **Warm, direct and plain.** Sentence case, active verbs, short sentences. Buttons say what happens ("Check my answers", "Save request"). Errors say what happened and what to do next ("Your sign-in session has expired. Reload this page and sign in again.") and never apologise vaguely.
* **Honest.** Say "retained" when data is retained and "could not be confirmed" when a deletion fails. Never claim a save, deletion or approval that has not been confirmed by the server.
* **Safeguarding.** Copy and placeholders must not invite information that identifies children, and must not suggest that safeguarding concerns belong in reflection or mentoring notes.
* **No invented facts.** Do not fill content gaps with made-up field sizes, prices, dates or research claims.

---

## 9. Content loading contract (style work must not change it)

The visual layer depends on, and must preserve, the on-demand loading model:

1. **Start-up** fetches the manifest only (`/catalogue` and `/me`): headings, summaries and metadata, filtered to the person's pathway. No lesson or activity bodies.
2. **Opening a course or category** uses the manifest already in memory; no body requests.
3. **Opening a lesson or activity** fetches only that item (`/lessons/{id}` or `/resources/{id}`), then only the diagrams that item references (`/media/{id}`), each checked against the pathway on the server.
4. Opened items and diagrams are kept in memory for the current page only; reload, sign-out or a persona change starts afresh.
5. No prefetch, preload, polling or bundled all-in-one lesson HTML. Responses are `Cache-Control: private, no-store`.
6. The generated plugin index and the preview manifest list each source guide by title and page only. `build.py` drops `sources[*].file` from both (the authoring catalogue keeps the originals), so local file paths never reach the browser.

Rules for style and gallery work:

* Do not add CSS `background-image` URLs, `@import`s or `<link rel="preload">` that point at lesson or diagram content.
* Do not inline lesson bodies or diagrams into the page, stylesheet or this gallery.
* Keep skeleton or loading states CSS-only; they must not trigger extra requests.
* The HTML gallery uses invented sample text and a placeholder drawing. It contains no curriculum content.

---

## 10. Maintaining this guide

**When you change CSS**

1. Decide the layer first: layout, spacing, breakpoints and print go in `hub.css`; colour, type, radius, shadow and focus go in `brand.css`.
2. Use existing tokens. If you need a new colour, add a `--scys-*` token with a comment giving its use and its contrast against the background it sits on.
3. Re-check contrast for any text, border-of-control or focus change (4.5:1 body text, 3:1 large text, icons, control boundaries and focus indicators).
4. Check the change at 1280, 1000, 700 and 375 px, in print preview, with reduced motion on and in a forced-colours mode.
5. Tab through the changed screen to confirm focus is visible and in a sensible order.
6. Bump the plugin `VERSION` so browsers fetch the new file.
7. Re-run Codex's isolated suites (WordPress assertions, PHP lint, workflow and network/brand browser checks).
8. Update this guide's token or component tables and the hash table at the top.
9. Refresh `STYLE-GUIDE.html`: it embeds copies of `hub.css` and `brand.css` so it works offline, so replace both `<style>` blocks with the new files and update the hashes shown in its footer.
10. Log the change on the execution sheet.

**When you add a component**

* Reuse the existing classes before creating new ones; prefix any new class `hub-`.
* Add its default, hover, focus, active, disabled, error and empty states to the gallery.
* Keep native elements, 44 px targets and polite live regions for results.
* Keep yellow off it unless it is the single primary action.

**What needs a decision, not a style change**

New colours outside the brand book, web fonts, notifications, new policy wording and anything that changes what data is stored or loaded.
