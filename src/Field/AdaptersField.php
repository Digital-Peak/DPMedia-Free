<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Media\Administrator\Event\MediaProviderEvent;
use Joomla\Component\Media\Administrator\Provider\ProviderManager;

class AdaptersField extends ListField
{
	protected function getOptions()
	{
		$options = parent::getOptions();

		$manager = new ProviderManager();

		// Fire the event to get the results
		$eventParameters = ['context' => 'AdapterManager', 'providerManager' => $manager];
		$event           = new MediaProviderEvent('onSetupProviders', $eventParameters);
		PluginHelper::importPlugin('filesystem');
		Factory::getApplication()->triggerEvent('onSetupProviders', $event);

		foreach ($manager->getProviders() as $provider) {
			foreach ($provider->getAdapters() as $adapter) {
				$options[] = [
					'text'  => $adapter->getAdapterName() . ' [' . $provider->getDisplayName() . ']',
					'value' => $provider->getID() . '-' . $adapter->getAdapterName()
				];
			}
		}

		return $options;
	}
}
