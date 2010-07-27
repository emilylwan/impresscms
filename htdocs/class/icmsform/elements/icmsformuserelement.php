<?php
/**
 * Form control creating a simple users selectbox for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		  1.1
 * @author		  marcan <marcan@impresscms.org>
 * @version		$Id$
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class IcmsFormUserElement extends icms_form_elements_Select {
	var $multiple = false;

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	function IcmsFormUserElement($object, $key) {
		$var = $object->vars[$key];
		$size = isset($var['size']) ? $var['size'] : ($this->multiple ? 5 : 1);

		parent::__construct($var['form_caption'], $key, $object->getVar($key, 'e'), $size, $this->multiple);

		// Adding the options inside this SelectBox
		// If the custom method is not from a module, than it's from the core
		$control = $object->getControl($key);

		global $xoopsDB;
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT uid, uname FROM '.$xoopsDB->prefix('users');
		$sql .= ' ORDER BY uname ASC';

		$result = $xoopsDB->query($sql);
		if ($result) {
			while ($myrow = $xoopsDB->fetchArray($result)) {
				$uArray[$myrow['uid']] = $myrow['uname'];
			}
		}
		$this->addOptionArray($uArray);

	}
}

?>