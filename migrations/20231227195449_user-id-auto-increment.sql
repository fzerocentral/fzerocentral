/* Formerly not auto-incrementing */
ALTER TABLE phpbb_users MODIFY COLUMN user_id mediumint(8) NOT NULL AUTO_INCREMENT;
