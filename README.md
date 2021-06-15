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

`phpbb_f0_records` is the main table, where every record is stored. We don't
keep track of the history, so if you update your times, only the last entry
will remain.

`phpbb_f0_totals` is a roll-up of `phpbb_f0_records` over the `course_id`
column. It also contains a roll-up of itself over the `cup_id` column.

`phpbb_f0_champs_10` stores the AF and SRPR scores for each user/ladder.
