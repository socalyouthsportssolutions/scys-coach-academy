=== SCYS Coach Education Hub ===
Contributors: socalyouthsports
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.3.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private coach and volunteer education with practical mentor review.

== Description ==
Internal pilot candidate: learning dashboard, scenario quizzes, original guide visuals, activity library, age pathways, saved progress and assigned mentor reviews. Uses native WordPress accounts. No paid LMS or AI API service is required. Code licensing does not grant redistribution rights to attributed guide images/content.

== Installation ==
1. Back up files/database and install on a private staging site first.
2. Upload this ZIP through Plugins > Add New, then activate on one site.
3. Add [scys_coach_hub] to a full-width WordPress page.
4. Exclude the page and /wp-json/scys-hub/v2/* from caching.
5. Create SCYS Coach, SCYS Volunteer and SCYS Mentor test users. Assign mentors in learner profiles.
6. Open Users > SCYS Coach Hub for setup and content-editing links.
7. Verify the signed-out, learner, volunteer and assigned mentor journeys before a real pilot.

== Important operating notes ==
Do not publish the development source catalogue or browser preview: they contain demo answer keys. The plugin's private catalogue is stored in guarded PHP and learner responses omit the answer keys. WordPress REST cookie authentication and nonces are required.

Administrators can create and edit lessons, reusable categories, courses and learning pathways. Enter order numbers to include records; leave blank to exclude. Existing activities are editable. Curated image references and activity tags still use source-module updates. Learning is loaded on demand through the private v2 API. Completion records are not reset by content edits. Quiz metadata is not included in standard post text revisions.

Do not record child-identifying information in reflections. There is no dedicated privacy export/erase interface or uninstall cleanup yet. Retention must be managed by the site administrator. Mentor roster is capped at 200 enrolled accounts and shows a limit notice. Network-wide activation is unsupported.

Deactivation retains learning records, content posts, roles and capabilities. Back up the database and files before updates. Test the actual host's cache, email delivery, theme and security plugin configuration on staging.

== Changelog ==
= 0.3.1 =
SoCal Youth Sports brand layer (assets/brand.css, loads after hub.css; dequeue scys-hub-brand to roll back). Header logo from the theme custom logo or site icon (filter scys_hub_logo_url). Branded sign-in / not-enrolled gate. Completed-lesson badges. Friendly date and time display in mentoring. WordPress personal-data exporter and eraser (mentoring records retained by default; filter scys_hub_erase_mentoring). Suggested privacy-policy text. Opt-in uninstall cleanup (option scys_hub_delete_data_on_uninstall = 1); data is kept by default.

= 0.2.0 =
Modular lessons, reusable categories and courses, role-based learning pathways, private on-demand lesson/media endpoints and update migration preserving existing content/progress.

== Coach Mentoring 0.3.0 ==
Seven optional lessons and account-linked operational request/visit records. Collection is disabled until programme content/privacy acceptance is recorded in Users > Mentoring pilot settings. Use synthetic data on isolated testing only until approved. See the separate handoff for data lifecycle, migration and rollback.
