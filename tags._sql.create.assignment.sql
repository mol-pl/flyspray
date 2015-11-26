-- Table: flyspray_tag_assignment

-- DROP TABLE flyspray_tag_assignment;

CREATE TABLE flyspray_tag_assignment
(
  task_id integer NOT NULL DEFAULT 0,
  tag_id integer NOT NULL DEFAULT 0
);

ALTER TABLE flyspray_tag_assignment
  OWNER TO rejszusr;

-- Index: flyspray_task_id_assigned

-- DROP INDEX flyspray_task_id_assigned;

CREATE INDEX flyspray_task_id_assigned
  ON flyspray_tag_assignment
  USING btree
  (task_id, tag_id);

-- Index: flyspray_task_tag

-- DROP INDEX flyspray_task_tag;

CREATE UNIQUE INDEX flyspray_task_tag
  ON flyspray_tag_assignment
  USING btree
  (task_id, tag_id);

