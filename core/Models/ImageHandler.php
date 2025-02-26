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
 * Manage images
 *
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license    LICENSE.txt
 */

namespace ImpressCMS\Core\Models;

use icms;
use ImpressCMS\Core\Database\Criteria\CriteriaCompo;
use ImpressCMS\Core\Database\Criteria\CriteriaElement;
use ImpressCMS\Core\Database\Criteria\CriteriaItem;
use ImpressCMS\Core\Database\DatabaseConnectionInterface;

/**
 * Image handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of image class objects.
 *
 * @package    ICMS\Image
 * @author    Kazumi Ono    <onokazu@xoops.org>
 * @copyright    Copyright (c) 2000 XOOPS.org
 */
class ImageHandler extends AbstractExtendedHandler
{

	/**
	 * Handler for image bodies
	 *
	 * @var ImageBodyHandler
	 */
	protected $imagebody_handler;


	/**
	 * Constructor
	 *
	 * @param DatabaseConnectionInterface $db Database connection
	 */
	public function __construct(&$db)
	{
		$this->imagebody_handler = icms::handler('icms_image_body');

		parent::__construct($db, 'image', 'image_id', 'image_name', 'image_nicename', 'icms', 'image');
	}

	/**
	 * @inheritDoc
	 *
	 * @return array|false|Image|null
	 */
	public function &get($id, $as_object = true, $debug = false, $criteria = false)
	{
		$mainCriteria = new \Imponeer\Database\Criteria\CriteriaCompo(
			new \Imponeer\Database\Criteria\CriteriaItem('i.image_id', $id)
		);
		if ($criteria) {
			$mainCriteria->add($criteria);
		}
		$mainCriteria->setLimit(1);
		$image = $this->getObjects($mainCriteria, false, true);
		if (isset($image[0])) {
			$ret = $as_object ? $image[0] : $image[0]->toArray();
		} else {
			$ret = null;
		}
		return $ret;
	}

	/**
	 * This event is executed when saving
	 *
	 * @param ImageBody $obj Saving object
	 *
	 * @return  boolean
	 */
	protected function afterSave(&$obj)
	{
		if ($obj->image_body) {
			$body = $this->imagebody_handler->get($obj->image_id);
			$body->image_id = $obj->image_id;
			$body->image_body = $obj->image_body;
			return $body->store();
		}
		return true;
	}

	/**
	 * This event executes after deletion
	 *
	 * @param ImageBody $obj Deleted object
	 *
	 * @return boolean
	 */
	protected function afterDelete(&$obj)
	{
		$sql = sprintf('DELETE FROM %s WHERE image_id = %d', $this->imagebody_handler->table, $obj->image_id);
		$this->db->query($sql);
		return true;
	}

	/**
	 * Load image from the database
	 *
	 * @param null|CriteriaItem $criteria Criteria
	 * @param bool $id_as_key Use the ID as key into the array
	 * @param bool $getbinary Get binary image?
	 * @param bool|string $sql Extra sql
	 * @param bool $debug Debug mode?
	 *
	 * @return Image[]
	 */
	public function getObjects($criteria = null, $id_as_key = false, $getbinary = false, $sql = false, $debug = false)
	{
		if ($getbinary) {
			$this->generalSQL = 'SELECT i.*, b.image_body FROM ' . $this->table . ' i LEFT JOIN ' . $this->imagebody_handler->table . ' b ON b.image_id=i.image_id';
		} else {
			$this->generalSQL = '';
		}
		if ($criteria instanceof CriteriaElement) {
			if (!in_array($criteria->getSort(), array('image_id', 'image_created', 'image_mimetype', 'image_display', 'image_weight'))) {
				$criteria->setSort('image_weight');
			}
			if (($criteria instanceof CriteriaItem) && (!$criteria->prefix)) {
				$criteria->prefix = 'i';
			}
		}
		return parent::getObjects($criteria, $id_as_key, true, $sql, $debug);
	}

	/**
	 * Get a list of images
	 *
	 * @param int|null $imgcat_id Image category ID
	 * @param bool|null|int $image_display List only displayed images?
	 * @param int $notinuse Not use param (only added for fixing Declaration of ImageHandler::getList($imgcat_id, $image_display = NULL, $notinuse1 = 0, $debug = false) should be compatible with Handler::getList($criteria = NULL, $limit = 0, $start = 0, $debug = false) error)
	 * @param bool $debug Enable debug mode?
	 *
	 * @return Image[]
	 *
	 * @todo Do better fix here for declaration compatibility
	 */
	public function getList($imgcat_id = null, $image_display = 0, $notinuse = 0, $debug = false)
	{
		$criteria = new CriteriaCompo();
		if ($imgcat_id !== null) {
			$criteria->add(
				new CriteriaItem('imgcat_id', (int)($imgcat_id))
			);
		}
		if ($image_display) {
			$criteria->add(new CriteriaItem('image_display', (int)($image_display)));
		}
		$images = &$this->getObjects($criteria, false, true, false, true);
		$ret = array();
		foreach (array_keys($images) as $i) {
			$ret[$images[$i]->image_name] = $images[$i]->image_nicename;
		}
		return $ret;
	}
}
