<?php
/**
 * This is DokuWiki's Main Configuration file
 *
 * All the default values are kept here, you should not modify it but use
 * a local.php file instead to override the settings from here.
 *
 * This is a piece of PHP code so PHP syntax applies!
 *
 * For help with the configuration see http://www.splitbrain.org/dokuwiki/wiki:config
 */


/* Datastorage and Permissions */

$dokuConf['fmode']       = 0644;              //set file creation mode
$dokuConf['dmode']       = 0755;              //set directory creation mode
$dokuConf['lang']        = 'en';              //your language
$dokuConf['basedir']     = '';                //absolute dir from serveroot - blank for autodetection
$dokuConf['baseurl']     = '';                //URL to server including protocol - blank for autodetect
$dokuConf['savedir']     = './data';          //where to store all the files
$dokuConf['allowdebug']  = 0;                 //allow debug output, enable if needed 0|1

/* Display Options */

$dokuConf['start']       = 'start';           //name of start page
$dokuConf['title']       = 'DokuWiki';        //what to show in the title
$dokuConf['template']    = 'default';         //see tpl directory
$dokuConf['fullpath']    = 0;                 //show full path of the document or relative to datadir only? 0|1
$dokuConf['recent']      = 20;                //how many entries to show in recent
$dokuConf['breadcrumbs'] = 10;                //how many recent visited pages to show
$dokuConf['youarehere']  = 0;                 //show "You are here" navigation? 0|1
$dokuConf['typography']  = 1;                 //convert quotes, dashes and stuff to typographic equivalents? 0|1
$dokuConf['htmlok']      = 0;                 //may raw HTML be embedded? This may break layout and XHTML validity 0|1
$dokuConf['phpok']       = 0;                 //may PHP code be embedded? Never do this on the internet! 0|1
$dokuConf['dformat']     = 'Y/m/d H:i';       //dateformat accepted by PHPs date() function
$dokuConf['signature']   = ' --- //[[@MAIL@|@NAME@]] @DATE@//'; //signature see wiki:config for details
$dokuConf['toptoclevel'] = 1;                 //Level starting with and below to include in AutoTOC (max. 5)
$dokuConf['maxtoclevel'] = -1;                 //Up to which level include into AutoTOC (max. 5)
$dokuConf['maxseclevel'] = 3;                 //Up to which level create editable sections (max. 5)
$dokuConf['camelcase']   = 0;                 //Use CamelCase for linking? (I don't like it) 0|1
$dokuConf['deaccent']    = 1;                 //deaccented chars in pagenames (1) or romanize (2) or keep (0)?
$dokuConf['useheading']  = 0;                 //use the first heading in a page as its name
$dokuConf['refcheck']    = 1;                 //check for references before deleting media files
$dokuConf['refshow']     = 0;                 //how many references should be shown, 5 is a good value

/* Antispam Features */

$dokuConf['usewordblock']= 1;                 //block spam based on words? 0|1
$dokuConf['indexdelay']  = 60*60*24*5;        //allow indexing after this time (seconds) default is 5 days
$dokuConf['relnofollow'] = 1;                 //use rel="nofollow" for external links?
$dokuConf['mailguard']   = 'hex';             //obfuscate email addresses against spam harvesters?
                                          //valid entries are:
                                          //  'visible' - replace @ with [at], . with [dot] and - with [dash]
                                          //  'hex'     - use hex entities to encode the mail address
                                          //  'none'    - do not obfuscate addresses

/* Authentication Options - read http://www.splitbrain.org/dokuwiki/wiki:acl */

$dokuConf['useacl']      = 0;                //Use Access Control Lists to restrict access?
$dokuConf['autopasswd']  = 1;                //autogenerate passwords and email them to user
$dokuConf['authtype']    = 'plain';          //which authentication backend should be used
$dokuConf['passcrypt']   = 'smd5';           //Used crypt method (smd5,md5,sha1,ssha,crypt,mysql,my411)
$dokuConf['defaultgroup']= 'user';           //Default groups new Users are added to
$dokuConf['superuser']   = '!!not set!!';    //The admin can be user or @group
$dokuConf['profileconfirm'] = '1';           //Require current password to confirm changes to user profile
$dokuConf['disableactions'] = '';            //comma separated list of actions to disable

/* Advanced Options */

