<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\Registry\Registry;

/**
 * Caching support trait for media adapters.
 */
trait CacheTrait
{
	/** @var CacheControllerFactoryInterface $cacheFactory */
	private $cacheFactory;

	/**
	 * Fetch the file for the given path.
	 */
	abstract protected function fetchFile(string $path = '/'): \stdClass;

	/**
	 * Fetch the files for the given path.
	 */
	abstract protected function fetchFiles(string $path = '/'): array;

	/**
	 * Fetch the files for the given path.
	 */
	abstract protected function fetchSearch(string $path, string $needle, bool $recursive = false): array;

	/**
	 * Returns the config for the caching functionality.
	 */
	abstract protected function getConfig(): Registry;

	public function getFile(string $path = '/'): \stdClass
	{
		$cache = $this->cacheFactory->createCacheController('output', ['defaultgroup' => 'plg_filesystem_dp' . $this->name]);
		$cache->setCaching($this->getConfig()->get('cache', 1) == 1);
		$cache->setLifeTime($this->getConfig()->get('cache_time', 900) / 60);

		$cacheId = 'file-' . $path;

		if ($cache->contains($cacheId)) {
			$cache->gc();
			return $cache->get($cacheId);
		}

		$file = $this->fetchFile($path);

		$cache->store($file, $cacheId);

		return $file;
	}

	public function getFiles(string $path = '/'): array
	{
		$cache = $this->cacheFactory->createCacheController('output', ['defaultgroup' => 'plg_filesystem_dp' . $this->name]);
		$cache->setCaching($this->getConfig()->get('cache', 1) == 1);
		$cache->setLifeTime($this->getConfig()->get('cache_time', 900) / 60);

		$cacheId = 'files-' . $path;

		if ($cache->contains($cacheId)) {
			$cache->gc();
			return $cache->get($cacheId);
		}

		$files = $this->fetchFiles($path);

		$cache->store($files, $cacheId);

		return $files;
	}

	public function search(string $path, string $needle, bool $recursive = false): array
	{
		$cache = $this->cacheFactory->createCacheController('output', ['defaultgroup' => 'plg_filesystem_dp' . $this->name]);
		$cache->setCaching($this->getConfig()->get('cache', 1) == 1);
		$cache->setLifeTime($this->getConfig()->get('cache_time', 900) / 60);

		$cacheId = 'search-' . $path . '-' . md5($needle);

		if ($cache->contains($cacheId)) {
			$cache->gc();
			return $cache->get($cacheId);
		}

		$files = $this->fetchSearch($path, $needle, $recursive);

		$cache->store($files, $cacheId);

		return $files;
	}

	/**
	 * Sets the internal cache factory.
	 */
	public function setCacheFactory(CacheControllerFactoryInterface $cacheFactory): void
	{
		$this->cacheFactory = $cacheFactory;
	}

	/**
	 * Clears the cache for the give path.
	 */
	protected function clearCache(string $path): void
	{
		$cache = $this->cacheFactory->createCacheController('output', ['defaultgroup' => 'plg_filesystem_dp' . $this->name]);
		$cache->remove('file-' . $path);
		$cache->remove('files-' . $path);

		// Also remove parent directory
		if ($path && $path !== '/') {
			$cache->remove('file-' . \dirname($path));
			$cache->remove('files-' . \dirname($path));
		}
	}
}
