-- Note! Might be PG SQL specific.
-- Also note - table name may vary.
ALTER TABLE flyspray_comments
   ADD COLUMN done integer NOT NULL DEFAULT 0;
COMMENT ON COLUMN flyspray_comments.done
  IS 'status of the comment (kind of like closed for tasks)';
