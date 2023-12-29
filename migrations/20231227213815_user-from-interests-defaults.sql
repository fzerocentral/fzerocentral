/* Formerly nullable and defaulted to NULL, but no existing rows are NULL; more sensible default is the empty string */
ALTER TABLE phpbb_users MODIFY COLUMN user_from varchar(100) NOT NULL DEFAULT '';
ALTER TABLE phpbb_users MODIFY COLUMN user_interests varchar(255) NOT NULL DEFAULT '';
