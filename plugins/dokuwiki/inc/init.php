<?php
/**
 * Initialize some defaults needed for DokuWiki
 */

  // start timing Dokuwiki execution
  function delta_time($start=0) {
    list($usec, $sec)  = array_pad(explode(" ", microtime()), 2, "");
    return ((float)$usec+(float)$sec)-((float)$start);
  }
  define('DOKU_START_TIME', delta_time());

  // define the include path
  if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../').'/');

  // define config path (packagers may want to change this to /etc/dokuwiki/)
  if(!defined('DOKU_CONF')) define('DOKU_CONF',DOKU_INC.'conf/');

  // check for error reporting override or set error reporting to sane values
  if (!defined('DOKU_E_LEVEL') && @file_exists(DOKU_CONF.'report_e_all')) {
    define('DOKU_E_LEVEL', E_ALL);
  }
  if (!defined('DOKU_E_LEVEL')) { error_reporting(E_ALL ^ E_NOTICE); }
  else { error_reporting(DOKU_E_LEVEL); }

  // init memory caches
  global $cache_revinfo;  $cache_revinfo = array();
  global $cache_wikifn;   $cache_wikifn = array();
  global $cache_cleanid;  $cache_cleanid = array();
  global $cache_authname; $cache_authname = array();

  //prepare config array()
  global $dokuConf;
  if (!defined('DOKU_UNITTEST')) {
    $dokuConf = array();

    // load the config file(s)
    require_once(DOKU_CONF.'dokuwiki.php');
    if(@file_exists(DOKU_CONF.'local.php')){
      require_once(DOKU_CONF.'local.php');
    }
  }

  //prepare language array
  global $lang;
  $lang = array();

  // define baseURL
  if(!defined('DOKU_BASE')) define('DOKU_BASE',getBaseURL());
  if(!defined('DOKU_URL'))  define('DOKU_URL',getBaseURL(true));

  // define cookie and session id
  if (!defined('DOKU_COOKIE')) define('DOKU_COOKIE', 'DW'.md5(DOKU_URL));

  // define Plugin dir
  if(!defined('DOKU_PLUGIN'))  define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

  // define main script
  if(!defined('DOKU_SCRIPT')) define('DOKU_SCRIPT','doku.php');

  // define Template baseURL
  if(!defined('DOKU_TPL')) define('DOKU_TPL',
                                  DOKU_BASE.'lib/tpl/'.$dokuConf['template'].'/');

  // define real Template directory
  if(!defined('DOKU_TPLINC')) define('DOKU_TPLINC',
                                  DOKU_INC.'lib/tpl/'.$dokuConf['template'].'/');

  // make session rewrites XHTML compliant
  @ini_set('arg_separator.output', '&amp;');

  // enable gzip compression
  if ($dokuConf['gzip_output'] &&
      !defined('DOKU_DISABLE_GZIP_OUTPUT') &&
      function_exists('ob_gzhandler') &&
      preg_match('/gzip|deflate/', $_SERVER['HTTP_ACCEPT_ENCODING'])) {
    ob_start('ob_gzhandler');
  }

  // init session
  if (!headers_sent() && !defined('NOSESSION')){
    session_name("DokuWiki");
    session_start();
  }

  // disable gzip if not available
  if($dokuConf['compression'] == 'bz' && !function_exists('bzopen')){
    $dokuConf['compression'] = 'gz';
  }
  if($dokuConf['compression'] == 'gz' && !function_exists('gzopen')){
    $dokuConf['compression'] = 0;
  }

  // precalculate file creation modes
  init_creationmodes();

  // automatic upgrade to script versions of certain files
  scriptify(DOKU_CONF.'users.auth');
  scriptify(DOKU_CONF.'acl.auth');


/**
 * Checks paths from config file
 */
