<?php

// Copyright © 2007-2009 Johan Cwiklinski
//
// This file is part of Galette (http://galette.tuxfamily.org).
//
// Galette is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Galette is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Galette. If not, see <http://www.gnu.org/licenses/>.

/**
 * galette.inc.php
 *
 * @package Galette
 *
 * @author     Johan Cwiklinski <johan@x-tnd.be>
 * @copyright  2007-2009 Johan Cwiklinski
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version    $Id$
 * @since      Disponible depuis la Release 0.7alpha
 */

// test if galette is already installed and redirect to install page if not
$installed = file_exists(dirname( __FILE__).'/../config/config.inc.php');
if (! $installed) {
	header("location: install/index.php");
}

/**
* Import configuration settings
*/
if( !isset($base_path) ) $base_path = './';
require_once( $base_path . 'config/config.inc.php');
require_once( $base_path . 'config/versions.inc.php');

//we start a php session
session_start();

define('GALETTE_VERSION', 'v0.7alpha');
define('_templates_path', WEB_ROOT . 'templates/');
set_include_path(get_include_path() . PATH_SEPARATOR . WEB_ROOT . 'includes/pear/' . PATH_SEPARATOR . WEB_ROOT . 'includes/pear/PEAR-' . PEAR_VERSION . '/' . PATH_SEPARATOR . WEB_ROOT . 'includes/pear/MDB2-' . MDB2_VERSION . PATH_SEPARATOR . WEB_ROOT . 'includes/pear/Log-' . LOG_VERSION);

/*--------------------------------------------------------------------------------------
LOG and DEBUG
_file_log and _screen_log should take PEAR::LOG verbosity modes :
PEAR_LOG_EMERG		=>	System is unusable
PEAR_LOG_ALERT		=>	Immediate action required
PEAR_LOG_CRIT		=>	Critical conditions
PEAR_LOG_ERR		=>	Error conditions
PEAR_LOG_WARNING	=>	Warning conditions
PEAR_LOG_NOTICE		=>	Normal but significant
PEAR_LOG_INFO		=>	Informational
PEAR_LOG_DEBUG		=>	Debug-level messages

--------------------------------------------------------------------------------------*/
/** TODO
* - Set a database logger to replace actual one
*/
require_once('Log.php');
/** FIXME: for stables versions, log level must not be DEBUG, most probably WARNING or NOTICE */
define('_file_log', PEAR_LOG_INFO);				// ***** LOG : enregistrement des erreur dans un fichier de log
define('_log_file', WEB_ROOT . '/logs/galette.log');		// ***** LOG : fichier de log
define('_screen_log', PEAR_LOG_EMERG);			// ***** LOG : affichage des erreurs à l'écran

$conf = array(
	'error_prepend'	=>	'<div id="error" class="error">',
	'error_append'	=>	'</div>'
);
$display = &Log::singleton('display', '', 'galette', $conf, _screen_log);
$file = &Log::singleton('file', _log_file, 'galette', '', _file_log);

$log = &Log::singleton('composite');
$log->addChild($display);
$log->addChild($file);

/**
* MDB2 instanciation
*/
require_once(WEB_ROOT . '/classes/mdb2.class.php');
/** FIXME: mdb2 object should be stored into the session. This causes a fatal error on __destruct */
/*if( isset($_SESSION['galette']['db']) ){
	$mdb = unserialize($_SESSION['galette']['db']);
}else{
	$mdb2 = new GaletteMdb2();
	$_SESSION['galette']['db'] = serialize($mdb2);
}*/
$mdb = new GaletteMdb2();

/**
* Load preferences
*/
require_once(WEB_ROOT . 'classes/preferences.class.php');
$preferences = new Preferences();

/**
* Set the path to the current theme templates
*/
define('_current_template_path', _templates_path . $preferences->pref_theme . '/');

/**
* Language instantiation
*/
require_once(WEB_ROOT . 'classes/i18n.class.php');

if( isset($_SESSION['galette_lang']) ){
	$i18n = unserialize($_SESSION['galette_lang']);
}else{
	$i18n = new I18n();
	$_SESSION['galette_lang'] = serialize($i18n);
}

if( isset($_POST['pref_lang']) && strpos($_SERVER['PHP_SELF'], 'champs_requis.php') === false ){ $_GET['pref_lang'] = $_POST['pref_lang']; }
if( isset($_GET['pref_lang']) ){
	$i18n->changeLanguage( $_GET['pref_lang'] );
	$_SESSION['pref_lang'] = $_GET['pref_lang'];
}

/**
* Plugins
*/
define('PLUGINS_PATH', WEB_ROOT . 'plugins');
require_once( WEB_ROOT . 'classes/plugins.class.php');
$plugins = new plugins();
$plugins->loadModules(PLUGINS_PATH, $i18n->getFileName());

/**
* Authentication
*/
require_once(WEB_ROOT . 'classes/galette-login.class.php');
if(isset($_SESSION['galette']['login']))
	$login = unserialize($_SESSION['galette']['login']);
else $login = new GaletteLogin();

//required by Pictures object, so we put it there
/** TODO: use Mdb instead of Adodb for Pictures */
require_once(WEB_ROOT . 'includes/database.inc.php');

/**
* Members, alos load Adherent and Picture objects
*/
require_once(WEB_ROOT . 'classes/members.class.php');

/**
* Instanciate history object
*/
require_once(WEB_ROOT . 'classes/history.class.php');
if(isset($_SESSION['galette']['history']))
	$hist = unserialize($_SESSION['galette']['history']);
else $hist = new History();

/**
* Logo
*/
require_once(WEB_ROOT . 'classes/logo.class.php');
if( isset($_SESSION['galette']['logo']) ){
	$logo = unserialize($_SESSION['galette']['logo']);
} else {
	$logo = new Logo();
}

/**
* Now that all objects are correctly setted,
* we can include files that need it
*/
require_once(WEB_ROOT . 'includes/functions.inc.php');
include_once(WEB_ROOT . 'includes/session.inc.php');
require_once(WEB_ROOT . 'includes/i18n.inc.php');
require_once(WEB_ROOT . 'includes/smarty.inc.php');
?>