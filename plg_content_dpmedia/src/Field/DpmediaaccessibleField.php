<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\AccessiblemediaField;
use Joomla\CMS\Form\Form;

class DpmediaaccessibleField extends AccessiblemediaField
{
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		if ($value && is_string($value) && strpos($value, '{') !== 0) {
			$value = ['imagefile' => $value];
		}

		return parent::setup($element, $value, $group);
	}

	protected function loadSubFormData(Form $subForm)
	{
		$data = parent::loadSubFormData($subForm);

		$args = html_entity_decode($this->directory);
		$subForm->setFieldAttribute('imagefile', 'asset_id', substr($args, strpos($args, '&')));
		$subForm->setFieldAttribute('imagefile', 'types', $this->element['types'] ?? 'images');

		return $data;
	}
}
