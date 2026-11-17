# Coach Mentoring — local 0.3.1 handoff

17 November 2026. Development package only. No staging installation, live publication, real-record import, external permission change, invitations or messages have been performed.

## What was combined

The 9 November programme supplies the purpose, seven-lesson sequence, examples and operating proposal. The supplied Codex prompt adds the implementation and verification requirements. The draft's internal emails and request sheet remain internal context; neither document establishes that a feature was previously delivered. This update uses the existing WordPress accounts and mentor assignment, without importing or synchronising that sheet.

The pilot size, visit allocation and reading times remain proposals. No booking, staffing commitment or empirical programme validation is implied. The separate detailed methodology/pedagogy area remains deferred.

## Where to find it

- **My learning → Optional mentoring support → Coach Mentoring — optional support → Coach Mentoring**: seven lessons, with three scenarios each. Coaches, mentors and volunteers use the same lesson IDs. Existing foundation courses and their memberships are unchanged; prior completion remains stored once per permanent lesson ID.
- **My mentoring** for coaches/volunteers; **Assigned-coach mentoring** for mentors; **Mentoring coordination** for administrators: request list, due-date list, shared request/agreement, and multiple linked visits. The due and upcoming lists are explicitly paginated, 50 requests per page; check subsequent pages before concluding no work is due.
- **Practical tasks / Reflection review** retain the existing lesson-reflection workflow. Operational mentoring is separate from `_scys_hub_state` and quiz completion.
- **My mentoring → Field cards**: printable blank pre-visit, observation/debrief, four-section matchday and follow-up templates. Printing the ordinary record screen suppresses private record forms and notes. Account exports use WordPress’s authorised privacy-request workflow; there is no export button in the mentoring interface.
- **WordPress Users → Mentoring pilot settings**: administrator records the approved reporting URL and acceptance/procedure reference before enabling collection. Collection is disabled by default. This setting records an administrator's decision; it does not establish that the approvals have actually taken place.

## Architecture and access

The existing build creates independent guarded PHP content modules, the modular preview and the plugin ZIP. Lesson bodies and quizzes remain absent from the startup manifest. Server grading withholds keys and explanations until submission. Original lesson bodies, foundation memberships, activities and diagrams are preserved.

`includes/mentoring-store.php` owns authenticated `/scys-hub/v2/support`, `/support/{id}` and `/support/save`. `assets/mentoring.js` supplies the shared interface. WordPress records use the new `{prefix}scys_mentoring` table, never public posts, public media, analytics or production localStorage. Each row is one account-linked request with uniquely identified visit records. A versioned aggregate lets one atomic database update preserve multiple visits and detect concurrent changes. Submission tokens prevent ordinary duplicate retries; old retries outside the bounded receipt window fail the version check rather than overwrite current data.

The coach sees their own records. A mentor also needs Hub/review capability and the **current** `_scys_mentor_id` assignment. An administrator needs existing Hub access and `manage_options`. Every read/write rechecks access, including direct record IDs. Changing assignment immediately denies the former mentor's subsequent requests; a replacement mentor must accept the arrangement. Removing Hub access denies the account. Information already legitimately viewed cannot be remotely recalled from a person's memory or an already open page: sign out and close shared devices when access changes. Responses, including support-route errors, are private/no-store.

Only the authenticated coach can record their agreement and only the current mentor can accept their assignment. An administrator may coordinate but cannot impersonate either agreement or attendance. Confirmation also needs a time and recorded arrangement. Delivery requires an explicit coach/current-mentor confirmation, a non-future actual visit and the complete short record. Drafts remain editable. Existing attendance authorship is retained; correcting an actual date/type returns that visit to draft for renewed confirmation. Delivery does not automatically close outstanding actions.

Forms disable repeated submissions, retain failed drafts on the current page, and show saved/error states. Stale versions receive 409. Reconcile by copying the retained draft, reopening the current record, comparing and resaving; do not blindly retry a stale edit. Unchanged network-failure retries reuse the same token. Unsaved drafts are memory-only in production and are lost if the browser closes; a leave-page warning helps but is not durable draft storage. Save a server draft before leaving. The preview uses a separate synthetic-only browser store and explicitly simulates personas; this is not proof of production authorisation.

The change record preserves adult account IDs, timestamps, changed top-level fields, action and visit reference. It intentionally does not duplicate every previous note body. This is a practical change record, not a forensic/tamper-proof audit log or a restore UI. Database backups remain necessary.

## Content review and sources

Lessons distinguish SoCal values/proposed applications, practitioner examples, the autonomy-support review and the controlled mentoring study. Haugan et al. used a Norwegian performance-development setting and self-report measures; coach and athlete perceptions differed. It is not proof for grassroots U6–U8. The FIFA example is a licence-assessment illustration, adapted here for developmental support. No third-party research PDFs are redistributed.

