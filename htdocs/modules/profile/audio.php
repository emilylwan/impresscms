<?php
/**
* Audios page
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Jan Pedersen, Marcello Brandao, Sina Asghari, Gustavo Pilla <contact@impresscms.org>
* @package		profile
* @version		$Id$
*/

/**
 * Edit a Audio
 *
 * @param object $audioObj ProfileAudio object to be edited
*/
function editaudio($audioObj)
{
	global $profile_audio_handler, $xoTheme, $icmsTpl, $icmsUser;

	if (!$audioObj->isNew()){
		if (!$audioObj->userCanEditAndDelete()) {
			redirect_header($audioObj->getItemLink(true), 3, _NOPERM);
		}
		$audioObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $audioObj->getSecureForm(_MD_PROFILE_AUDIO_EDIT, 'addaudio');
		$sform->assign($icmsTpl, 'profile_audioeditform');
		$icmsTpl->assign('profile_category_path', $audioObj->getVar('title') . ' > ' . _EDIT);
	} else {
		if (!$profile_audio_handler->userCanSubmit()) {
			redirect_header(PROFILE_URL, 3, _NOPERM);
		}
		$audioObj->setVar('uid_owner', $icmsUser->uid());
		$audioObj->setVar('creation_time', time());
		$audioObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $audioObj->getSecureForm(_MD_PROFILE_AUDIO_SUBMIT, 'addaudio');
		$sform->assign($icmsTpl, 'profile_audioform');
		$icmsTpl->assign('profile_category_path', _SUBMIT);
	}

	$xoTheme->addStylesheet(ICMS_URL . '/modules/profile/module'.(( defined("_ADM_USE_RTL") && _ADM_USE_RTL )?'_rtl':'').'.css');
}


$profile_template = 'profile_audio.html';
include_once 'header.php';

$profile_audio_handler = icms_getModuleHandler('audio');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';

if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
global $icmsUser;
$clean_audio_id = isset($_GET['audio_id']) ? intval($_GET['audio_id']) : 0 ;
$clean_uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0 ;
$real_uid = is_object($icmsUser)?intval($icmsUser->uid()):0;
$audioObj = $profile_audio_handler->get($clean_audio_id);
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','addaudio','del','');
/**
 * Only proceed if the supplied operation is a valid operation
 */
if (in_array($clean_op,$valid_op,true)){
  switch ($clean_op) {
	case "mod":
		$audioObj = $profile_audio_handler->get($clean_audio_id);
		if ($clean_audio_id > 0 && $audioObj->isNew()) {
			redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
		}
		editaudio($audioObj);
		break;

	case "addaudio":
        if (!$xoopsSecurity->check()) {
        	redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
        }
         include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
        $controller = new IcmsPersistableController($profile_audio_handler);
		$controller->storeFromDefaultForm(_MD_PROFILE_AUDIO_CREATED, _MD_PROFILE_AUDIO_MODIFIED);
		break;

	case "del":
		$audioObj = $profile_audio_handler->get($clean_audio_id);
		if (!$audioObj->userCanEditAndDelete()) {
			redirect_header($audioObj->getItemLink(true), 3, _NOPERM);
		}
		if (isset($_POST['confirm'])) {
		    if (!$xoopsSecurity->check()) {
		    	redirect_header($impresscms->urls['previouspage'], 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
		    }
		}
  	    include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
        $controller = new IcmsPersistableController($profile_audio_handler);
		$controller->handleObjectDeletionFromUserSide();
		$icmsTpl->assign('profile_category_path', $audioObj->getVar('title') . ' > ' . _DELETE);

		break;

	default:
		$values = array();
		if($real_uid){
			$audioObj = $profile_audio_handler->get($clean_audio_id);
			if ($clean_audio_id > 0 && $audioObj->isNew()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			}
			editaudio($audioObj);
		}
		if($clean_audio_id > 0){
			$audioArray = $profile_audio_handler->getAudio($clean_audio_id);
			$profile_audio_handler->updateCounter($clean_audio_id);
			$icmsTpl->assign('profile_single_audio', $audioObj->toArray());
			$icmsTpl->assign('profile_category_path', $audioArray['title']);
		}elseif($clean_uid > 0){
			$audioArray = $profile_audio_handler->getAudio(false, false, $clean_uid);
			$icmsTpl->assign('profile_allaudio', $audioArray);
		}elseif($real_uid > 0){
			$audioArray = $profile_audio_handler->getAudio(false, false, $real_uid);
			$icmsTpl->assign('profile_allaudio', $audioArray);
		}else{
			redirect_header(PROFILE_URL);
		}


		/**
		 * Generating meta information for this page
		 */
		$icms_metagen = new IcmsMetagen($audioObj->getVar('title'), $audioObj->getVar('meta_keywords','n'), $audioObj->getVar('meta_description', 'n'));
		$icms_metagen->createMetaTags();

		break;
	}
}
$icmsTpl->assign('profile_module_home', icms_getModuleName(true, true));

include_once 'footer.php';
?>