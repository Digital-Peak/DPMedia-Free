<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
	 * @param integer $aspectRation
	 */
	protected function resizeImage($path, $width, $height, $quality = 80, $aspectRation = 1)
	{
		// Get the extension
		$extension = is_object($path) ? $path->extension : pathinfo($path, PATHINFO_EXTENSION);
		$extension = strtolower($extension);

		// Only resize images we can actually handle
		if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
			return;
		}

		// Do nothing when new dimensions are not set
		if (!$width && !$height) {
			return;
		}

		// Create the image object
		$imgObject = new Image(is_object($path) ? imagecreatefromstring($path->data) : $path);

		// Get image dimensions
		$imageWidth  = $imgObject->getWidth();
		$imageHeight = $imgObject->getHeight();

		// Do not enlarge
		if ($width > $imageWidth && $height > $imageHeight) {
			return;
		}

		// Modify the new dimensions when the aspect ratio should be kept
		if ($aspectRation == 1) {
			// When there is a width and the width of the image is bigger, reset the height
			if ($width && $imageWidth > $imageHeight) {
				$height = 0;
			}

			// When there is a height and the height of the image is bigger, reset the width
			if ($height && $imageHeight > $imageWidth) {
				$width = 0;
			}
		}

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
			$quality = (int)min(9, $quality / 10);
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