Verified public source pages on 10 November: [FIFA reflection example](https://www.fifatrainingcentre.com/en/environment/practical-example/coaching-development/the-coach-educator/student-coach-mentoring-promoting-reflection.php), [Raabe review abstract/record](https://cris.fau.de/publications/225323479/), [Haugan original methods/results](https://www.mdpi.com/2075-4663/9/8/116), [England Football matchday](https://learn.englandfootball.com/articles-and-resources/coaching/resources/2024/How-coaches-can-manage-matchday), [matchday experience](https://learn.englandfootball.com/articles-and-resources/coaching/resources/2022/How-to-create-a-great-matchday-experience), [AYSO Safe Haven](https://www.aysovolunteers.org/policy-safe-haven-cvpa/), [U.S. Soccer reporting](https://www.ussoccer.com/safeguarding/report-a-concern). The Raabe abstract is used for limited background, not a new quantitative effectiveness claim.

Hide & Seek and 4v2 Rondo exist in the source catalogue. The latter is tagged U12/U14. The attached proposal describes the 4v1 → 4v2 → wider-game progression; a distinct 4v1/3v3 source module was not verified, so lessons label the progression as an illustrative application and do not invent a resource link. The three worked examples are illustrative, not observations of named coaches. The old “anonymous reflection” wording in earlier documentation should be understood as no child-identifying details; records are linked to adult accounts.

## Launch blockers and data lifecycle

Before collecting real pilot notes, record coaching-lead approval of wording/quizzes/methods and technical/privacy acceptance on the actual host. Confirm the approved restricted reporting procedure, Region/CVPA supervision and team-area rules, coordinator/mentor capacity, retention/deletion rules and backup handling. No retention period is invented. Mentoring does not replace qualifications, screening, safeguarding training or formal performance procedures.

Do not enter children's names, identifying details, medical/family information, safeguarding allegations or identifiable child footage. Use the proper restricted reporting route immediately; this feature is not an abuse-reporting system. There is no automated sensitive-data classifier: the warning, adult review and approved correction process remain essential.

For corrections, the coach/current mentor/admin can edit the shared request or visit; preserve the saved version and change metadata. For accidental sensitive information, remove it promptly from the shared record and follow the approved privacy incident procedure, including backups/logs. Do not paste the information into the history or another mentoring field.

Account deletion does not automatically erase records, nor does plugin deactivation. Until policy is agreed, do not delete data. After an authorised retention/deletion decision, an administrator/technical owner must use a documented restricted database process for the relevant request rows, account metadata and backups; this release uses WordPress’s authorised personal-data request workflow for account-scoped exports and learning-state erasure. Operational mentoring records are retained by default. There is no learner-facing/public erase or export API. Store approval references and settings under the same privacy policy. Backup restores may restore corrected/deleted material: record and replay authorised corrections/deletions under that policy, and control access to backups. Do not promise immediate deletion from immutable backups.

## Migration and rollback

Back up database and plugin together. On private staging, replace the same plugin directory with 0.3.1. Content migration runs on update, seeds only missing records and preserves existing edited/draft/withdrawn content, quizzes, reflections, completion and assignments. The new table is additive and installs on activation or normal initialisation. No real mentoring rows are seeded. Existing foundation pathway records are not edited; the new shared optional pathway provides access independently.

Use synthetic test accounts first, confirm collection is disabled, then complete the host checks in `WORDPRESS-STAGING.md` and the checks in `tests/MENTORING-VERIFICATION.md`. Check actual PHP/WordPress versions, HTTPS, theme width, cookie/nonce expiry, cache/CDN exclusions, database writes, reassignment, user access removal and backup/restore. Do not infer those results from a ZIP or isolated code tests.

For rollback, disable collection first and preserve a backup of the new records. Deactivate/revert plugin files only after testing the compatibility of the retained data. Full rollback needs the matching database/files backup, and may discard later progress or mentoring changes unless reconciled. Deactivation deliberately retains the table, progress, content and roles; it is not erasure. No automatic downgrade is supplied. Uninstall retains data by default; the deliberate scys_hub_delete_data_on_uninstall option can remove data only after a separate authorised retention/backup decision. This update does not enable that option on a real site.

No notifications, calendar integration, certificates, external synchronisation, bulk import or broad staff-report export is added. Source files and research originals are preserved.

## Final review results

See tests/MENTORING-VERIFICATION.md: 111 assertions pass on each of the two isolated WordPress/PHP combinations, 18 workflow and 15 loading/brand browser checks pass, and all 41 packaged PHP files parse on PHP 8.1 and 8.3. Explicit open/completed follow-up state keeps a “not yet” response visible when another check is needed. Only an explicitly completed action with a recorded result/closure reason leaves the due list.
