<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPFtp\Adapter;

use DigitalPeak\Library\DPMedia\Adapter\CacheFactoryAwareInterface;
use DigitalPeak\Library\DPMedia\Adapter\CacheTrait;
use DigitalPeak\Library\DPMedia\Adapter\StreamSupportTrait;
use Joomla\CMS\Uri\Uri;

/**
 * Read and write FTP adapter for Joomla 4 media manager.
 */
class FtpAdapterWritable extends FtpAdapter implements CacheFactoryAwareInterface
{
	use CacheTrait;
	use StreamSupportTrait;

	public function getResource(string $path)
	{
		$handle = fopen('php://temp', 'w+');
		$this->getFtpClient()->fget($handle, $this->getPath($path), FTP_BINARY, 0);
		rewind($handle);
		return $handle;
	}

	public function createFolder(string $name, string $path): string
	{
		$this->connect();

		$newPath = $this->getFtpClient()->mkdir($this->getPath($path) . '/' . $name);
		if ($newPath === false) {
			throw new \Exception(error_get_last() ? reset(error_get_last() ? error_get_last()['message'] : 'Error') : 'Error');
		}

		$this->clearCache($path);

		return basename($newPath);
	}

	public function createFile(string $name, string $path, $data): string
	{
		$this->updateFile($name, $path, $data);

		$this->clearCache($path);

		return $name;
	}

	public function updateFile(string $name, string $path, $data)
	{
		$this->connect();

		$this->getFtpClient()->putFromString($this->getPath($path) . '/' . $name, $data);

		$this->clearCache($path);
	}

	public function delete(string $path)
	{
		$this->connect();

		$success = $this->getFtpClient()->remove($this->getPath($path), true);
		if (!$success) {
			throw new \Exception(error_get_last() ? error_get_last()['message'] : 'Error');
		}

		$this->clearCache($path);
	}

	public function move(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		$this->connect();

		$success = $this->getFtpClient()->rename($this->getPath($sourcePath), $this->getPath($destinationPath));
		if ($success === false) {
			throw new \Exception(error_get_last() ? error_get_last()['message'] : 'Error');
		}

		$this->clearCache($sourcePath);

		return $destinationPath;
	}

	public function copy(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		throw new \Exception('Not possible on FTP!');
	}
}
