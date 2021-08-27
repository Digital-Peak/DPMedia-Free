<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Event\DispatcherInterface;

class Pkg_DPMediaInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
	protected $minimumPhp      = '7.4.0';
	protected $minimumJoomla   = '3.99.99';
	protected $allowDowngrades = true;

	public function preflight($type, $parent)
	{
		if (!parent::preflight($type, $parent)) {
			return false;
		}

		// Delete existing update sites, necessary if upgrading eg. free to pro
		$this->run(
			"delete from #__update_sites_extensions where extension_id in (select extension_id from #__extensions where element = 'pkg_dpmedia')"
		);
		$this->run("delete from #__update_sites where name like 'DPMedia%'");

		return true;
	}

	public function postflight($type, $parent)
	{
		// Perform some post install tasks
		if ($type == 'install') {
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element like 'dp%' and folder like 'media-action'");
		}

		if ($type == 'update') {
			$updater = function ($event) {
				if ($event->getArgument('installer')->getManifest()->packagename != 'dpmedia') {
					return;
				}

				$this->refreshUpdateSite();
			};

			// Update sites are created in a plugin
			Factory::getContainer()->get(DispatcherInterface::class)->addListener('onExtensionAfterInstall', $updater);
			Factory::getContainer()->get(DispatcherInterface::class)->addListener('onExtensionAfterUpdate', $updater);
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

	private function refreshUpdateSite()
	{
		$params = ComponentHelper::getParams('pkg_dpmedia');

		$dlid = trim($params->get('downloadid', ''));
		if (!$dlid) {
			return;
		}

		// If I have a valid Download ID I will need to use a non-blank extra_query in Joomla! 3.2+
		$extraQuery = null;
		if (preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid)) {
			$extraQuery = 'dlid=' . $dlid;
		}

		// Create the update site definition we want to store to the database
		$updateSite = ['enabled' => 1, 'last_check_timestamp' => 0, 'extra_query' => $extraQuery];

		$db = Factory::getDBO();

		// Get the extension ID to ourselves
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('package'))
			->where($db->qn('element') . ' = ' . $db->q('pkg_dpmedia'));
		$db->setQuery($query);

		$extensionId = $db->loadResult();
		if (empty($extensionId)) {
			return;
		}

		// Get the update sites for our extension
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extensionId));
		$db->setQuery($query);

		$updateSiteIDs = $db->loadColumn(0);
		if (!count($updateSiteIDs)) {
			return;
		}

		// Loop through all update sites
		foreach ($updateSiteIDs as $id) {
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__update_sites'))
				->where($db->qn('update_site_id') . ' = ' . $db->q($id));
			$db->setQuery($query);

			$site = $db->loadObject();
			if ($site->extra_query == $updateSite['extra_query']) {
				continue;
			}

			$updateSite['update_site_id'] = $id;
			$newSite                      = (object)$updateSite;
			$db->updateObject('#__update_sites', $newSite, 'update_site_id', true);
		}
	}
}
