    </div>
    <p id="footer">
       <!-- Please don't remove this line - it helps promote Flyspray -->
       <a href="http://flyspray.org/" class="offsite">{L('poweredby')}<?php if ($user->perms('is_admin')): ?> {$fs->version}  {$fs->getSvnRev()}<?php endif; ?></a>
	   &bull; <?=(date("Y-m-d H:i:s"))?> (<?=(date_default_timezone_get())?>)
    </p>
    <div id="extra-data" style="display: none;">
		<span class="max-file-size" data-unit="MiB">{$fs->max_file_size}</span>
		<span class="lang--upload-limit">{L('max')}</span>
		<span class="lang--upload-too-big">{L('upload.total_size_too_big')}</span>
    </div>
  </div>
  </body>
</html>
