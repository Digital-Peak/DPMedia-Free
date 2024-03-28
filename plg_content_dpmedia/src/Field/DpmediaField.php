<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\MediaField;

class DpmediaField extends MediaField
{
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		if (is_string($value) && str_starts_with($value, '{')) {
			$value = json_decode($value);
		}

		if (is_object($value) && !empty($value->imagefile)) {
			$value = $value->imagefile;
		}

		if (!is_string($value)) {
			$value = '';
		}

		return parent::setup($element, $value, $group);
	}
}
