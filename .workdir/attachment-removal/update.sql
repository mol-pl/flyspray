/**
	Update attachments schema.
*/

/*
CREATE TABLE IF NOT EXISTS public.flyspray_attachments
(
    attachment_id integer NOT NULL DEFAULT nextval('flyspray_attachments_attachment_id_seq'::regclass),
    task_id integer NOT NULL DEFAULT 0,
    comment_id integer NOT NULL DEFAULT 0,
    orig_name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    file_name character varying(30) COLLATE pg_catalog."default" NOT NULL,
    file_type character varying(50) COLLATE pg_catalog."default" NOT NULL,
    file_size integer NOT NULL DEFAULT 0,
    added_by integer NOT NULL DEFAULT 0,
    date_added integer NOT NULL DEFAULT 0,
    CONSTRAINT flyspray_attachments_pkey PRIMARY KEY (attachment_id)
)
*/

-- Note! Might be PG SQL specific.
-- Also note - table name may vary.
ALTER TABLE flyspray_attachments
   ADD COLUMN is_removed integer NOT NULL DEFAULT 0
   ,ADD COLUMN date_removed integer NOT NULL DEFAULT 0
;
COMMENT ON COLUMN flyspray_attachments.is_removed IS 'file missing or intentionaly removed';
COMMENT ON COLUMN flyspray_attachments.date_removed IS 'time of removal if file was intentionaly removed';

/*
ALTER TABLE flyspray_attachments DROP COLUMN removed_date;
ALTER TABLE flyspray_attachments DROP COLUMN isremoved;
*/