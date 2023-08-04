; <?php die( 'Do not access this page directly.' ); ?>

      ; This is the Flysplay configuration file. It contains the basic settings
      ; needed for Flyspray to operate. All other preferences are stored in the
      ; database itself and are managed directly within the Flyspray admin interface.
      ; You should consider putting this file somewhere that isn't accessible using
      ; a web browser, and editing header.php to point to wherever you put this file.

[general]
fs_prefix_code="RZ"								; Flyspray task prefix code (defulat = FS)
cookiesalt="..."	; Randomisation value for cookie encoding
output_buffering = "on"							; Available options: "on" or "gzip"
address_rewriting = "1"							; Boolean. 0 = off, 1 = on.
reminder_daemon = "1"							; Boolean. 0 = off, 1 = on.
passwdcrypt = "..."								; Available options: "crypt", "md5", "sha1" (Deprecated, do not change the default)
doku_url = "http://en.wikipedia.org/wiki/"      ; URL to your external wiki for [[dokulinks]] in FS
syntax_plugin = "dokuwiki"						; Plugin name for Flyspray's syntax (use any non-existing plugin name for deafult syntax)
update_check = "0"								; Boolean. 0 = off, 1 = on.

; for graphs to work either dot_public or dot_path must be set
dot_public = "" 								; URL to a public dot server
;dot_path = "/usr/bin/dot"						; Path to the dot executable (see www.graphviz.org)
dot_path = ""						; Path to the dot executable (see www.graphviz.org)
dot_format = "svg" ; "png" or "svg"

max_summary_on_list = 175						; maximum summary length on task list
check_for_duplicated_requests = "1"				; Boolean. 0 = off, 1 = on.
test_sections = "cliph_test"					; String, CSV of sections to be replaced. Most have "_test" sufix
test_server_name = "..."	    				; String, case-insensitive name of the server that (when matched) will activate test configuration sections

new_task_reportedver_tenses = "2,3"

anon_lock = "1"									; Boolean. 0 = off, 1 = on. If on anonymous user will only be able to see headers.
domain_auth_only = "0"							; Boolean. 0 = off, 1 = on. If on only LDAP auth will be allowed.

enable_intro_collapser = "1"					; Boolean. 0 = off, 1 = on. If on then intro message tables will be collapsed. Default=off

tag_project_group = "projekt"					; Regexp filter for project groups of tags

; Config for sched_attach_delete.php
[attach_del]
enabled = "1"									; Boolean. 0 = off, 1 = on. If on anonymous user will only be able to see headers.
batch_size = 1000								; Max rows checked in one batch (recomended: 1000).
;max_batches = 10								; Max number of batches in one schedule (this can be as large as you want).
												; Note! It is recomended that: batch_size*max_batches > total_attachements (total files still on disk).

; Cliph upper tab bar
[cliph]
tab_number = 4
include_path = "../cliph/clip.php"
css_url = "/cliph/clip.css"

[cliph_test]
tab_number = 4
include_path = "../mol/www/cliph/clip.php"
css_url = "//mol.local/cliph/clip.css"

[database]
dbtype="pgsql"
dbhost="localhost"
dbname="..."
dbuser="..."
dbpass="..."
dbprefix="flyspray_"

[attachments]
zip = "application/zip" ; MIME-type for ZIP files
; mapping new MS Office types to older (and shorter) equivalents
; https://stackoverflow.com/questions/4212861/what-is-a-correct-mime-type-for-docx-pptx-etc
docx = "application/msword"
xlsx = "application/vnd.ms-excel"
pptx = "application/vnd.ms-powerpoint"

[formcopy]
reMsgSourceBaseUrls = "/:\/\/(192\.160\.0\.[0-9]+|[a-z.]+\.mol\.(com\.)?pl|localhost)(:[0-9]+)?$/"	; do not double slash! (php does in parse_ini_file)
dest_flyspray_base_url = "https://internal/rejsz/"
