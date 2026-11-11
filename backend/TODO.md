# What's left to build

Every page exists and renders. The ones below have a dashed **Not built yet** box where behavior is missing; the
box names the controller method. Pick one, build it, remove the box. Reads are real everywhere; it's the writes
that are stubbed. The design doc covers the rules each one has to follow.

## Hackers

| Page | Route | Missing | Where |
| --- | --- | --- | --- |
| Start a team | `/team/new` | Create the team and its leader row in one transaction | `TeamController::new` |
| Team | `/team` | The looking-for-a-team list (paged); invite someone (with the Beginner warning); make leader; remove; reset the invite link; rename; disband; leave | `TeamController::team` |
| Invites | `/team/invites` | List sent (leader) or received (invitee) invites; cancel; join; decline | `TeamController::invites` |
| Tracks | `/team/tracks` | Switch tracks under a team lock, with the Beginner check | `TeamController::tracks` |
| Join link | `/join/{code}` | The join: size check, Beginner gate, decline other invites | `TeamController::join` |
| Submission | `/submission` | Save the write-up (upsert, `updated_by`, `updated_at`) | `HackerController::submission` |
| Settings | `/settings` | Profile save; password change; discoverability; resume upload (needs S3) | `HackerController::settings` |
| Map | `/map` | Jason's traced floor plan; pins from team tables and judge panels | `templates/hacker/map.html.twig` |

Still missing entirely: the two-step email-first registration (design doc, Verification; Victor), login links
(`/login/link`), the discoverability modal on first visit to Team.

## Organizers (`/organizer`, `ROLE_ADMIN`)

| Page | Missing | Where |
| --- | --- | --- |
| Home | The "Beginner teams with a non-beginner" and "verified, no resume" counts | `OrganizerController::home` |
| People | Status, school and no-team filters; CSV export (logged) | `OrganizerController::people` |
| One person | Status change (emails them, audit log); login link; deletion | `OrganizerController::person` |
| One team | Rename, track (no Beginner override), make leader, remove, reset link, disband; all logged | `OrganizerController::team` |
| Check-in | The Check in button (`checked_in_at`, audit log) | `OrganizerController::checkIn` |
| Announcements | Post (save, then email everyone registered); delete | `OrganizerController::announcements` |
| Judging | Panels, assignments, scores, export: needs the judging tables | `OrganizerController::judging` |

Every organizer write also writes an `audit_log` row (`actor`, `action`, `subject_type`, `subject_id`).

## Judges (`/judge`, `ROLE_JUDGE`)

All four pages are layouts only until the judging tables come back (see the design doc's Judging section).

## Shared

- `App\Service\Me`: the logged-in person's application, team and whether they lead it. Use it instead of re-querying.
- `App\Twig\PaneExtension`: `pane_next()` and `pane_announcements()` feed the right-hand column on every page.
- `templates/_shell/todo.html.twig`: the "Not built yet" box. Remove the include when the real thing lands.
- Sidebar highlighting: set `{% set section = '...' %}` at the top of a page template; keys are in `_shell/nav.html.twig`.
- Pages that render logged out set `{% set out = true %}` and use `<div class="grid narrow">`.
