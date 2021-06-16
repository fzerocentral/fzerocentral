ALTER TABLE phpbb_users ADD COLUMN reset_token VARCHAR(128);
ALTER TABLE phpbb_users ADD COLUMN reset_token_expiration BIGINT UNSIGNED NOT NULL DEFAULT 0;
