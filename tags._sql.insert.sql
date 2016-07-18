--
-- Test data
--

INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 11, 'projekt', 'MOL');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 12, 'projekt', 'Libra');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 13, 'projekt', 'Patron');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 1, 'edycja', 'Publiczna');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 2, 'edycja', 'Akademicka');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 3, 'edycja', 'Zwykła');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 4, 'edycja', 'Pedagogiczna');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 5, 'edycja', 'Prawnicza');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 6, 'edycja', 'Start');
INSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, 7, 'edycja', 'Starter');

-- must change sequance after manually setting tag_id
SELECT setval('flyspray_list_tag_tag_id_seq', 13);