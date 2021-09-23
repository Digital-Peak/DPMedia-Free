<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Media download support for media adapters.
 */
trait DownloadMediaTrait
{
	/**
	 * The supported formats to generate thumbnails from.
	 */
	private $supportedThumbnailImageFormats = ['jpg', 'jpeg', 'png', 'tiff', 'tif', 'gif', 'bmp'];

	/**
	 * Returns the content of the file with the given config.
	 *
	 * @param \stdClass $file
	 * @param Registry  $config
	 *
	 * @return string
	 */
	abstract protected function getContent(\stdclass $file, Registry $config): string;

	/**
	* Returns the name.
	*
	* @return string
	*/
	abstract protected function getName(): string;

	/**
	 * Downloads the given file to the local filesystem. The path relative to root is returned.
	 *
	 * @param \stdClass $file
	 * @param Registry  $config
	 *
	 * @return string
	 *
	 * @see DownloadMediaTrait::getMediaPath()
	 */
	protected function download(\stdClass $file, Registry $config): string
	{
		$filePath = $this->getMediaPath($file, $config);

		// Test if file exists and if modification date is greather or equal the given remote file
		if (file_exists(JPATH_SITE . $filePath) && filemtime(JPATH_SITE . $filePath) >= strtotime($file->modified_date)) {
			return $filePath;
		}

		if (!file_exists(dirname(JPATH_SITE . $filePath))) {
			Folder::create(dirname(JPATH_SITE . $filePath));
		}

		file_put_contents(JPATH_SITE . $filePath, $this->getContent($file, $config));
		touch(JPATH_SITE . $filePath, strtotime($file->modified_date));

		// If it is not an image or no special sizes are defined, write the image
		if (!in_array($file->extension, ['jpg', 'jpeg', 'png', 'gif'])
			|| (!$config->get('local_image_width') && !$config->get('local_image_height'))) {
			return $filePath;
		}

		$imgObject = new Image(JPATH_SITE . $filePath);

		// If image is smaller, then do no resize
		if ($imgObject->getWidth() < $config->get('local_image_width', 0)
			&& $imgObject->getHeight() < $config->get('local_image_height', 0)) {
			return $filePath;
		}

		$imgObject->resize($config->get('local_image_width', 0), $config->get('local_image_height', 0), false, Image::SCALE_INSIDE);

		$type = IMAGETYPE_JPEG;
		switch ($file->extension) {
			case 'gif':
				$type = IMAGETYPE_GIF;
				break;
			case 'png':
				$type = IMAGETYPE_PNG;
		}

		$imgObject->toFile(JPATH_SITE . $filePath, $type);
		touch(JPATH_SITE . $filePath, strtotime($file->modified_date));

		return $filePath;
	}

	/**
	 * Generates a thumbnail in the thumbnail path from the config. Fallback is default file in media.
	 *
	 * @param \stdClass $file
	 * @param Registry  $config
	 *
	 * @return string
	 */
	protected function generateThumb(\stdclass $file, Registry $config): string
	{
		if (!in_array($file->extension, $this->supportedThumbnailImageFormats)) {
			return '';
		}

		$thumbConfig = new Registry([
			'local_media_path'   => $config->get('local_image_thumb_path', '/images/dp' . $this->getName() . '/thumbs'),
			'local_image_width'  => 120,
			'local_image_height' => 120
		]);

		$thumb = null;

		$filePath = $this->getMediaPath($file, $thumbConfig);
		if (file_exists(JPATH_SITE . $filePath) && filemtime(JPATH_SITE . $filePath) >= strtotime($file->modified_date)) {
			$thumb = rtrim(Uri::root(), '/')  . $filePath;
		}

		// To not bloat, only a certain amount of thumbnails are generated
		static $thumbCount = 0;
		if (!$thumb && $thumbCount < $config->get('thumb_count', 10)) {
			$thumb = rtrim(Uri::root(), '/') .  $this->download($file, $thumbConfig);
			$thumbCount++;
		}

		if (!$thumb) {
			$thumb = rtrim(Uri::root(), '/')  . '/media/lib_dpmedia/images/default.jpg';
		}

		return $thumb;
	}

	/**
	 * Returns the path to the media file relative to the root. The root of the file is taken from the config
	 * parameter local_media_path.
	 *
	 * @param \stdClass $file
	 * @param Registry  $config
	 *
	 * @return string
	 */
	protected function getMediaPath(\stdclass $file, Registry $config): string
	{
		$path = $config->get('local_media_path', '/images/dp' . $this->getName() . '/media') . '/';
		$path .= dirname($file->path) . '/';
		$path .= pathinfo($file->name, PATHINFO_FILENAME);
		$path .= '.' . $file->extension;

		return Path::clean($path, '/');
	}
}
