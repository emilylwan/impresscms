<?php
/**
 * Resize plugin for Images Manager - Image Resize Tool
 *
 * Resizes an image
 *
 * @copyright The ImpressCMS Project http://www.impresscms.org/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package core
 * @since 1.2
 */

use WideImage\WideImage;

$xoopsOption['nodebug'] = 1;

/* 3 critical parameters must exist - and must be safe */
$image_path = $_GET['image_path'];
$image_url = filter_input(INPUT_GET, 'image_url', FILTER_SANITIZE_URL);
$filter = $_GET['filter'] ?? '';

/* prevent remote file inclusion */
$valid_path = ICMS_IMANAGER_FOLDER_PATH . '/temp';
if (!empty($image_path)) {
	$image_path = realpath($image_path);
	if (!str_starts_with($image_path, $valid_path)) {
		$image_path = null;
	}
} else {
	$image_path = null;
}

/* compare URL to ICMS_URL - it should be a full URL and within the domain, without traversal */
$submitted_url = parse_url($image_url);
$base_url = parse_url(ICMS_URL); // \icms::$urls not available?
if ($submitted_url['scheme'] != $base_url['scheme']) $image_url = null;
if ($submitted_url['host'] != $base_url['host']) $image_url = null;
if ($submitted_url['path'] != parse_url(ICMS_IMANAGER_FOLDER_URL . '/temp/' . basename($image_path), PHP_URL_PATH)) $image_url = null;

if (!isset($image_path) || !isset($image_url)) {
	echo "alert('" . _ERROR . "');";
} else {
	$fit = 'inside';
	$width = null;
	$height = null;

	if (isset($_GET['width'])) {
		if (substr($_GET['width'], -1, 1) == '%') {
			$width = (int) $_GET['width'] . "%";
			$fit = 'fill';
		} else {
			$width = (int) $_GET['width'];
		}
	}

	if (isset($_GET['height'])) {
		if (substr($_GET['height'], -1, 1) == '%') {
			$height = (int) $_GET['height'] . "%";
			$fit = 'fill';
		} else {
			$height = (int) $_GET['height'];
		}
	}

	$save = isset($_GET['save']) ? (int) $_GET['save'] : 0;
	$del = isset($_GET['delprev']) ? (int) $_GET['delprev'] : 0;

	$img = WideImage::load($image_path);
	$arr = explode('/', $image_path);
	$arr[count($arr) - 1] = 'resize_' . $arr[count($arr) - 1];
	$temp_img_path = implode('/', $arr);
	$arr = explode('/', $image_url);
	$arr[count($arr) - 1] = 'resize_' . $arr[count($arr) - 1];
	$temp_img_url = implode('/', $arr);

	if ($del) {
		@unlink($temp_img_path);
		exit();
	}

	$img->resize($width, $height, $fit)->saveToFile($temp_img_path);

	if ($save) {
		if (!@unlink($image_path)) {
			echo "alert('" . _ERROR . "');";
			exit();
		}
		if (!@copy($temp_img_path, $image_path)) {
			echo "alert('" . _ERROR . "');";
			exit();
		}
		if (!@unlink($temp_img_path)) {
			echo "alert('" . _ERROR . "');";
			exit();
		}
		echo 'window.location.reload( true );';
	} else {
		echo "var w = window.open('" . $temp_img_url . "','resize_image_preview','width=" . ($width + 20) . ",height=" . ($height + 20) . ",resizable=yes');";
		echo "w.onunload = function (){resize_delpreview();}";
	}
}
