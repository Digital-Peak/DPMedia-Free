<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

use DigitalPeak\ThinHTTP\ClientInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Media\Administrator\Adapter\AdapterInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;

/**
 * Read only base adapter class for Joomla media manager.
 */
abstract class Adapter implements AdapterInterface
{
	protected string $name;
	protected bool $useLastPathSegment = true;

	public function __construct(
		private readonly Registry $config,
		protected ClientInterface $http,
		protected MimeTypeMapping $mimeTypeMapping,
		protected DatabaseInterface $db,
		protected CMSApplicationInterface $app
	) {
		$this->name = strtolower((new \ReflectionClass($this))->getShortName());
		$this->name = str_replace('adapter', '', $this->name);
		$this->name = str_replace('writable', '', $this->name);
	}

	/**
	 * Fetch the file for the given path.
	 */
	abstract protected function fetchFile(string $path = '/'): \stdClass;

	/**
	 * Fetch the files for the given path.
	 */
	abstract protected function fetchFiles(string $path = '/'): array;

	/**
	 * Fetch the files for the given path and search needle.
	 */
	abstract protected function fetchSearch(string $path, string $needle, bool $recursive = false): array;

	/**
	 * Fetch the url for the given path.
	 */
	protected function fetchUrl(string $path): string
	{
		return $this->getFile($path)->url ?? '';
	}

	public function getFile(string $path = '/'): \stdClass
	{
		return $this->fetchFile($path);
	}

	public function getFiles(string $path = '/'): array
	{
		return $this->fetchFiles($path);
	}

	public function getUrl(string $path): string
	{
		return $this->fetchUrl($path);
	}

	public function search(string $path, string $needle, bool $recursive = false): array
	{
		return $this->fetchSearch($path, $needle, $recursive);
	}

	public function getResource(string $path): mixed
	{
		throw new \Exception('Not implemented, please get the pro version.');
	}

	public function createFolder(string $name, string $path): string
	{
		throw new \Exception('Not implemented, please get the pro version.');
	}

	public function createFile(string $name, string $path, $data): string
	{
		throw new \Exception('Not implemented, please get the pro version.');
	}

	public function updateFile(string $name, string $path, $data): void
	{
		throw new \Exception('Not implemented, please get the pro version.');
	}

	public function delete(string $path): void
	{
		throw new \Exception('Not implemented, please get the pro version.');
	}

	public function move(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		throw new \Exception('Not implemented, please get the pro version.');
	}

	public function copy(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		throw new \Exception('Not implemented, please get the pro version.');
	}

	public function getAdapterName(): string
	{
		return $this->getConfig()->get('display_name');
	}

	/**
	 * Returns the id for the given path, basically it strips the last segment.
	 */
	protected function getPathId(string $path): string
	{
		$id = basename($path);
		if (strpos($id, '.')) {
			return pathinfo($id, PATHINFO_FILENAME);
		}

		return $id;
	}

	/**
	 * Returns the real path for the given path.
	 */
	protected function getPath(string $path): string
	{
		// Append the root folder when in root
		if ($path === '' || $path === '0' || $path === '/' || !$this->useLastPathSegment) {
			$path = rtrim((string)$this->getConfig()->get('root_folder', '/'), '/') . '/' . $path;
		}

		// Replace last /
		$path = rtrim($path, '/');

		// Normalize
		$path = $path !== '' && $path !== '0' ? Path::clean($path, '/') : $path;

		return $path;
	}

	/**
	 * Returns a date object for the given date string respecting the global or user timezone.
	 */
	protected function getDate(?string $date = null): Date
	{
		$dateObj = Factory::getDate($date !== null && $date !== '' && $date !== '0' ? $date : '');

		$timezone = $this->app->get('offset');
		$user     = $this->app->getIdentity();

		if ($user && $user->id !== 0) {
			$userTimezone = $user->getParam('timezone');

			if (!empty($userTimezone)) {
				$timezone = $userTimezone;
			}
		}

		if ($timezone) {
			$dateObj->setTimezone(new \DateTimeZone($timezone));
		}

		return $dateObj;
	}

	/**
	 * Returns the config.
	 */
	public function getConfig(): Registry
	{
		return $this->config;
	}

	/**
	 * Returns the name.
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Updates the internal params. Can be use D when an access token has changed.
	 */
	protected function updateParams(string $compareKey = 'refresh_token'): void
	{
		$plugin = PluginHelper::getPlugin('filesystem', 'dp' . $this->name);
		if (!$plugin) {
			return;
		}

		$pluginParams = new Registry($plugin->params);
		$folders      = $pluginParams->get('folders');
		foreach ($folders as $key => $folder) {
			if ($folder->$compareKey != $this->getConfig()->get($compareKey)) {
				continue;
			}

			$folders->$key = $this->getConfig()->toObject();
		}

		$query = $this->db->getQuery(true)
			->update('#__extensions')
			->set('params =' . $this->db->quote($pluginParams->toString()))
			->where('element =' . $this->db->quote('dp' . $this->name))
			->where('type =' . $this->db->quote('plugin'));
		$this->db->setQuery($query);
		$this->db->execute();
	}
}
