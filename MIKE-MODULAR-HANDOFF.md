> Current review candidate: **0.3.1, 17 November 2026**. Read [MENTORING-HANDOFF.md](MENTORING-HANDOFF.md) and [tests/MENTORING-VERIFICATION.md](tests/MENTORING-VERIFICATION.md) first. The earlier 0.2.0 guidance below is retained for architecture/history; use the 0.3.1 ZIP in this review pack. Mentoring collection is disabled by default.

# Mike — modular hub update, 6 November 2026

The previous bundle has been refactored into independent lessons and a reusable learning structure. This is version 0.2.0 for local/staging review; nothing has been installed on the live website.

## The structure

**Lesson → Category → Course → Learning pathway → Audience**

| Record | Contains | Reuse |
|---|---|---|
| Lesson | Its own written content, visual references and quiz | One lesson may be selected by several categories |
| Category | Ordered lesson references | One category may be selected by several courses |
| Course | Ordered category references | One course may be selected by several pathways |
| Learning pathway | Ordered course references and audience selection | The same course can serve several pathways |

Existing audiences are coaches/mentors and volunteers. New staff or private-coaching roles are future configuration work. Age-group activity plans remain separate under **Activity pathways** so they are not confused with learning pathways.

## Example already built

Both **Coach foundations** and **Volunteer foundations** use **The child and our environment**, containing **Why we coach** and **Enter their world**. They point to the same records. The coach course adds planning/adaptation and reflection categories; the volunteer course adds supporting the lead coach.

The seed organisation is a starting arrangement of the existing content, not a new claim about teaching effectiveness. The individual child stays at the centre; the detailed pedagogy area is still deferred.

## What now loads

At startup: shared CSS/JavaScript, a lightweight index of assigned learning/activity headings and the signed-in user's progress. The generated complete index is 20,665 bytes uncompressed before role filtering; the previous index carried about 1.2 MB including image data. This comparison is index payload, not measured host page speed.

On opening a lesson: one authenticated lesson detail request, followed by requests for that lesson's protected visuals. Activity instructions are requested only when an activity opens. Within the current page, opened records are cached in memory; changing the preview persona clears those caches. A fresh page load obtains current structure and content. Open pages do not receive live content-edit notifications.

The startup index contains no lesson HTML, quiz questions, activity instruction bodies or embedded image data. On the server, startup selects post headings and metadata rather than reading every lesson body. Quiz keys are never returned in learner detail responses; grading remains server-side. Each direct lesson request rechecks access through a published pathway/course/category.

## WordPress editing walkthrough

1. In **Academy Lessons**, create a lesson. Add its title, written content, excerpt, estimated minutes and three scenario questions with answers/explanations. Preview/review the material before publishing.
2. In **Academy Categories**, create or edit a category. Give included lessons order numbers; leave others blank. Reuse the same lesson in another category if needed.
3. In **Academy Courses**, choose and order categories in the same way.
4. In **Academy Pathways**, choose and order courses, then select coaches/mentors, volunteers or both.
5. Check the learner account: **My learning → course → category → lesson**. Dashboard Continue learning is a shortcut to the next unfinished lesson.

Drafting or trashing a record withdraws it. Removing a link does not delete the child record or erase progress. A lesson used by another published route remains accessible through that route. Lesson completion is recorded once by permanent ID across all uses; it is not duplicated per course. There is no automatic retraining/reset when text changes.

Existing curated activity text is editable; adding new activity metadata and new protected diagram mappings still uses source modules. Standard WordPress-uploaded images/video follow the site's own media permissions. The earlier security/privacy review remains relevant; this update is not a claim that all its findings have been resolved.

## Files to review

- `releases/scys-coach-hub-0.2.0.zip`: WordPress plugin, same directory and shortcode as before.
- `releases/scys-coach-hub-modular-preview-0.2.0.zip`: extract, then run **Start Preview.cmd** (Python required). Preview only, not a secure hosted LMS.
- `WORDPRESS-STAGING.md`: staging/update steps and remaining operating decisions.
- `tests/VERIFICATION.md`: exact scope of fresh checks and their limits.

## Still outside this update

Individual/group assignment scheduling, deadlines/reminders, certificates, broader notifications, new staff roles, session planner, session uploads, leaderboards and white-labelling remain later work from the brief. This update addresses content modularity, reuse, loading and WordPress structure authoring.

Please review on staging with an existing edited/completed lesson as well as a new lesson. Check the actual theme width, cache/CDN exclusions, private media behaviour, permissions, backup/restore and maintenance process before a real pilot.
