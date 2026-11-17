> Current review candidate: **0.3.1, 17 November 2026**. Read [MENTORING-HANDOFF.md](MENTORING-HANDOFF.md) and [tests/MENTORING-VERIFICATION.md](tests/MENTORING-VERIFICATION.md) first. The earlier 0.2.0 guidance below is retained for architecture/history; use the 0.3.1 ZIP in this review pack. Mentoring collection is disabled by default.

# Put the Coach Academy into WordPress

## Recommended route

Use this as a private, mobile-friendly area of the existing WordPress website. The plugin supplies the app interface; WordPress supplies individual accounts and password resets. Coaches use a normal URL such as `/coach-academy/` on a phone, tablet or computer. They can bookmark it or add the page to their phone's home screen. Version 0.2.0 is not an App Store app or an offline PWA.

No paid LMS is required for this version. Hosting, backups, email delivery and ongoing maintenance still need an owner. No AI API tokens are used when coaches use the hub.

## Install on staging

1. Make a current database/files backup and use a private staging copy of the site. Confirm WordPress 6.6+ and PHP 8.1+. This version supports a single site; network-wide activation is blocked.
2. In Plugins → Add New → Upload Plugin, select `releases/scys-coach-hub-0.2.0.zip`, install and activate it.
3. Create a page titled **Coach Academy** with a Shortcode block containing `[scys_coach_hub]`. Prefer a full-width page template. The page itself can be published on staging: signed-out visitors receive a login prompt and users without hub access are denied the learning interface.
4. Exclude that page and `/wp-json/scys-hub/v2/*` from page/CDN caching. Do not cache logged-in responses. The plugin sets private/no-store headers and noindex for the shortcode page; the host/CDN must respect them. Never upload the development folder, offline preview or source catalogue to a public web directory.
5. Create separate test accounts under Users with roles **SCYS Coach**, **SCYS Volunteer** and **SCYS Mentor**. Assign a mentor using the coach/volunteer's user-profile field. Use an existing WordPress admin for setup. Do not give learners Administrator access. Changing an existing staff member's primary WordPress role may remove permissions needed for their other duties; test with dedicated academy accounts first.
6. Visit Users → SCYS Coach Hub for setup help. Edit lessons, activities, categories, courses and pathways through the links there. Follow MIKE-MODULAR-HANDOFF.md for a reusable-category example. Review the scenario answer keys before inviting the pilot group.

## Pilot acceptance checks

- Signed-out visitors see sign-in/reset options; an ordinary Subscriber cannot retrieve the learning catalogue.
- A coach completes a quiz, receives specific feedback, refreshes and retains progress on another signed-in device. A failed attempt remains retryable.
- A volunteer receives the shorter three-lesson journey; a coach has five lessons. Accounts do not share progress.
- A learner submits an account-linked practical reflection without child-identifying details. The assigned mentor can review it, another mentor cannot, and the learner sees the saved feedback.
- Editing the reflection after approval sends it back for fresh mentor review. A review of an outdated reflection is rejected.
- An administrator edits lesson text, a question and activity instructions and verifies the actual learner page after saving. Content revisions can restore text; quiz metadata is not covered by the WordPress text-revision history, so keep a database backup before quiz edits.
- The library search, age filters, original diagrams, print view and navigation work on desktop and a narrow phone.
- Login/reset emails reach the test account through the site's existing mail setup. Test expiry/logout and the displayed save-error/retry message. A host blocking WordPress REST must allow the authenticated hub routes.
- Confirm the page/theme width, caching exclusions, security plugins, HTTPS and backup restore on this actual site. Isolated automated checks do not establish compatibility with every host/plugin combination.

## How staff use it

**Administrator:** creates accounts, chooses their academy role, assigns mentors, maintains existing content and handles access removal. There is no public self-enrolment or payment flow.

**Coach:** signs in, continues a lesson, uses age-group resources and submits practical observations. Each lesson requires three correct answers; retries are allowed. Online learning and mentor approval are displayed separately.

**Volunteer:** follows the shorter foundation journey and phased practical support activities under the lead coach.

**Mentor:** opens Mentor review, reads assigned learners' account-linked reflections without child-identifying details, gives feedback and marks a task approved or requests changes. Approval is an internal development record, not an external qualification or safeguarding clearance.

## Content and data decisions before a real pilot

- Coaching lead reviews the new lesson wording, quizzes, proposed age progressions/adaptations and diagram legibility. The source guides remain the reference; the detailed pedagogy section is deferred.
- Confirm permission to use the supplied guide extracts with the intended audience. Source references are preserved; the research library and full third-party PDFs are not packaged.
- Name the academy administrator and mentors, choose the pilot group, and decide how often practical work is reviewed.
- Agree retention/access-removal procedures. Reflections must not include children's names, health information or other identifying details. This version has no dedicated privacy export/erase screen; administrators need a documented backup-aware process for the `_scys_hub_state` and `_scys_mentor_id` user metadata.

## Version 0.2.0 boundaries

No certificates, automatic email reminders, bulk account importer, staff-wide report export, attendance/safeguarding integration, video hosting, public discussion board or native mobile app. The mentor roster currently loads at most 200 enrolled accounts and displays a limit notice; expand pagination before a larger rollout. Do not use that capped view as a complete organisation report.

Lessons, categories, courses and learning pathways can now be created and arranged in WordPress. Use the membership order fields to include or exclude child records; the same child may belong to several parents. Pathways select the existing coach/mentor or volunteer audience. Existing activity age tags and curated image references still require a source-module update. Material course changes do not automatically revoke previous completions or create a new course version. Plan content versioning before using completions for mandatory recurring training.

## Rollback and updates

Deactivate the plugin to remove its interface. User progress, content posts, roles and capabilities are deliberately retained; deactivation is not data erasure. Restore the backed-up database and plugin together if an update needs full rollback. There is no uninstall cleanup routine in this version. Do not delete user metadata or roles without a specific data-removal decision and backup.

Once staging passes and the coaching lead approves the material, the next step is a small live pilot with a coach, volunteer and mentor. Live installation/publication has not been performed as part of this build.


## Updating from 0.1.0

Back up both the database and plugin. Install the 0.2.0 ZIP as a replacement on staging; keep the same plugin directory and shortcode. Clear the page/CDN asset cache so the updated script uses the v2 API. The migration adds category/course/pathway records and lightweight lesson metadata without replacing existing edited text, quizzes, progress or mentor assignments. It runs on normal initialisation after an update; deactivation/reactivation is not required.

Test a previously completed lesson and edited lesson, a volunteer account, and a withdrawn lesson after the update. Completion is shared by permanent lesson ID across all categories/courses. Removing a lesson from a category does not erase progress or delete the lesson. Drafting/trashing a lesson withdraws it across all courses. Drafting a category/course/pathway removes its route through the structure; another published route can still grant access. These are structural assignment controls, not individual/group assignment scheduling.

Existing completion is not automatically revoked when a lesson changes. Define a content-version policy before using this for mandatory recurring training. Restore database and files together for a full rollback to the prior version; do not run an older plugin against changed content without validation.
