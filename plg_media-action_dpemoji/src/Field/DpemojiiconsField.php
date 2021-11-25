<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\MediaAction\DPEmoji\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Layout\LayoutHelper;

class DpemojiiconsField extends FormField
{
	protected $type = 'Dpemojiicons';

	public function getInput()
	{
		$path = JPATH_PLUGINS . '/media-action/dpemoji/resources/node_modules/unicode-emoji-json/data-by-group.json';
		if (!file_exists($path)) {
			$path = JPATH_ROOT . '/media/plg_media-action_dpemoji/js/emoji.json';
		}

		return LayoutHelper::render(
			'icons',
			['groups' => json_decode(file_get_contents($path))],
			JPATH_PLUGINS . '/media-action/dpemoji/layouts'
		);
	}
}