function init_paths(){
  global $dokuConf;

  $paths = array('datadir'   => 'pages',
                 'olddir'    => 'attic',
                 'mediadir'  => 'media',
                 'metadir'   => 'meta',
                 'cachedir'  => 'cache',
                 'lockdir'   => 'locks');

  foreach($paths as $c => $p){
    if(empty($dokuConf[$c]))  $dokuConf[$c] = $dokuConf['savedir'].'/'.$p;
    $dokuConf[$c]             = init_path($dokuConf[$c]);
    if(empty($dokuConf[$c]))  nice_die("The $c does not exist, isn't accessable or writable.
                               You should check your config and permission settings.
                               Or maybe you want to <a href=\"install.php\">run the
                               installer</a>?");
  }

  // path to old changelog only needed for upgrading
  $dokuConf['changelog_old'] = init_path((isset($dokuConf['changelog']))?($dokuConf['changelog']):($dokuConf['savedir'].'/changes.log'));
  if ($dokuConf['changelog_old']=='') { unset($dokuConf['changelog_old']); }
  // hardcoded changelog because it is now a cache that lives in meta
  $dokuConf['changelog'] = $dokuConf['metadir'].'/_dokuwiki.changes';
}

/**
 * Checks the existance of certain files and creates them if missing.
 */
function init_files(){
  global $dokuConf;

  $files = array( $dokuConf['cachedir'].'/word.idx',
                  $dokuConf['cachedir'].'/page.idx',
                  $dokuConf['cachedir'].'/index.idx');

  foreach($files as $file){
    if(!@file_exists($file)){
      $fh = @fopen($file,'a');
      if($fh){
        fclose($fh);
        if($dokuConf['fperm']) chmod($file, $dokuConf['fperm']);
      }else{
        nice_die("$file is not writable. Check your permissions settings!");
      }
    }
  }
}

/**
 * Returns absolute path
 *
 * This tries the given path first, then checks in DOKU_INC.
 * Check for accessability on directories as well.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function init_path($path){
  // check existance
  $p = realpath($path);
  if(!@file_exists($p)){
    $p = realpath(DOKU_INC.$path);
    if(!@file_exists($p)){
      return '';
    }
  }

  // check writability
  if(!@is_writable($p)){
    return '';
  }

  // check accessability (execute bit) for directories
  if(@is_dir($p) && !@file_exists("$p/.")){
    return '';
  }

  return $p;
}

/**
 * Sets the internal config values fperm and dperm which, when set,
 * will be used to change the permission of a newly created dir or
 * file with chmod. Considers the influence of the system's umask
 * setting the values only if needed.
 */
function init_creationmodes(){
  global $dokuConf;

  // Legacy support for old umask/dmask scheme
  unset($dokuConf['dmask']);
  unset($dokuConf['fmask']);
  unset($dokuConf['umask']);
  unset($dokuConf['fperm']);
  unset($dokuConf['dperm']);

  // get system umask, fallback to 0 if none available
  $umask = @umask();
  if(!$umask) $umask = 0000;

  // check what is set automatically by the system on file creation
  // and set the fperm param if it's not what we want
  $auto_fmode = 0666 & ~$umask;
  if($auto_fmode != $dokuConf['fmode']) $dokuConf['fperm'] = $dokuConf['fmode'];

  // check what is set automatically by the system on file creation
  // and set the dperm param if it's not what we want
  $auto_dmode = $dokuConf['dmode'] & ~$umask;
  if($auto_dmode != $dokuConf['dmode']) $dokuConf['dperm'] = $dokuConf['dmode'];
}

/**
 * remove magic quotes recursivly
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function remove_magic_quotes(&$array) {
  foreach (array_keys($array) as $key) {
    if (is_array($array[$key])) {
      remove_magic_quotes($array[$key]);
    }else {
      $array[$key] = stripslashes($array[$key]);
    }
  }
}

/**
 * Returns the full absolute URL to the directory where
 * DokuWiki is installed in (includes a trailing slash)
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function getBaseURL($abs=false){
  global $dokuConf;
  //if canonical url enabled always return absolute
  if($dokuConf['canonical']) $abs = true;

  if($dokuConf['basedir']){
    $dir = $dokuConf['basedir'].'/';
  }elseif(substr($_SERVER['SCRIPT_NAME'],-4) == '.php'){
    $dir = dirname($_SERVER['SCRIPT_NAME']).'/';
  }elseif(substr($_SERVER['PHP_SELF'],-4) == '.php'){
    $dir = dirname($_SERVER['PHP_SELF']).'/';
  }elseif($_SERVER['DOCUMENT_ROOT'] && $_SERVER['SCRIPT_FILENAME']){
    $dir = preg_replace ('/^'.preg_quote($_SERVER['DOCUMENT_ROOT'],'/').'/','',
                         $_SERVER['SCRIPT_FILENAME']);
    $dir = dirname('/'.$dir).'/';
  }else{
    $dir = './'; //probably wrong
  }

  $dir = str_replace('\\','/',$dir); #bugfix for weird WIN behaviour
  $dir = preg_replace('#//+#','/',$dir);

  //handle script in lib/exe dir
  $dir = preg_replace('!lib/exe/$!','',$dir);

  //handle script in lib/plugins dir
  $dir = preg_replace('!lib/plugins/.*$!','',$dir);

  //finish here for relative URLs
  if(!$abs) return $dir;

  //use config option if available
  if($dokuConf['baseurl']) return $dokuConf['baseurl'].$dir;

  //split hostheader into host and port
  list($host,$port)  = array_pad(explode(':',$_SERVER['HTTP_HOST']), 2, "");
  if(!$port)  $port = $_SERVER['SERVER_PORT'];
  if(!$port)  $port = 80;

  // see if HTTPS is enabled - apache leaves this empty when not available,
  // IIS sets it to 'off', 'false' and 'disabled' are just guessing
  if (preg_match('/^(|off|false|disabled)$/i',$_SERVER['HTTPS'])){
    $proto = 'http://';
    if ($port == '80') {
      $port='';
    }
  }else{
    $proto = 'https://';
    if ($port == '443') {
      $port='';
    }
  }

  if($port) $port = ':'.$port;

  return $proto.$host.$port.$dir;
}

/**
 * Append a PHP extension to a given file and adds an exit call
 *
 * This is used to migrate some old configfiles. An added PHP extension
 * ensures the contents are not shown to webusers even if .htaccess files
 * do not work
 *
 * @author Jan Decaluwe <jan@jandecaluwe.com>
 */
function scriptify($file) {
  // checks
  if (!is_readable($file)) {
    return;
  }
  $fn = $file.'.php';
  if (@file_exists($fn)) {
    return;
  }
  $fh = fopen($fn, 'w');
  if (!$fh) {
    nice_die($fn.' is not writable. Check your permission settings!');
  }
  // write php exit hack first
  fwrite($fh, "# $fn\n");
  fwrite($fh, '# <?php exit()?>'."\n");
  fwrite($fh, "# Don't modify the lines above\n");
  fwrite($fh, "#\n");
  // copy existing lines
  $lines = file($file);
  foreach ($lines as $line){
    fwrite($fh, $line);
  }
  fclose($fh);
  //try to rename the old file
  io_rename($file,"$file.old");
}

/**
 * print a nice message even if no styles are loaded yet.
 */
function nice_die($msg){
  echo<<<EOT
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
  <html>
    <head><title>DokuWiki Setup Error</title></head>
    <body style="font-family: Arial, sans-serif">
      <div style="width:60%; margin: auto; background-color: #fcc;
                  border: 1px solid #faa; padding: 0.5em 1em;">
      <h1 style="font-size: 120%">DokuWiki Setup Error</h1>
      <p>$msg</p>
      </div>
    </body>
  </html>
EOT;
  exit;
}


//Setup VIM: ex: et ts=2 enc=utf-8 :
