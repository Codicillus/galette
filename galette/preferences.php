<?php

// Copyright © 2004 Frédéric Jaqcuot
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
 * Choix des préférences de galette
 *
 * Les préférences se répartissent selon les groupes suivants:
 * - Association: Paramètres généraux de l'association tel
 *   que l'adresse, le logo etc...
 * - Galette: Préférences d'utilisation du logiciel
 * - Courriel: Adresse d'expéditeur et de réponse des courriers
 * - Etiquettes : Définition du format des étiquettes
 * - Cartes de membres : Choix du format des cartes
 * - Compte d'administration : Paramètres d'accès
 * 
 * @package    Galette
 *
 * @author     Frédéric Jaqcuot
 * @copyright  2004 Frédéric Jaqcuot
 * @copyright  2007-2009 Johan Cwiklinski
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version    $Id$
 * @since      Disponible depuis la Release 0.62
 */

/** @ignore */
require_once('includes/galette.inc.php');

if( !$login->isLogged() ){
	header("location: index.php");
	die();
}
if( !$login->isAdmin() ){
	header("location: voir_adherent.php");
	die();
}

require_once(WEB_ROOT . 'classes/print_logo.class.php');

// initialize warnings
$error_detected = array();
$warning_detected = array();
$confirm_detected = array();
$prefs_stored = false;
$print_logo = new PrintLogo();

// flagging required fields
$required = array(
	'pref_nom' => 1,
	'pref_lang' => 1,
	'pref_numrows' => 1,
	'pref_log' => 1,
	'pref_etiq_marges_v' => 1,
	'pref_etiq_marges_h' => 1,
	'pref_etiq_hspace' => 1,
	'pref_etiq_vspace' => 1,
	'pref_etiq_hsize' => 1,
	'pref_etiq_vsize' => 1,
	'pref_etiq_cols' => 1,
	'pref_etiq_rows' => 1,
	'pref_etiq_corps' => 1,
	'pref_card_abrev' => 1,
	'pref_card_strip' => 1,
	'pref_card_marges_v' => 1,
	'pref_card_marges_h' => 1,
	'pref_card_hspace' => 1,
	'pref_card_vspace' => 1,
	'pref_admin_login' => 1
);

$prefs_fields = $preferences->getFieldsNames();

