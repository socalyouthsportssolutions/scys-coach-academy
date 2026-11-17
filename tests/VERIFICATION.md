# Verification — modular version 0.2.0, 6 November 2026

## Integration checks

45 assertions passed in disposable WordPress Playground runs. Runtime-reported versions were WordPress 6.6.11 / PHP 8.1.34 and WordPress 6.9.11 / PHP 8.3.36. The final selective startup-query implementation was rerun on WordPress 6.9.11 / PHP 8.3.36. These are local isolated tests, not checks on the SCYS host.

Coverage includes the earlier account isolation, quiz server grading, completion deduplication, mentor assignment/review, reflection revision and persistence checks, plus:

- Lightweight startup with no lesson bodies, quiz questions, activity instruction bodies or embedded image data.
- Authenticated lesson detail and separate visual loading; withheld answer keys/explanations.
- Direct restricted-lesson denial for volunteers, and logged-out detail/media denial.
- Administrator authoring of an independent new lesson without a rebuild.
- An unassigned published lesson remaining inaccessible.
- Category membership/order, with the same lesson reused by coach and volunteer courses.
- Draft withdrawal removing the lesson from both the index and direct access, with no packaged fallback.
- Update migration preserving withdrawn lessons, edited text and existing progress, including records lacking the newer summary metadata.
- Literal backslashes surviving progress persistence.

The migration check simulates legacy metadata/content. It is not a backup/restore exercise or a production-database migration rehearsal.

## Browser checks

Modular preview: pathway → course → category → lesson; successful quiz and updated course progress; separate volunteer journey; activity search and independently loaded activity instructions.

Actual isolated WordPress: native sign-in, private catalogue loading, category/lesson navigation, separately requested original diagram successfully rendered, quiz completion saved through cookie-authenticated REST with a nonce, completion retained after reload. The administrator changed category ordering through the WordPress course editor; the saved order was confirmed in the learner course.

The displayed narrow viewport was approximately 615 pixels wide and the inspected course had no horizontal document overflow. Requested 320/1280 viewport overrides did not change the observed viewport, so this release does not claim a fresh 320-pixel phone layout test. Check phone sizing on the actual staging host. A full-page course screenshot is saved in releases/modular-course-preview.png.

## Build and content checks

JavaScript syntax, Python build/launcher syntax, package integrity/path layout and source/package equality are checked. The six original lesson bodies, sixteen activities and six original diagram bytes are compared with the prior committed catalogue to verify preservation.

The complete generated startup manifest is 20,665 uncompressed bytes before role filtering. The comparison with the previous approximately 1.2 MB catalogue is a payload-size comparison, not a measured host page-load benchmark.

## Limits

No live installation, actual-host cache/security/theme/email verification, real coach enrolment, independent security certification or complete resolution of the earlier security/privacy review is claimed. New scheduling, certificates and notification functions from the brief remain future work. The default WordPress theme uses a narrow content area; choose a full-width staging template.

Preview files contain simulated accounts and client-side quiz answer keys; do not deploy them publicly. Only the plugin provides the authenticated/server-graded behaviour. Quiz/relationship metadata has no dedicated revision-history UI. Existing completion is not revoked by text edits.

## Repeat

Run tests/wordpress-integration.php only in a disposable isolated WordPress instance. It creates test users and changes course content. Mount the plugin under /wordpress/wp-content/plugins/scys-coach-hub and this tests folder under /tests, then invoke the official WordPress Playground CLI php command with the chosen runtime versions and the test script. The script prints actual runtime versions and fails on an unsuccessful assertion.
