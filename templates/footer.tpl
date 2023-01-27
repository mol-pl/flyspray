    </div>
    <p id="footer">
       <!-- Please don't remove this line - it helps promote Flyspray -->
       <a href="http://flyspray.org/" class="offsite">{L('poweredby')}<?php if ($user->perms('is_admin')): ?> {$fs->version}  {$fs->getSvnRev()}<?php endif; ?></a>
	   &bull; <?=(date("Y-m-d H:i:s"))?> (<?=(date_default_timezone_get())?>)
    </p>
  </div>
  </body>
</html>
