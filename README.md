# SCYS Coach Education Hub — 0.3.1 mentoring review candidate

This continues the existing modular app and WordPress plugin. It has not been installed on the SCYS staging or live website.

Start with MENTORING-HANDOFF.md and tests/MENTORING-VERIFICATION.md. The review ZIP contains a readable start page, all seven lessons for content review, a runnable synthetic preview, the installable plugin ZIP, developer source, repeatable isolated tests and evidence.

My learning → Optional mentoring support → Coach Mentoring contains seven reusable lessons and 21 scenario questions. Existing coach/volunteer foundations, permanent lesson IDs and prior completion are preserved. Learning stays separate from delivered mentoring and on-field competence.

Claude’s existing 11 November review and v0.3.1 brand layer were recovered from execution item A149. A formal style guide, offline component gallery and loading-review addendum are included. The original content-store and operational schema remain unchanged. Native WordPress privacy export/erase hooks and a safe-by-default opt-in uninstall routine are covered by new isolated tests.

My mentoring / Assigned-coach mentoring / Mentoring coordination provide account-linked requests, accepted arrangements, multiple visits, draft notes, one next action, resource links and paginated due-date lists. Practical tasks and Reflection review retain the existing reflection workflow. Field cards print blank templates only.

Production collection is disabled by default. Users → Mentoring pilot settings records the required approvals and approved reporting URL before an administrator enables collection. Use synthetic test data only until content, technical/privacy and operating-policy acceptance are complete.

`source/catalogue.json` is the startup manifest; `source/content/` contains separate bodies and protected media. `python build.py` creates the modular preview, guarded private PHP modules and plugin ZIP. `includes/content-store.php` preserves content on migration. `includes/mentoring-store.php` owns operational records in the private database table. Shared interface files are in `assets/`.

WordPress uses authenticated `/scys-hub/v2/` routes, current assignment/ownership checks, REST nonces and server-side quiz grading. Production notes do not use localStorage or public media. The preview simulates accounts and stores invented examples in the browser; do not publish it publicly.

Test in an isolated WordPress installation with WordPress 6.6+ and PHP 8.1+. The sendable review pack also contains a Windows launcher using Node.js 24+ and official Playground libraries to run the synthetic PHP integration suite without installing WordPress on the host. See the review start page for requirements. The native Windows CLI mount path is not used by that launcher.

No paid LMS, AI API, email/SMS notifications, calendar synchronisation, bulk imports or real-world account permission changes are added. Deactivation retains data. Follow MENTORING-HANDOFF.md for migration, correction, retention, backup implications and rollback; WORDPRESS-STAGING.md remains the host acceptance reference.
