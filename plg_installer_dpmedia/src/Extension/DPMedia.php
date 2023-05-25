<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Installer\DPMedia\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

class DPMedia extends CMSPlugin
{
	/** @var CMSApplication $app */
	protected $app;

	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		if (strpos($url, '/download/dpmedia/') === false) {
			return;
		}

		$model = $this->app->bootComponent('com_installer')->getMVCFactory()->createModel('Updatesites', 'Administrator', ['ignore_request' => true]);
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
