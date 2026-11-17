# To build

Each of these has a dashed "Not built yet" box on its page. Delete the box when the work is done.

## Sign-up and login

- [ ] Login links, which also cover forgotten passwords (`/login/link`)

## Team

- [ ] Create a team (`/team/new`)
- [ ] Join from an invite link (`/join/{code}`)
- [ ] List people looking for a team and invite them (`/team`)
- [ ] Invites: list, cancel, accept, decline (`/team/invites`)
- [ ] Leader actions: make leader, remove, rename, reset the link, disband
- [ ] Leave a team
- [ ] Pick or switch track (`/team/tracks`)
- [ ] The "let team leaders find me" switch, on Team and in Settings

Joining, inviting and switching track all have to respect the Beginner track's rule and the team size limit.

## Hacker pages

- [ ] Save the submission write-up (`/submission`)
- [ ] Settings: profile, password, resume upload (`/settings`)
- [ ] Map: the floor plan, with team tables and judging marked (`/map`)

## Organizer pages

- [ ] Home: the two counts that show a dash
- [ ] People: filters and CSV export
- [ ] One person: change status, send a login link, delete
- [ ] One team: the same actions a leader has
- [ ] Check-in button
- [ ] Announcements: post and delete. Posting emails everyone registered
- [ ] Resume download

Every organizer change writes an `audit_log` row.

## Judging

- [ ] Waits on the judging tables. The four judge pages and the organizer's Judging page are layouts only