// Validation
if (isset($_POST['valid']) && $_POST['valid'] == "1"){
	// verification de champs
	$insert_values = array();

	// obtain fields
	foreach($prefs_fields as $fieldname){
		if (isset($_POST[$fieldname]))
			$value=trim($_POST[$fieldname]);
		else
			$value="";

		// now, check validity
		if ($value != '')
			switch ($fieldname){
				case 'pref_email':
					if (!is_valid_email($value))
						$error_detected[] = _T("- Non-valid E-Mail address!");
						break;
				case 'pref_admin_login':
					if (strlen($value)<4)
						$error_detected[] = _T("- The username must be composed of at least 4 characters!");
					else{
						//check if login is already taken
						if( $login->loginExists($value) ) $error_detected[] = _T("- This username is already used by another member !");
					}
					break;
				case 'pref_numrows':
					if (!is_numeric($value) || $value <0)
						$error_detected[] = "<li>"._T("- The numbers and measures have to be integers!")."</li>";
					break;
				case 'pref_etiq_marges_h':
				case 'pref_etiq_marges_v':
				case 'pref_etiq_hspace':
				case 'pref_etiq_vspace':
				case 'pref_etiq_hsize':
				case 'pref_etiq_vsize':
				case 'pref_etiq_cols':
				case 'pref_etiq_rows':
				case 'pref_etiq_corps':
				case 'pref_card_marges_v':
				case 'pref_card_marges_h':
				case 'pref_card_hspace':
				case 'pref_card_vspace':
					// prevent division by zero
					if ($fieldname=='pref_numrows' && $value=='0')
						$value = '10';
						if (!is_numeric($value) || $value <0)
							$error_detected[] = _T("- The numbers and measures have to be integers!");
					break;
				case 'pref_card_tcol':
					// Set strip text color to white
					if (! preg_match("/#([0-9A-F]{6})/i", $value))
						$value = '#FFFFFF';
					break;
				case 'pref_card_scol':
				case 'pref_card_bcol':
				case 'pref_card_hcol':
					// Set strip background colors to black
					if (! preg_match("/#([0-9A-F]{6})/i", $value))
						$value = '#000000';
					break;
				case 'pref_admin_pass':
					if (strlen($value)<4)
						$error_detected[] = _T("- The password must be of at least 4 characters!");
					break;
				case 'pref_membership_ext':
					if (!is_numeric($value) || $value < 0)
						$error_detected[] = _T("- Invalid number of months of membership extension.");
					break;
				case 'pref_beg_membership':
					$beg_membership = explode("/",$value);
					if (count($beg_membership) != 2)
						$error_detected[] = _T("- Invalid format of beginning of membership.");
					else {
						$now = getdate();
						if (!checkdate($beg_membership[1], $beg_membership[0], $now['year']))
							$error_detected[] = _T("- Invalid date for beginning of membership.");
					}
					break;
			}

			// fill up pref structure (after $value's modifications)
			$pref[$fieldname] = stripslashes($value);

			$insert_values[$fieldname] = $value;
			$result->MoveNext();
	}

	// missing relations
	if (isset($insert_values['pref_mail_method'])){
		if ($insert_values['pref_mail_method']==2 || $insert_values['pref_mail_method']==1){
			if ($insert_values['pref_mail_method']==2){
				if (!isset($insert_values['pref_mail_smtp']) || $insert_values['pref_mail_smtp']=='')
					$error_detected[] = _T("- You must indicate the SMTP server you want to use!");
			}
			if (!isset($insert_values['pref_email_nom']) || $insert_values['pref_email_nom']=='')
				$error_detected[] = _T("- You must indicate a sender name for emails!");
			if (!isset($insert_values['pref_email']) || $insert_values['pref_email']=='')
				$error_detected[] = _T("- You must indicate an email address Galette should use to send emails!");
		}
	}

	if (isset($insert_values['pref_beg_membership']) && $insert_values['pref_beg_membership'] != '' && isset($insert_values['pref_membership_ext']) && $insert_values['pref_membership_ext'] != ''){
		$error_detected[] = _T("- Default membership extention and beginning of membership are mutually exclusive.");
	}

	// missing required fields?
	while (list($key,$val) = each($required)){
		if (!isset($pref[$key]))
			$error_detected[] = _T("- Mandatory field empty.")." ".$key;
		elseif (isset($pref[$key]))
			if (trim($pref[$key])=='')
				$error_detected[] = _T("- Mandatory field empty.")." ".$key;
	}

	// Check passwords. MD5 hash will be done into the Preferences class
	if(strcmp($insert_values['pref_admin_pass'],$_POST['pref_admin_pass_check']) != 0) {
		$error_detected[] = _T("Passwords mismatch");
	}

	if (count($error_detected)==0){
		// update preferences
		while (list($champ,$valeur)=each($insert_values)){
			if(($champ == "pref_admin_pass" && $_POST['pref_admin_pass']!= '') | ($champ != "pref_admin_pass")) {
				$preferences->$champ = $valeur;
			}
		}
		//once all values has been updated, we can store them
		if( !$preferences->store() ){
			$error_detected[] = _T("An SQL error has occured while storing preferences. Please try again, and contact the administrator if the problem persists.");
		} else {
			$prefs_stored = true;
		}

		// picture upload
		if (isset($_FILES['logo']) ){
			if ($_FILES['logo']['tmp_name'] !='' ) {
				if (is_uploaded_file($_FILES['logo']['tmp_name'])){
					$res = $logo->store($_FILES['logo']);
					if ( $res < 0) {
						switch($res){
							case Logo::INVALID_FILE:
								$patterns = array('|%s|', '|%t|');
								$replacements = array($logo->getAllowedExts(), htmlentities($logo->getBadChars()));
								$error_detected[] = preg_replace($patterns, $replacements, _T("- Filename or extension is incorrect. Only %s files are allowed. File name should not contains any of: %t"));
								break;
							case Logo::FILE_TOO_BIG:
								$error_detected[] = preg_replace('|%d|', Logo::MAX_FILE_SIZE, _T("File is too big. Maximum allowed size is %d"));
								break;
							case Logo::MIME_NOT_ALLOWED:
								/** FIXME: should be more descriptive */
								$error_detected[] = _T("Mime-Type not allowed");
								break;
							case Logo::SQL_ERROR:
							case Logo::SQL_BLOB_ERROR:
								$error_detected[] = _T("An SQL error has occured.");
								break;
							
						}
					}
					$logo = new Logo();
					$_SESSION['galette']['logo'] = serialize($logo);
					$tpl->assign('logo', $logo);
				}
			}
		}

		if (isset($_POST['del_logo'])){
			if (!$logo->delete()){
				$error_detected[] = _T("Delete failed");
			} else {
				$logo = new Logo(); //get default Logo
				$_SESSION['galette']['logo'] = serialize($logo);
				$tpl->assign('logo', $logo);
			}
		}

		// Card logo upload
		if (isset($_FILES['card_logo']) )
			if ($_FILES['card_logo']['tmp_name'] !='' ) {
				if (is_uploaded_file($_FILES['card_logo']['tmp_name'])){
					$res = $print_logo->store($_FILES['card_logo']);
					if ( $res < 0) {
						switch($res){
							case PrintLogo::INVALID_FILE:
								$patterns = array('|%s|', '|%t|');
								$replacements = array($print_logo->getAllowedExts(), htmlentities($print_logo->getBadChars()));
								$error_detected[] = preg_replace($patterns, $replacements, _T("- Filename or extension is incorrect. Only %s files are allowed. File name should not contains any of: %t"));
								break;
							case PrintLogo::FILE_TOO_BIG:
								$error_detected[] = preg_replace('|%d|', PrintLogo::MAX_FILE_SIZE, _T("File is too big. Maximum allowed size is %d"));
								break;
							case PrintLogo::MIME_NOT_ALLOWED:
								/** FIXME: should be more descriptive */
								$error_detected[] = _T("Mime-Type not allowed");
								break;
							case PrintLogo::SQL_ERROR:
							case PrintLogo::SQL_BLOB_ERROR:
								$error_detected[] = _T("An SQL error has occured.");
								break;
							
						}
					}
				}
			}

		if (isset($_POST['del_card_logo']))
			if (!$print_logo->delete()){
				$error_detected[] = _T("Delete failed");
			} else {
				$print_logo = new PrintLogo();
			}
	}
} else {
	// collect data
	foreach($prefs_fields as $fieldname){
		$pref[$fieldname] = $preferences->$fieldname;
	}
}

//List available themes
$themes = array();
$d = dir(_templates_path);
while (($entry = $d->read()) !== false) {
	$full_entry = _templates_path . $entry;
	if ($entry != '.' && $entry != '..' && is_dir($full_entry) && file_exists($full_entry.'/page.tpl'))
		$themes[] = $entry;
}
$d->close();

$tpl->assign('time', time());
$tpl->assign('pref', $pref);
$tpl->assign('pref_numrows_options', array(
	10 => '10',
	20 => '20',
	50 => '50',
	100 => '100',
	0 => _T('All'))
);
$tpl->assign('print_logo', $print_logo);
$tpl->assign('required', $required);
$tpl->assign('languages', $i18n->getList());
$tpl->assign('themes', $themes);
$tpl->assign('error_detected', $error_detected);
$tpl->assign('warning_detected', $warning_detected);
$tpl->assign('prefs_stored', $prefs_stored);
$tpl->assign('color_picker', true);
// page generation
$content = $tpl->fetch('preferences.tpl');
$tpl->assign('content', $content);
$tpl->display('page.tpl');
?>