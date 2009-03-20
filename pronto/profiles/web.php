<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Execution profile for a web-based process.
 *
 **/

define('PROFILE', 'web');

// Core Libraries
require_once(DIR_FS_PRONTO.DS.'core'.DS.'registry.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'factory.php');
if(phpversion() < 5) {
	require_once(DIR_FS_PRONTO.DS.'core'.DS.'lazyload_php4.php');
} else {
	require_once(DIR_FS_PRONTO.DS.'core'.DS.'lazyload_php5.php');
}
require_once(DIR_FS_PRONTO.DS.'core'.DS.'web.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'template.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'session.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'access.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'validator.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'i18n.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'cache.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'util.php');

// URL route config
require_once(DIR_FS_APP.DS.'config/urls.php');
Registry::set('pronto:urls', $URLS);
unset($URLS);

// External Libraries
if(!function_exists('json_encode')) {
	require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'json-php4.php');
}

if(DEBUG === true && isset($_GET['phpinfo'])) {
	phpinfo();
	die;
}

/************************************************************************
 * CACHE INITIALIZATION
 ************************************************************************/
if(USE_CACHE === true && defined('CACHE_DRIVER')) {
	require_once(DIR_FS_PRONTO.DS.'core'.DS.'cache'.DS.CACHE_DRIVER.'.php');
	$cn = "Cache_".CACHE_DRIVER;
	$cache = new $cn();
	unset($cn);

	// allow the user to flush the cache if in DEBUG mode...
	if(DEBUG === true) {
		if(isset($_GET['cache_flush'])) {
			echo "<pre>Flushing Cache...</pre><br>\n";
			$cache->flush();
		}
		if(isset($_GET['cache_stats'])) {
			echo "<pre>".print_r($cache->stats(),true)."</pre><br>\n";
		}
	}
} else {
	$cache = new Cache();
}
$cache->gc();
Registry::set('pronto:cache', $cache);

/************************************************************************
 * MODULE INITIALIZATION
 ************************************************************************/
if(defined('MODULES')) {
	foreach(explode(' ', MODULES) as $modname) {
		$modpath = DIR_FS_APP.DS.'modules'.DS.$modname.DS.'config'.DS;
		if(file_exists($modpath.'config.php')) require_once($modpath.'config.php');
		if(file_exists($modpath.'urls.php')) {
			$old = Registry::get('pronto:urls');
			require_once($modpath.'urls.php');
			$URLS += $old;
			Registry::set('pronto:urls', $URLS);
			unset($old, $URLS);
		}
	}
	unset($modname, $modpath);
}

/************************************************************************
 * CHARACTER SET
 ************************************************************************/
ini_set('default_charset', 'UTF-8');
if(extension_loaded('mbstring')) {
	mb_internal_encoding(CHARSET);
}

/************************************************************************
 * CONNECT TO DATABASE
 ************************************************************************/
$db =& Factory::db(array(
	'dsn'  => DB_DSN,
	'file' => DB_FILE,
	'host' => DB_HOST,
	'user' => DB_USER,
	'pass' => DB_PASS,
	'name' => DB_NAME));
Registry::set('pronto:db', $db);

/************************************************************************
 * START THE SESSION HANDLER
 ************************************************************************/
if(isset($_COOKIE[SESSION_COOKIE])) {
	// start the session right away, as we have a SID already
	start_session();
}

/************************************************************************
 * ACCESS CONTROL
 ************************************************************************/
$access = new Access();
define('ACCESS_ID', $access->get_id());
Registry::set('pronto:access', $access);

/************************************************************************
 * INTERNATIONALIZATION
 ************************************************************************/
$i18n = new I18N();
$i18n->autoset_language('en');
define('LANG', $i18n->get_language());
Registry::set('pronto:i18n', $i18n);

/************************************************************************
 * WEB DISPATCH
 ************************************************************************/
$web = new Web(__FILE__);
$web->template_layout = 'layout.php';
Registry::set('pronto:web', $web);

/************************************************************************
 * PRELOAD PLUGINS
 ************************************************************************/
foreach(explode(' ', PLUGINS) as $p) {
	if($p) Factory::plugin($p, 'page');
}
unset($p);

/************************************************************************
 * REMAINING UTILITY CLASSES
 ************************************************************************/
$p = new Validator();
Registry::set('pronto:validator', $p);
unset($p);

/************************************************************************
 * HANDLE ERRORS/DEBUGGING/PROFILING
 ************************************************************************/
error_reporting(E_ALL & ~E_NOTICE);
if(DEBUG === true) {
	$web->enable_debug();
	//$web->profile();  // This is disabled by default, as it doesn't work
	                    // with threaded web server modules.
	$db->profile = true;
}

// Error handler for Debug and Production mode, defined in core/util.php.
// To override it, define your own pronto_error() function in
// app/profiles/web.php or somewhere else that is included before this file.
set_error_handler('pronto_error');

/************************************************************************
 * FINALLY, DISPATCH THE REQUEST
 ************************************************************************/
$urls = Registry::get('pronto:urls');
$web->run($urls);

?>