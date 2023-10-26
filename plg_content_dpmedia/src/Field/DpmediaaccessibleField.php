<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\AccessiblemediaField;
use Joomla\CMS\Form\Form;

class DpmediaaccessibleField extends AccessiblemediaField
{
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		if ($value && is_string($value) && strpos($value, '{') !== 0) {
			$value = ['imagefile' => $value];
		}

		$return = parent::setup($element, $value, $group);

		// Encode the adapter
		if (strpos($this->value['imagefile'] ?? '', 'joomlaImage://')) {
			$this->value['imagefile'] = preg_replace_callback(
				'/joomlaImage:\/\/([^\/]+)/',
				function ($matches) {
					if (!$matches) {
						return;
					}

					$adapter = $matches[1];

					/**
					 * There is a double encoding needed here for the front end, otherwise the media field is rendering &
					 * characters on the front encoded which count as new arg as part of the route call.
					 * https://github.com/joomla/joomla-cms/blob/4.4-dev/layouts/joomla/form/field/media.php#L115
					 * `$url = Route::_($url);`
					 */
					if (Factory::getApplication()->isClient('site')) {
						$adapter = urlencode($adapter);
					}

					return 'joomlaImage://' . urlencode($adapter);
				},
				$this->value['imagefile']
			);
		}

		return $return;
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
