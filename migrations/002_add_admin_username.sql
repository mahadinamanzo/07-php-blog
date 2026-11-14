USE swd_blogs;

ALTER TABLE admins
  ADD COLUMN username VARCHAR(32) NULL AFTER name;

UPDATE admins
SET username = CONCAT('admin', id)
WHERE username IS NULL;

ALTER TABLE admins
  MODIFY username VARCHAR(32) NOT NULL,
  ADD UNIQUE KEY uq_admins_username (username);
