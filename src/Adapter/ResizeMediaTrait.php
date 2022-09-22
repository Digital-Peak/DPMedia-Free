<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

use Joomla\CMS\Image\Image;

/**
 * Media resize support.
 */
trait ResizeMediaTrait
{
	/**
	 * Resizes the image on the current path with width and height. If the path is an
	 * object then it must have the properties extension and data.
	 *
	 * @param mixed   $path
	 * @param integer $width
	 * @param integer $height
	 * @param integer $quality
	 */
	protected function resizeImage($path, $width, $height, $quality = 80)
	{
		$extension = is_object($path) ? $path->extension : pathinfo($path, PATHINFO_EXTENSION);
		$extension = strtolower($extension);
		if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
			return;
		}

		if (!$width && !$height) {
			return;
		}

		$imgObject = new Image(is_object($path) ? imagecreatefromstring($path->data) : $path);
		if ($width && $height) {
			$imgObject->cropResize($width, $height, false);
		} else {
			$imgObject->resize($width, $height, false, Image::SCALE_INSIDE);
		}

		$type = IMAGETYPE_JPEG;
		switch ($extension) {
			case 'gif':
				$type = IMAGETYPE_GIF;
				break;
			case 'png':
				$type = IMAGETYPE_PNG;
		}

		// The quality of png's must be between 0 and 9
		if ($extension === 'png') {
			$quality = min(9, $quality / 10);
		}

		if (!is_object($path)) {
			$imgObject->toFile($path, $type, ['quality' => $quality]);
			return;
		}

		ob_start();
		$imgObject->toFile(null, $type, ['quality' => $quality]);
		$path->data = ob_get_contents();
		ob_end_clean();
	}
}
