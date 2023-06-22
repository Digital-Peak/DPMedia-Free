<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

return new class () implements InstallerScriptInterface {
	private $minimumPhp    = '7.4.0';
	private $minimumJoomla = '4.2.0';

	public function install(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function update(InstallerAdapter $adapter): bool
	{
		$file = $adapter->getParent()->getPath('source') . '/deleted.php';
		if (!file_exists($file)) {
			return true;
		}

		require $file;

		return true;
	}

	public function uninstall(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
			Log::add(sprintf(Text::_('JLIB_INSTALLER_MINIMUM_PHP'), $this->minimumPhp), Log::WARNING, 'jerror');

			return false;
		}

		if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
			Log::add(sprintf(Text::_('JLIB_INSTALLER_MINIMUM_JOOMLA'), $this->minimumJoomla), Log::WARNING, 'jerror');

			return false;
		}

		return true;
	}

	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		// Perform some post install tasks
		if ($type == 'install' || $type == 'discover_install') {
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element like 'dp%' and folder like 'media-action'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_filesystem_dprestricted'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_content_dpmedia'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_user_dpmedia'");
		}

		// Make sure the installer plugin is enabled
		$this->run("update `#__extensions` set enabled = 1 where name = 'plg_installer_dpmedia'");

		return true;
	}

	private function run($query)
	{
		try {
			$db = Factory::getDbo();
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}
};
