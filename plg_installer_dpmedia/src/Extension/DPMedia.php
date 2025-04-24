<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Installer\DPMedia\Extension;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Event\Installer\BeforeUpdateSiteDownloadEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;

class DPMedia extends CMSPlugin
{
	use DatabaseAwareTrait;

	public function onInstallerBeforeUpdateSiteDownload(BeforeUpdateSiteDownloadEvent $event): void
	{
		$url = $event->getUrl();
		if ($url !== '' || !str_contains($url, 'digital-peak.com')) {
			return;
		}

		$query = $this->getDatabase()->getQuery(true);
		$query->select('name')->from('#__update_sites');
		$query->where('location = :location')->bind(':location', $url);

		$this->getDatabase()->setQuery($query);
		if (!str_contains((string)$this->getDatabase()->loadResult(), 'DPMedia')) {
			return;
		}

		$uri = Uri::getInstance($url);
		$uri->setVar('j', JVERSION);
		$uri->setVar('p', phpversion());
		$uri->setVar('m', $this->getDatabase()->getVersion());

		$path = JPATH_LIBRARIES . '/lib_dpmedia/lib_dpmedia.xml';
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$uri->setVar('v', $manifest instanceof \SimpleXMLElement ? (string)$manifest->version : '');
		}

		if ($uri->getVar('v') === 'DP_DEPLOY_VERSION') {
			return;
		}

		$event->updateUrl($uri->toString());
	}

	public function onInstallerBeforePackageDownload(string &$url, array &$headers): void
	{
		if (!str_contains($url, '/download/dpmedia/')) {
			return;
		}

		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return;
		}

		$model = $app->bootComponent('com_installer')->getMVCFactory()->createModel('Updatesites', 'Administrator', ['ignore_request' => true]);
		$model->setState('filter.search', 'DPMedia Core');
		$model->setState('filter.enabled', 1);
		$model->setState('list.start', 0);
		$model->setState('list.limit', 1);

		$updateSite = $model->getItems();

		// Check if there is a download ID
		if (empty($updateSite) || empty($updateSite[0]->downloadKey) || empty($updateSite[0]->downloadKey['value'])) {
			return;
		}

		$uri = Uri::getInstance($url);
		$uri->setVar('dlid', $updateSite[0]->downloadKey['value']);

		$url = $uri->toString();
	}
}
