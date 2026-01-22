<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;

return new class () implements InstallerScriptInterface, DatabaseAwareInterface {
	use DatabaseAwareTrait;

	private string $minimumPhp = '8.1.0';

	private string $minimumJoomla = '4.4.0';

	public function install(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function update(InstallerAdapter $adapter): bool
	{

		$file = $adapter->getParent()->getPath('source') . '/deleted.php';
		if (file_exists($file)) {
			require $file;
		}

		$path    = JPATH_ADMINISTRATOR . '/manifests/packages/pkg_dpmedia.xml';
		$version = null;

		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = $manifest instanceof SimpleXMLElement ? (string)$manifest->version : null;
		}

		if (\in_array($version, [null, '', '0', 'DP_DEPLOY_VERSION'], true)) {
			return true;
		}

		if (version_compare($version, '1.10.0') === -1) {
			$this->run("update #__extensions set package_id = 0
			where package_id = (select * from (select extension_id from #__extensions where element ='pkg_dpmedia') as e)
			and name not in ('lib_dpmedia', 'plg_content_dpmedia', 'plg_installer_dpmedia', 'plg_user_dpmedia')");
		}

		if (version_compare($version, '1.14.0') === -1) {
			$this->run(
				"UPDATE `#__update_sites` SET location=replace(location,'&ext=extension.xml','') where location like 'https://joomla.digital-peak.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=%'"
			);
			$this->run(
				"UPDATE `#__update_sites` SET location=replace(location,'https://joomla.digital-peak.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=','https://cdn.digital-peak.com/update/stream.php?id=') where location like 'https://joomla.digital-peak.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=%'"
			);
		}

		return true;
	}

	public function uninstall(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
			Log::add(\sprintf(Text::_('JLIB_INSTALLER_MINIMUM_PHP'), $this->minimumPhp), Log::WARNING, 'jerror');

			return false;
		}

		if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
			Log::add(\sprintf(Text::_('JLIB_INSTALLER_MINIMUM_JOOMLA'), $this->minimumJoomla), Log::WARNING, 'jerror');

			return false;
		}

		return true;
	}

	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		// Perform some post install tasks
		if ($type === 'install' || $type === 'discover_install') {
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element like 'dp%' and folder like 'media-action'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_filesystem_dprestricted'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_content_dpmedia'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_user_dpmedia'");
		}

		// Make sure the installer plugin is enabled
		$this->run("update `#__extensions` set enabled = 1 where name = 'plg_installer_dpmedia'");
		// Ensure DPMedia update sites are enabled
		$this->run("update `#__update_sites` set enabled = 1 where name like '%DPMedia%'");

		return true;
	}

	private function run(string $query): void
	{
		try {
			$db = $this->getDatabase();
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $exception) {
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'error');
		}
	}
};
