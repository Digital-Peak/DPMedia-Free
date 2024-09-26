<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\AccessiblemediaField;
use Joomla\CMS\Form\Form;

class DpmediaaccessibleField extends AccessiblemediaField
{
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		if ($value && \is_string($value) && !str_starts_with($value, '{')) {
			$value = ['imagefile' => $value];
		}

		$return = parent::setup($element, $value, $group);

		$imageFile = $this->value->imagefile ?? ($this->value['imagefile'] ?? '');

		// Encode the adapter
		if (strpos((string)$imageFile, 'joomlaImage://')) {
			$imageFile = preg_replace_callback(
				'/joomlaImage:\/\/([^\/]+)/',
				static function (array $matches): string {
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
				(string)$imageFile
			);

			if (\is_object($this->value)) {
				// @phpstan-ignore-next-line
				$this->value->imagefile = $imageFile;
			}

			if (\is_array($this->value)) {
				$this->value['imagefile'] = $imageFile;
			}
		}

		return $return;
	}

	protected function loadSubFormData(Form $subForm)
	{
		$data = parent::loadSubFormData($subForm);

		$args = html_entity_decode($this->directory);
		$subForm->setFieldAttribute('imagefile', 'asset_id', substr($args, strpos($args, '&') ?: 0));
		$subForm->setFieldAttribute('imagefile', 'types', $this->element['types'] ?? 'images');

		return $data;
	}
}
