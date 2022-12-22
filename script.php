<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class Pkg_DPMediaInstallerScript extends InstallerScript
{
	protected $minimumPhp      = '7.4.0';
	protected $minimumJoomla   = '4.1.0';
	protected $allowDowngrades = true;

	public function postflight($type, $parent)
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
	}

	private function run($query)
	{
		try {
			$db = Factory::getDBO();
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}
}
