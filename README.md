# F-Zero Central website

This repository contains a rewritten version of the website without the phpbb
forum dependency.


## Database

The website requires the following tables:

- `phpbb_f0_champs_10`
- `phpbb_f0_records`
- `phpbb_f0_totals`
- `phpbb_users`
  - `reset_token_expiration`
  - `reset_token`
  - `user_email`
  - `user_id`
  - `user_interests`
  - `user_password`
  - `username`

At some point, these will be renamed and simplified to remove the phpbb ties.

### `phpbb_f0_records`

The main table, where every record is stored. We don't keep track of the
history, so if you update your times, only the last entry will remain.

Values are stored in milliseconds, normalized to NTSC. Conversions to and from
hundredths/pal are done on render / submission.

The `record_type` can be:
- `C`: the total time for the course (course/complete?)
- `L`: the best lap time
- `S`: max speed during the whole course

### `phpbb_f0_totals`

Stores the total time it took for each player to complete each cup. Also, the total
time to complete the whole ladder.

It's a roll-up of `phpbb_f0_records` over the `course_id` column. It also
contains a roll-up of itself over the `cup_id` column, with some defaults
(Ferris Beuller) for when players don't have a record for certain courses.

### `phpbb_f0_champs_10`

Stores the AF and SRPR scores for each user/ladder.


## Ladder info

Each ladder is described in `data/ladders/*.xml`. This description contains the list
of cups, courses, ships, and other settings.

We're currently porting these to YAML to reduce some weirdness when dealing directly
with DOM nodes in twig templates.

The ladder/cup/course identifiers in these files shouldn't be changed, as
they're mapped to entries in the database.
