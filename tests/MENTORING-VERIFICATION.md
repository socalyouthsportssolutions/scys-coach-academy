# Mentoring verification — 17 November 2026

Review candidate 0.3.1. These results concern disposable local tests, not the SCYS staging or live host.

- 111 WordPress/API assertions passed on WordPress 6.6.11 / PHP 8.1.34.
- 111 assertions passed on WordPress 6.9.11 / PHP 8.3.36.
- All 41 packaged PHP files parsed without syntax errors on each PHP runtime.
- 18 mentoring browser checks and 15 additional loading/brand checks passed in headless Microsoft Edge. Both shared interface scripts pass Node syntax checks. The loading trace proves startup/course/category screens fetch no lesson, activity or diagram bodies; each body loads only when opened, and reopening uses the same-page cache.
- Original six lesson bodies, original activities, foundation memberships and six diagram files passed preservation checks against the prior Git baseline. The manifest remains free of lesson bodies, quiz questions, answer keys and private notes. The generated index is 21,561 uncompressed bytes before role filtering.
- Build/ZIP integrity, package/source equality and four non-empty printable field-card pages passed.

The API tests cover default collection-disabled behaviour; nonce enforcement; account ownership; accepted arrangement; delivery confirmation; multiple linked visits; drafts; duplicate submission tokens; stale versions; tampered ownership; unrelated mentors/coaches; reassignment; administrators; unprivileged and signed-out users; no-store; overdue follow-ups; not-yet results staying open; action closure; invalid dates; shared lesson keys; deduplicated completion; and idempotent migration preserving edited/withdrawn records and progress. They reuse the earlier reflection/mentor-review tests.

The 19 added WordPress assertions cover native privacy-tool registration, account-scoped export and pagination, receipt exclusion, default record retention, explicit synthetic erasure, honest failed-deletion reporting, branded signed-out/unenrolled gates, logo selection and default/opt-in uninstall. All erasure and cleanup tests use disposable synthetic WordPress instances only.

The browser checks cover lazy content loading; seven ordered lessons and quiz completion; request saving; synthetic account separation; mentor acceptance; delivery and a second visit; refresh; save failure/retry; stale edits; four blank print cards; 320px layout; labels; keyboard focus; and no script errors.

A legacy progress-save defect was fixed: WordPress compares the previous metadata value without unslashing it, so passing a slashed previous value could reject subsequent saves after a literal backslash was stored. Later quiz saves now pass with that state retained. The previous integration fixture now edits an original foundation lesson so it does not overwrite a new mentoring lesson before that lesson is tested.

The native Windows Playground CLI failed during its filesystem bootstrap with ErrnoError 28. The supplied test harness uses the official Playground libraries with a fully disposable in-memory WordPress/SQLite filesystem instead; it creates no real accounts or records and bypasses native mount issues. This is still an isolated WordPress test, not a real-host MySQL/cache/nonce-login/browser acceptance test.

Not verified: SCYS host/theme/plugin/CDN compatibility, MySQL integration on the target host, fresh authenticated WordPress browser journeys, real-session logout/expiry, backup/restore, production access removal, full accessibility conformance or independent security certification. Mike should test these on a private disposable/staging site before considering a pilot.

No real data was imported. Nothing was installed on or published to the live site. Collection remains disabled by default. Content approval, technical/privacy acceptance, approved reporting procedure, retention/deletion/backup rules and programme capacity remain real-data launch blockers.

Raw logs, PHP parsing results and preview browser results are supplied in evidence/. Use only synthetic data while reviewing.
