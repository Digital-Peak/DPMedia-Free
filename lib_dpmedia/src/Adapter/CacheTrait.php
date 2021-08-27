<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

defined('_JEXEC') or die;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;

/**
 * Caching support trait for media adapters.
 */
trait CacheTrait
{
	/** @var CacheControllerFactoryInterface $cacheFactory */
	private $cacheFactory;

	/**
	 * Fetch the file for the given path.
	 *
	 * @param string $path
	 *
	 * @return \stdClass
	 */
	abstract protected function fetchFile(string $path = '/'): \stdClass;

	/**
	 * Fetch the files for the given path.
	 *
	 * @param string $path
	 *
	 * @return array
	 */
	abstract protected function fetchFiles(string $path = '/'): array;

	public function getFile(string $path = '/'): \stdClass
	{
		$cache = $this->cacheFactory->createCacheController('output', ['defaultgroup' => 'plg_filesystem_dp' . $this->name]);
		$cache->setCaching($this->params->get('cache', 1) == 1);
		$cache->setLifeTime($this->params->get('cache_time', 900) / 60);

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
		$cache->setCaching($this->params->get('cache', 1) == 1);
		$cache->setLifeTime($this->params->get('cache_time', 900) / 60);

		$cacheId = 'files-' . $path;

		if ($cache->contains($cacheId)) {
			$cache->gc();
			return $cache->get($cacheId);
		}

		$files = $this->fetchFiles($path);

		$cache->store($files, $cacheId);

		return $files;
	}

	/**
	 * Sets the internal cache factory.
	 *
	 * @param CacheControllerFactoryInterface $cacheFactory
	 */
	public function setCacheFactory(CacheControllerFactoryInterface $cacheFactory)
	{
		$this->cacheFactory = $cacheFactory;
	}

	/**
	 * Clears the cache for the give path.
	 *
	 * @param string $path
	 */
	protected function clearCache(string $path)
	{
		$cache = $this->cacheFactory->createCacheController('output', ['defaultgroup' => 'plg_filesystem_dp' . $this->name]);
		$cache->remove('file-' . $path);
		$cache->remove('files-' . $path);

		// Also remove parent directory
		if ($path && $path != '/') {
			$cache->remove('file-' . dirname($path));
			$cache->remove('files-' . dirname($path));
		}
	}
}
