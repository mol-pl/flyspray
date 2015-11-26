--
-- Table: flyspray_list_tag
--

-- DROP TABLE flyspray_list_tag;

CREATE TABLE flyspray_list_tag
(
  tag_id serial NOT NULL,
  tag_group character varying(20) NOT NULL,
  tag_name character varying(20) NOT NULL,
  
  project_id integer NOT NULL DEFAULT 0,
  list_position integer NOT NULL DEFAULT 0,
  show_in_list integer NOT NULL DEFAULT 0,
  
  CONSTRAINT flyspray_list_tag_pkey PRIMARY KEY (tag_id)
);

ALTER TABLE flyspray_list_tag
  OWNER TO rejszusr;

--
-- Index: flyspray_project_id_tag
--

-- DROP INDEX flyspray_project_id_tag;

CREATE INDEX flyspray_project_id_tag
  ON flyspray_list_tag
  USING btree
  (project_id);