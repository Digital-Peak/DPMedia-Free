<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPFtp\Adapter;

defined('_JEXEC') or die;

use DigitalPeak\Library\DPMedia\Adapter\Adapter;
use DigitalPeak\Plugin\Filesystem\DPFtp\FtpClientAwareInterface;
use DigitalPeak\Plugin\Filesystem\DPFtp\FtpClientAwareTrait;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Media\Administrator\Exception\FileNotFoundException;

/**
 * Read only FTP adapter for Joomla 4 media manager.
 */
class FtpAdapter extends Adapter implements FtpClientAwareInterface
{
	use FtpClientAwareTrait;

	protected $useLastPathSegment = false;

	public function fetchFile(string $path = '/'): \stdClass
	{
		// Somehow on create operations $path has //
		$path = Path::clean($path, '/');
		$file = array_filter($this->fetchFiles(dirname($path)), function ($f) use ($path) {
			return $f->path === $path;
		});
		if (!$file) {
			throw new FileNotFoundException('File not found');
		}

		return reset($file);
	}

	public function fetchFiles(string $path = '/'): array
	{
		if (pathinfo($path, PATHINFO_EXTENSION)) {
			$path = dirname($path);
		}

		$path = $this->getPath($path) ?: '/';
		$this->connect();
		$files = $this->getFtpClient()->mlsd($path);
		if ($files === false) {
			throw new \Exception(error_get_last() ? error_get_last()['message'] : 'Error');
		}

		$data = [];
		foreach ($files as $entry) {
			if ($entry['name'] == '.' || $entry['name'] == '..') {
				continue;
			}

			$data[] = $this->getFileInfo((object)$entry, $path);
		}

		return $data;
	}

	public function getUrl(string $path, bool $force = false): string
	{
		return $this->download($this->getFile($path));
	}

	protected function download(\stdClass $file, bool $force = false)
	{
		$filePath = Path::clean('/media/plg_filesystem_dpftp/.cache/' . pathinfo($file->name, PATHINFO_FILENAME) . '-' . $file->modified_date . '.' . $file->extension, '/');

		if (!file_exists(JPATH_SITE . $filePath) || $force) {
			if (!file_exists(dirname(JPATH_SITE . $filePath))) {
				Folder::create(dirname(JPATH_SITE . $filePath));
			}

			$this->connect();
			$success = $this->getFtpClient()->get(JPATH_SITE . $filePath, $this->getPath($file->path), FTP_BINARY);
			if ($success === false) {
				throw new \Exception(error_get_last() ? error_get_last()['message'] : 'Error');
			}
		}

		return Uri::root() . Path::clean($filePath, '/');
	}

	public function search(string $path, string $needle, bool $recursive = false): array
	{
		$files = $this->getFiles($path);

		$data = [];
		foreach ($files as $file) {
			if (strpos($file->name, $needle) === false) {
				continue;
			}
			$data[] = $file;
		}

		return $data;
	}

	/**
	 * Ensures the internal FTP client is ready.
	 */
	protected function connect()
	{
		if ($this->getFtpClient()->getConnection()) {
			return;
		}

		$this->getFtpClient()->connect($this->params->get('host'), $this->params->get('ssl', 1), $this->params->get('port', 21));
		$this->getFtpClient()->login($this->params->get('username'), $this->params->get('password'));
	}

	/**
	 * Extract file information from an entry of FTP.
	 *
	 * @param \stdClass $fileEntry
	 * @param string $path
	 *
	 * @return \stdClass
	 */
	private function getFileInfo(\stdClass $fileEntry, string $path): \stdClass
	{
		$file                          = new \stdClass();
		$file->type                    = $fileEntry->type == 'file' ? 'file' : 'dir';
		$file->name                    = $fileEntry->name;
		$file->path                    = rtrim($path, '/') . '/' . $fileEntry->name;
		$file->path                    = substr_replace($file->path, '', 0, strlen(rtrim($this->params->get('root_folder', '/'), '/')));
		$file->size                    = !empty($fileEntry->size) ? $fileEntry->size : (!empty($fileEntry->sizd) ? $fileEntry->sizd : 0);
		$file->width                   = 0;
		$file->height                  = 0;
		$file->create_date_formatted   = '';
		$file->modified_date_formatted = '';
		$file->create_date             = '';
		$file->modified_date_formatted = HTMLHelper::_('date', $this->getDate($fileEntry->modify), $this->app->getLanguage()->_('DATE_FORMAT_LC5'));
		$file->modified_date           = $fileEntry->modify;
		$file->extension               = '';
		$file->thumb_path              = '';

		if ($file->type == 'file') {
			$file->extension = pathinfo($file->name, PATHINFO_EXTENSION);
			$file->mime_type = $this->mimeTypeMapping->getMimetype($file->extension);
		}

		if (in_array($file->extension, $this->supportedThumbnailImageFormats)) {
			$file->url        = $this->download($file);
			$file->thumb_path = $file->url;
		}

		return $file;
	}
}
