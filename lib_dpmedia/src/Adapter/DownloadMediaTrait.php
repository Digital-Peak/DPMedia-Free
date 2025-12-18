<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;

/**
 * Media download support for media adapters.
 */
trait DownloadMediaTrait
{
	use ResizeMediaTrait;

	/**
	 * The supported formats to generate thumbnails from.
	 */
	private array $supportedThumbnailImageFormats = ['jpg', 'jpeg', 'png', 'tiff', 'tif', 'gif', 'bmp', 'webp'];

	/**
	 * Returns the content of the file with the given config.
	 */
	abstract protected function getContent(\stdClass $file, Registry $config): string;

	/**
	* Returns the name.
	*/
	abstract protected function getName(): string;

	/**
	 * Downloads the given file to the local filesystem. The path relative to root is returned.
	 *
	 * @see DownloadMediaTrait::getMediaPath()
	 */
	public function download(\stdClass $file, Registry $config): string
	{
		$filePath = $this->getMediaPath($file, $config);

		// Set the timezone to UTC for filemtime
		$oldTZ = date_default_timezone_get();
		date_default_timezone_set('UTC');

		// Test if file exists and if modification date is greater or equal the given remote file
		if (file_exists(JPATH_SITE . $filePath) && filemtime(JPATH_SITE . $filePath) >= strtotime((string)$file->modified_date)) {
			date_default_timezone_set($oldTZ);
			return $filePath;
		}

		if (!file_exists(\dirname(JPATH_SITE . $filePath))) {
			Folder::create(\dirname(JPATH_SITE . $filePath));
		}

		file_put_contents(JPATH_SITE . $filePath, $this->getContent($file, $config));
		$this->resizeImage(JPATH_SITE . $filePath, $config->get('local_image_width', 0), $config->get('local_image_height', 0), 75, 1);
		touch(JPATH_SITE . $filePath, strtotime((string)$file->modified_date) ?: 0);
		date_default_timezone_set($oldTZ);

		return $filePath;
	}

	/**
	 * Generates a thumbnail in the thumbnail path from the config. Fallback is default file in media.
	 */
	protected function generateThumb(\stdclass $file, Registry $config): string
	{
		if ($file->type !== 'file' || !\in_array(strtolower((string)$file->extension), $this->supportedThumbnailImageFormats)) {
			return '';
		}

		$thumbConfig = new Registry([
			'local_media_path'   => $config->get('local_image_thumb_path', '/images/dp' . $this->getName() . '/thumbs'),
			'local_image_width'  => 120,
			'local_image_height' => 120
		]);

		$thumb = null;

		// Set the timezone to UTC for filemtime
		$oldTZ = date_default_timezone_get();
		date_default_timezone_set('UTC');

		$filePath = $this->getMediaPath($file, $thumbConfig);
		if (file_exists(JPATH_SITE . $filePath) && filemtime(JPATH_SITE . $filePath) >= strtotime((string)$file->modified_date)) {
			$thumb = rtrim(Uri::root(), '/') . $filePath;
		}
		date_default_timezone_set($oldTZ);

		// To not bloat, only a certain amount of thumbnails are generated
		static $thumbCount = 0;
		if ((\in_array($thumb, [null, '', '0'], true)) && $thumbCount < $config->get('local_image_thumb_count', 10)) {
			$thumb = rtrim(Uri::root(), '/') . $this->download($file, $thumbConfig);
			$thumbCount++;
		}

		if (\in_array($thumb, [null, '', '0'], true)) {
			return rtrim(Uri::root(), '/') . '/media/lib_dpmedia/images/default.jpg';
		}

		return $thumb;
	}

	/**
	 * Deletes a thumbnail in the thumbnail path from the config when it exists.
	 */
	protected function deleteThumb(string $path, Registry $config): void
	{
		$file            = new \stdClass();
		$file->extension = pathinfo($path, PATHINFO_EXTENSION);
		$file->name      = basename($path);
		$file->path      = $path;

		if (!\in_array($file->extension, $this->supportedThumbnailImageFormats)) {
			return;
		}

		$thumbConfig = new Registry([
			'local_media_path' => $config->get('local_image_thumb_path', '/images/dp' . $this->getName() . '/thumbs')
		]);

		$filePath = $this->getMediaPath($file, $thumbConfig);
		if (!file_exists(JPATH_SITE . $filePath)) {
			return;
		}

		unlink(JPATH_SITE . $filePath);
	}

	/**
	 * Returns the path to the media file relative to the root. The root of the file is taken from the config
	 * parameter local_media_path.
	 */
	protected function getMediaPath(\stdclass $file, Registry $config): string
	{
		$path = $config->get('local_media_path', '/images/dp' . $this->getName() . '/media') . '/';
		$path .= \dirname((string)$file->path) . '/';
		$path .= pathinfo((string)$file->name, PATHINFO_FILENAME);
		$path .= '.' . $file->extension;

		return Path::clean($path, '/');
	}
}
