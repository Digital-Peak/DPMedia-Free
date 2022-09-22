<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\MediaAction\DPFilter\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\Registry\Registry;

class DpfilterpresetsField extends ListField
{
	protected $type = 'Dpfilterpresets';

	public function getOptions()
	{
		$plugin = \Joomla\CMS\Plugin\PluginHelper::getPlugin('media-action', 'dpfilter');

		$params = new Registry($plugin->params);

		$options = [['text' => '', 'value' => '']];
		foreach ($params->get('presets', []) as $preset) {
			$options[] = ['text' => $preset->title, 'value' => json_encode($preset)];
		}
		return $options;
	}
}
