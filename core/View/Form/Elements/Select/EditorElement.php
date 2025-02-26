<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //

namespace ImpressCMS\Core\View\Form\Elements\Select;

use icms;
use ImpressCMS\Core\Extensions\Editors\EditorsRegistry;
use ImpressCMS\Core\View\Form\Elements\SelectElement;
use ImpressCMS\Core\View\Form\Elements\TrayElement;

/**
 * Creates a form attribute which is able to select an editor
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package	ICMS\Form\Elements\Select
 * @author	phppp (D.J.)
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class EditorElement extends TrayElement {
	/**
	 * Constructor
	 *
	 * @param	object	$form	the form calling the editor selection
	 * @param	string	$name	editor name
	 * @param	string	$value	Pre-selected text value
	 * @param	bool	$noHtml  dohtml disabled
	 */
	public function __construct(&$form, $name = 'editor', $value = null, $noHtml = false)
	{
		global $icmsConfig;

		if (empty($value)) {
			$value = $icmsConfig['editor_default'];
		}

		parent::__construct(_SELECT);

		/**
		 * @var EditorsRegistry $editorsRegistry
		 */
		$editorsRegistry = icms::getInstance()->get('\\' . EditorsRegistry::class);

		$edtlist = $editorsRegistry->getList('content');
		$option_select = new SelectElement('', $name, $value);
		$querys = preg_replace('/editor=(.*?)&/', '', $_SERVER['QUERY_STRING']);
		$extra = 'onchange="if(this.options[this.selectedIndex].value.length > 0 ){
				window.location = \'?editor=\'+this.options[this.selectedIndex].value+\'&' . $querys . '\';
			}"';
		$option_select->setExtra($extra);
		$option_select->addOptionArray($edtlist);

		$this->addElement($option_select);
	}
}
