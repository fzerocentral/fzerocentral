# F-Zero Central website

This repository contains a rewritten version of the website without the phpbb
forum dependency.

Installation requires PHP and MariaDB.


## Config

You must create a `config.ini` file at the root of the repo and define certain variables in it. Example contents:

```
[app]
base_url = http://127.0.0.1:8000
hmac_key = "around50randomlettersnumbersandsymbols^+_(&@-!*)"
debug = true

[database]
host = localhost
username = root
password = mypassword
name = mydatabase
debug = true
```

There's also an `[email]` section which only the production environment needs to define. Development environments can set app -> debug to `true` to have email contents output to the file `log/debug_emails.log`, instead of sending actual emails.


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

### Migrations

The files under the `migrations` directory keep track of database schema changes made since this repo was created.

If someone else added migration files, you can check what you need to run to get your schema up to date by running `php migrations/pending.php`. Then to actually run the migrations: `php migrations/run.php`. (These scripts work by using a `schema_migrations` table to track which migrations have run in a particular database.)

If you're changing the schema, you should add one or more migration files to the `migrations` directory. Be sure to start the filename with the current date and time, mimicking the other filenames' format, to ensure that alphabetizing the filenames puts the migrations in the order they should be run.

### `phpbb_f0_records`

The main table, where every record is stored. We don't keep track of the
history, so if you update your times, only the last entry will remain.

Values are stored in milliseconds, normalized to NTSC. Conversions to and from
hundredths/pal are done on render / submission.

The `record_type` can be:
- `C`: the total time for the course (course/complete?)
- `L`: the best lap time
- `S`: max speed during the whole course

Each player submits their times to a course (on a cup on a ladder). Each entry
is composed of the best total course time, the best lap, and potentially the
max speed (`record_type`). These three can be achieved in independent runs, so
we store proofs and verification status for each of the three separately.

The player also has the ability to enter their splits (the time each lap took),
and a comment. These are not per record type, but per course. The database is a
bit weird in that it stores the comment in the `notes` field for the best lap
entry, and the splits in the `notes` field for the total course time entry. The
`notes` field in the max speed entry is, from what I understand, left empty.

There's also a `splits` column in each of these entries, but I don't think it
was being used. It looks something like this (some columns were omitted for
readability):

```
| course_id | user_id | record_type | value | verified | videourl | notes        |
| 1         | 1       | "C"         | 3000  | 1        | http://  | splits here  |
| 1         | 1       | "L"         | 1000  | 0        |          | comment here |
| 1         | 1       | "S"         | 1000  | 0        | http://  | unused       |
```

The splits/comment should probably be stored somewhere else. This structure has
the downside of players being unable to enter comments when the ladder does not
support the best lap record type, and it's generally a bit weird to fetch
information.

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
