<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPFtp\Adapter;

use DigitalPeak\Library\DPMedia\Adapter\Adapter;
use DigitalPeak\Library\DPMedia\Adapter\DownloadMediaTrait;
use DigitalPeak\Library\DPMedia\Adapter\MimeTypeMapping;
use DigitalPeak\Plugin\Filesystem\DPFtp\FtpClientAwareInterface;
use DigitalPeak\Plugin\Filesystem\DPFtp\FtpClientAwareTrait;
use DigitalPeak\ThinHTTP;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Media\Administrator\Exception\FileNotFoundException;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Read only FTP adapter for Joomla 4 media manager.
 */
class FtpAdapter extends Adapter implements FtpClientAwareInterface
{
	use FtpClientAwareTrait;
	use DownloadMediaTrait;

	protected $useLastPathSegment = false;

	public function __construct(Registry $config, ThinHTTP $http, MimeTypeMapping $mimeTypeMapping, DatabaseInterface $db, CMSApplication $app)
	{
		$config->set('local_media', 1);
		parent::__construct($config, $http, $mimeTypeMapping, $db, $app);
	}

	protected function fetchFile(string $path = '/'): \stdClass
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

	protected function fetchFiles(string $path = '/'): array
	{
		$path = $this->getPath($path) ?: '/';

		$ftpPath = $path;
		if (strpos($path, '.') !== false) {
			$ftpPath = dirname($path);
		}

		$this->connect();
		$files = $this->getFtpClient()->mlsd($ftpPath);
		if ($files === false) {
			throw new \Exception(error_get_last() ? error_get_last()['message'] : 'Error');
		}

		$data = [];
		foreach ($files as $entry) {
			if ($entry['name'] == '.' || $entry['name'] == '..') {
				continue;
			}

			// When the path is different, then a file is fetched and the list should only contain the file
			if ($ftpPath !== $path && $ftpPath. '/' . $entry['name'] !== $path) {
				continue;
			}

			$data[] = $this->getFileInfo((object)$entry, $ftpPath);
		}

		return $data;
	}

	protected function fetchUrl(string $path): string
	{
		return rtrim(Uri::root(), '/') .  $this->download($this->getFile($path), $this->getConfig());
	}

	protected function fetchSearch(string $path, string $needle, bool $recursive = false): array
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

		$this->getFtpClient()->connect($this->getConfig()->get('host'), $this->getConfig()->get('ssl', 1), $this->getConfig()->get('port', 21));
		$this->getFtpClient()->login($this->getConfig()->get('username'), $this->getConfig()->get('password'));
		$this->getFtpClient()->pasv((int) $this->getConfig()->get('pasv', 0) === 1);
	}

	/**
	 * Extract file information from an entry of FTP.
	 *
	 * @param \stdClass $fileEntry
	 * @param string    $path
	 *
	 * @return \stdClass
	 */
	private function getFileInfo(\stdClass $fileEntry, string $path): \stdClass
	{
		$file            = new \stdClass();
		$file->type      = $fileEntry->type == 'file' ? 'file' : 'dir';
		$file->name      = $fileEntry->name;
		$file->path      = rtrim($path, '/') . '/' . $fileEntry->name;
		$file->path      = substr_replace($file->path, '', 0, strlen(rtrim($this->getConfig()->get('root_folder', '/'), '/')));
		$file->size      = !empty($fileEntry->size) ? (int)$fileEntry->size : (!empty($fileEntry->sizd) ? (int)$fileEntry->sizd : 0);
		$file->width     = 0;
		$file->height    = 0;
		$file->extension = '';
		$file->mime_type = '';

		if ($file->type == 'file') {
			$file->extension = pathinfo($file->name, PATHINFO_EXTENSION);
			$file->mime_type = $this->mimeTypeMapping->getMimetype($file->extension);
		}

		$createDate = $this->getDate(!empty($fileEntry->modify) ? \DateTime::createFromFormat('YmdHis', $fileEntry->modify)->format('c') : null);
		$updateDate = clone $createDate;

		$file->create_date_formatted   = HTMLHelper::_('date', $createDate, $this->app->getLanguage()->_('DATE_FORMAT_LC5'));
		$file->create_date             = $createDate->format('c');
		$file->modified_date_formatted = HTMLHelper::_('date', $updateDate, $this->app->getLanguage()->_('DATE_FORMAT_LC5'));
		$file->modified_date           = $updateDate->format('c');

		$file->thumb_path = $this->generateThumb($file, $this->getConfig());

		return $file;
	}

	protected function getContent(\stdclass $file, Registry $config): string
	{
		$handle = fopen('php://temp', 'w+');
		$this->getFtpClient()->fget($handle, $this->getPath($file->path), FTP_BINARY, 0);
		rewind($handle);
		return stream_get_contents($handle);
	}
}
