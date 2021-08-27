<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

defined('_JEXEC') or die;

use DigitalPeak\ThinHTTP;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\Component\Media\Administrator\Adapter\AdapterInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Read only adapter for Joomla 4 media manager.
 */
abstract class Adapter implements AdapterInterface
{
	protected $params;
	protected $mimeTypeMapping;
	protected $http;
	protected $db;
	protected $app;
	protected $name;
	protected $supportedThumbnailImageFormats = ['jpg', 'jpeg', 'png', 'tiff', 'tif', 'gif', 'bmp'];
	protected $useLastPathSegment             = true;

	public function __construct(Registry $params, ThinHTTP $http, MimeTypeMapping $mimeTypeMapping, DatabaseInterface $db, CMSApplication $app)
	{
		$this->params          = $params;
		$this->http            = $http;
		$this->mimeTypeMapping = $mimeTypeMapping;
		$this->db              = $db;
		$this->app             = $app;

		$this->name = strtolower((new \ReflectionClass($this))->getShortName());
		$this->name = str_replace('adapter', '', $this->name);
		$this->name = str_replace('writable', '', $this->name);
	}

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
		return $this->fetchFile($path);
	}

	public function getFiles(string $path = '/'): array
	{
		return $this->fetchFiles($path);
	}

	public function getResource(string $path)
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

	public function updateFile(string $name, string $path, $data)
	{
		throw new \Exception('Not implemented, please get the pro version.');
	}

	public function delete(string $path)
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

	public function getUrl(string $path): string
	{
		$file = $this->getFile($path);

		return $file && $file->url ? $file->url : '';
	}

	public function getAdapterName(): string
	{
		return $this->params->get('display_name');
	}

	public function search(string $path, string $needle, bool $recursive = false): array
	{
		return [];
	}

	/**
	 * Returns the id for the given path, basically it strips the last segment.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function getId(string $path): string
	{
		$id = basename($path);
		if (strpos($id, '.')) {
			$id = pathinfo($id, PATHINFO_FILENAME);
		}

		return $id;
	}

	/**
	 * Returns the real path for the given path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function getPath(string $path): string
	{
		// Append the root folder when in root
		if (!$path || $path == '/' || !$this->useLastPathSegment) {
			$path = rtrim($this->params->get('root_folder', '/'), '/') . '/' . $path;
		}

		// Replace last /
		$path = rtrim($path, '/');

		// Normalize
		$path = $path ? Path::clean($path, '/') : $path;

		return $path;
	}

	/**
	 * Returns a date object for the given date string respecting the global or user timezone.
	 *
	 * @params string $date
	 *
	 * @return Date
	 */
	protected function getDate(string $date = null): Date
	{
		$dateObj = Factory::getDate($date);

		$timezone = $this->app->get('offset');
		$user     = $this->app->getIdentity();

		if ($user->id) {
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
	 * Updates the internal params. Can be used when an access token has changed.
	 *
	 * @param string $compareKey
	 */
	protected function updateParams($compareKey = 'refresh_token')
	{
		$plugin = \Joomla\CMS\Plugin\PluginHelper::getPlugin('filesystem', 'dp' . $this->name);
		if (!$plugin) {
			return null;
		}

		$pluginParams = new Registry($plugin->params);
		$folders      = $pluginParams->get('folders');
		foreach ($folders as $key => $folder) {
			if ($folder->$compareKey != $this->params->get($compareKey)) {
				continue;
			}

			$folders->$key = $this->params->toObject();
		}

		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__extensions'))
			->set($this->db->quoteName('params') . '=' . $this->db->quote($pluginParams->toString()))
			->where($this->db->quoteName('element') . '=' . $this->db->quote('dp' . $this->name))
			->where($this->db->quoteName('type') . '=' . $this->db->quote('plugin'));
		$this->db->setQuery($query);
		$this->db->execute();
	}
}
