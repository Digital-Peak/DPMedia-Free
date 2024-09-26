<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;

class AdaptersField extends ListField
{
	use ProviderManagerHelperTrait;

	protected function getOptions()
	{
		$options = parent::getOptions();

		foreach ($this->getProviderManager()->getProviders() as $provider) {
			foreach ($provider->getAdapters() as $adapter) {
				$options[] = (object)[
					'text'  => $adapter->getAdapterName() . ' [' . $provider->getDisplayName() . ']',
					'value' => $provider->getID() . '-' . $adapter->getAdapterName()
				];
			}
		}

		return $options;
	}
}
