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
