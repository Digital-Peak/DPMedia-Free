<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;

class Pkg_DPMediaInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
	protected $minimumPhp      = '7.4.0';
	protected $minimumJoomla   = '4.1.0';
	protected $allowDowngrades = true;

	public function preflight($type, $parent)
	{
		if ($parent->getElement() != 'pkg_dpmedia') {
			return;
		}

		if (!parent::preflight($type, $parent)) {
			return false;
		}

		// Delete existing update sites, necessary if upgrading eg. free to pro
		$this->run(
			"delete from #__update_sites_extensions where extension_id in (select extension_id from #__extensions where element = 'pkg_dpmedia')"
		);
		$this->run("delete from #__update_sites where name like 'DPMedia Premium%' or name like 'DPMedia Professional%' or name like 'DPMedia Standard%'");

		return true;
	}

	public function postflight($type, $parent)
	{
		// Perform some post install tasks
		if ($type == 'install' || $type == 'discover_install') {
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element like 'dp%' and folder like 'media-action'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_filesystem_dprestricted'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_content_dpmedia'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and name = 'plg_user_dpmedia'");
		}
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
