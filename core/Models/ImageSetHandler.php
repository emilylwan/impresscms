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
/**
 * Manage of imagesets baseclass
 * Image sets - the image directory within a module - are part of templates
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 */
namespace ImpressCMS\Core\Models;

use Exception;
use Imponeer\Database\Criteria\CriteriaCompo;
use Imponeer\Database\Criteria\CriteriaItem;
use ImpressCMS\Core\Database\Criteria\CriteriaElement;
use ImpressCMS\Core\Database\DatabaseConnectionInterface;
use RuntimeException;

/**
 * XOOPS imageset handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS imageset class objects.
 *
 * @package    ICMS\Image\Set
 * @author      Kazumi Ono <onokazu@xoops.org>
 * @copyright	Copyright (c) 2000 XOOPS.org
 */
class ImageSetHandler extends AbstractExtendedHandler {

	/**
	 * Constructor
	 *
	 * @param DatabaseConnectionInterface $db              Database connection
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'image_set', 'imgset_id', 'imgset_name', '', 'icms', 'imgset');
	}

	/**
	 * This event executes after deletion
	 *
	 * @param ImageSet $obj           Instance of icms_image_set_Object
	 *
	 * @return boolean
	 */
	protected function afterDelete($obj) {
		$sql = sprintf("DELETE FROM %s WHERE imgset_id = '%u'", $this->db->prefix('imgset_tplset_link'), $obj->imgset_id);
		$this->db->query($sql);
		return true;
	}

	/**
	 * Retrieve array of images meeting certain conditions
	 *
	 * @param \Imponeer\Database\Criteria\CriteriaElement $criteria Criteria with conditions for the imagesets
	 * @param bool $id_as_key should the imageset's imgset_id be the key for the returned array?
	 * @param bool $as_object
	 * @param bool $sql
	 * @param bool $debug
	 *
	 * @return ImageSet[]
	 *
	 * @throws Exception
	 */
	public function getObjects($criteria = null, $id_as_key = false, $as_object = true, $sql = false, $debug = false)
	{
		if ($sql) {
			throw new RuntimeException('$sql must be set to false');
		}
		$ret = array();
		$limit = $start = 0;
		$sql = sprintf(
			'SELECT DISTINCT i.* FROM %s i LEFT JOIN %s l ON l.imgset_id=i.imgset_id',
			$this->table,
			$this->db->prefix('imgset_tplset_link')
		);
		if (isset($criteria) && is_subclass_of($criteria, CriteriaElement::class)) {
			$sql .= ' ' . $criteria->renderWhere();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$imgset = new ImageSet($this, $myrow);
			if (!$id_as_key) {
				$ret[] = & $imgset;
			} else {
				$ret[$myrow['imgset_id']] = & $imgset;
			}
			unset($imgset);
		}
		return $ret;
	}

	/**
	 * Links a image set to a themeset (tplset)
	 * @param int $imgset_id image set id to link
	 * @param int $tplset_name theme set to link
	 *
	 * @return bool TRUE if succesful FALSE if unsuccesful
	 */
	public function linkThemeset($imgset_id, $tplset_name) {
		$imgset_id = (int) $imgset_id;
		$tplset_name = trim($tplset_name);
		if ($imgset_id <= 0 || $tplset_name == '') {
			return false;
		}
		if (!$this->unlinkThemeset($imgset_id, $tplset_name)) {
			return false;
		}
		$sql = sprintf(
			"INSERT INTO %s (imgset_id, tplset_name) VALUES ('%u', %s)",
			$this->db->prefix('imgset_tplset_link'),
			$imgset_id, $this->db->quoteString($tplset_name)
		);
		$result = $this->db->query($sql);
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	 * Unlinks a image set from a themeset (tplset)
	 *
	 * @param int $imgset_id image set id to unlink
	 * @param int $tplset_name theme set to unlink
	 *
	 * @return bool TRUE if succesful FALSE if unsuccesful
	 * */
	public function unlinkThemeset($imgset_id, $tplset_name) {
		$imgset_id = (int) $imgset_id;
		$tplset_name = trim($tplset_name);
		if ($imgset_id <= 0 || $tplset_name == '') {
			return false;
		}
		$sql = sprintf(
			"DELETE FROM %s WHERE imgset_id = '%u' AND tplset_name = %s",
			$this->db->prefix('imgset_tplset_link'),
			$imgset_id,
			$this->db->quoteString($tplset_name)
		);
		$result = $this->db->query($sql);
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	 * Get a list of image set objects matching certain conditions
	 *
	 * @param null $criteria
	 * @param int $limit
	 * @param int $start
	 * @param bool $debug
	 * @param int $refid conditions to match
	 * @param int $tplset conditions to match
	 *
	 * @return ImageSet[]
	 * @throws Exception
	 */
	public function getList($criteria = null, $limit = 0, $start = 0, $debug = false, $refid = null, $tplset = null)
	{
		if (!($criteria instanceof CriteriaCompo)) {
			$criteria = new CriteriaCompo();
		}
		if (isset($refid)) {
			$criteria->add(new CriteriaItem('imgset_refid', (int)$refid));
		}
		if (isset($tplset)) {
			$criteria->add(new CriteriaItem('tplset_name', $tplset));
		}
		$imgsets = $this->getObjects($criteria, true);
		$ret = array();
		foreach (array_keys($imgsets) as $i) {
			$ret[$i] = $imgsets[$i]->imgset_name;
		}
		return $ret;
	}

}