$dokuConf['updatecheck'] = 1;                //automatically check for new releases?
$dokuConf['userewrite']  = 0;                //this makes nice URLs: 0: off 1: .htaccess 2: internal
$dokuConf['useslash']    = 0;                //use slash instead of colon? only when rewrite is on
$dokuConf['usedraft']    = 1;                //automatically save a draft while editing (0|1)
$dokuConf['sepchar']     = '_';              //word separator character in page names; may be a
                                         //  letter, a digit, '_', '-', or '.'.
$dokuConf['canonical']   = 0;                //Should all URLs use full canonical http://... style?
$dokuConf['autoplural']  = 0;                //try (non)plural form of nonexisting files?
$dokuConf['compression'] = 'gz';             //compress old revisions: (0: off) ('gz': gnuzip) ('bz2': bzip)
                                         //  bz2 generates smaller files, but needs more cpu-power
$dokuConf['cachetime']   = 60*60*24*10;      //maximum age for cachefile in seconds (defaults to a day)
$dokuConf['locktime']    = 15*60;            //maximum age for lockfiles (defaults to 15 minutes)
$dokuConf['fetchsize']   = 0;                //maximum size (bytes) fetch.php may download from extern, disabled by default
$dokuConf['notify']      = '';               //send change info to this email (leave blank for nobody)
$dokuConf['registernotify'] = '';            //send info about newly registered users to this email (leave blank for nobody)
$dokuConf['mailfrom']    = '';               //use this email when sending mails
$dokuConf['gzip_output'] = 0;                //use gzip content encodeing for the output xhtml (if allowed by browser)
$dokuConf['gdlib']       = 2;                //the GDlib version (0, 1 or 2) 2 tries to autodetect
$dokuConf['im_convert']  = '';               //path to ImageMagicks convert (will be used instead of GD)
$dokuConf['jpg_quality'] = '70';             //quality of compression when scaling jpg images (0-100)
$dokuConf['spellchecker']= 0;                //enable Spellchecker (needs PHP >= 4.3.0 and aspell installed)
$dokuConf['subscribers'] = 0;                //enable change notice subscription support
$dokuConf['compress']    = 1;                //Strip whitespaces and comments from Styles and JavaScript? 1|0
$dokuConf['hidepages']   = '';               //Regexp for pages to be skipped from RSS, Search and Recent Changes
$dokuConf['send404']     = 0;                //Send a HTTP 404 status for non existing pages?
$dokuConf['sitemap']     = 0;                //Create a google sitemap? How often? In days.
$dokuConf['rss_type']    = 'rss1';           //type of RSS feed to provide, by default:
                                         //  'rss'  - RSS 0.91
                                         //  'rss1' - RSS 1.0
                                         //  'rss2' - RSS 2.0
                                         //  'atom' - Atom 0.3
$dokuConf['rss_linkto'] = 'diff';            //what page RSS entries link to:
                                         //  'diff'    - page showing revision differences
                                         //  'page'    - the revised page itself
                                         //  'rev'     - page showing all revisions
                                         //  'current' - most recent revision of page
$dokuConf['rss_update'] = 5*60;              //Update the RSS feed every n minutes (defaults to 5 minutes)
$dokuConf['recent_days'] = 7;                //How many days of recent changes to keep. (days)

//Set target to use when creating links - leave empty for same window
$dokuConf['target']['wiki']      = '';
$dokuConf['target']['interwiki'] = '';
$dokuConf['target']['extern']    = '';
$dokuConf['target']['media']     = '';
$dokuConf['target']['windows']   = '';

//Proxy setup - if your Server needs a proxy to access the web set these
$dokuConf['proxy']['host'] = '';
$dokuConf['proxy']['port'] = '';
$dokuConf['proxy']['user'] = '';
$dokuConf['proxy']['pass'] = '';
$dokuConf['proxy']['ssl']  = 0;

/* Safemode Hack */

$dokuConf['safemodehack'] = 0;               //read http://wiki.splitbrain.org/wiki:safemodehack !
$dokuConf['ftp']['host'] = 'localhost';
$dokuConf['ftp']['port'] = '21';
$dokuConf['ftp']['user'] = 'user';
$dokuConf['ftp']['pass'] = 'password';
$dokuConf['ftp']['root'] = '/home/user/htdocs';

