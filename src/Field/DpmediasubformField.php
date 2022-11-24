<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class DpmediasubformField extends SubformField
{
	protected $context;
	protected $adapter;

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$success = parent::setup($element, $value, $group);

		$this->context = (string)$element['context'];
		$this->adapter = (string)$element['adapter'];

		return $success;
	}

	public function loadSubForm()
	{
		$form = parent::loadSubForm();

		$this->loadFields($form, $form->getFieldset());
		$this->loadFields($form, $form->getGroup(''));

		return $form;
	}

	protected function loadSubFormData(Form $subForm)
	{
		$forms = parent::loadSubFormData($subForm);

		foreach ($forms as $form) {
			$this->loadFields($form, $form->getFieldset());
			$this->loadFields($form, $form->getGroup(''));
		}

		return $forms;
	}

	private function loadFields(Form $form, array $fields)
	{
		parse_str(htmlspecialchars_decode($this->context), $data);
		$customFields = !empty($data['context']) ? FieldsHelper::getFields($data['context'], null, false, null, true) : [];

		// Loop over the field
		foreach ($fields as $field) {
			// Check if it is a media field
			if ($field->__get('type') !== 'Media' && $field->__get('type') !== 'Accessiblemedia') {
				continue;
			}

			foreach ($customFields as $customField) {
				if ($customField->label !== (string)$field->element->attributes()->label || $customField->fieldparams->get('dpmedia_accessible', 1)) {
					continue;
				}

				$form->setFieldAttribute($field->__get('fieldname'), 'type', 'dpmedia', $field->__get('group'));
			}

			// Get the directory
			$directory = $field->__get('directory');
			if (strpos($directory, 'context') === false) {
				// Banners has a hardcoded directory
				if ($directory === 'banners') {
					$directory = '';
				}

				// When none is set, use the adapter
				if (empty($directory) && $this->adapter) {
					$directory = $this->adapter;
				}

				// When no directory, we leave the field as it is
				if (!$directory) {
					continue;
				}

				$directory .= ':/';

				// Only show the restricted adapter when is selected
				if (strpos($directory, 'dprestricted') === 0) {
					$directory .= '&amp;force=1';
				}
				$directory .= $this->context;

				// Disable fields when no id is available
				if (empty($data['item']) && strpos($directory, 'dprestricted') === 0) {
					// Only print warning on GET requests
					if ($_SERVER['REQUEST_METHOD'] === 'GET') {
						Factory::getApplication()->enqueueMessage(Text::sprintf('PLG_CONTENT_DPMEDIA_FIELD_REMOVED_MESSAGE', $field->__get('title')), 'warning');
					}
					$form->removeField($field->__get('fieldname'), $field->__get('group'));
					continue;
				}
			}

			// Transform the field when is accessible media
			if (strtolower($form->getFieldAttribute($field->__get('fieldname'), 'type', '', $field->__get('group'))) === 'accessiblemedia') {
				$form->setFieldAttribute($field->__get('fieldname'), 'type', 'dpmediaaccessible', $field->__get('group'));
			}
			$form->setFieldAttribute($field->__get('fieldname'), 'directory', $directory, $field->__get('group'));
			$field->__set('directory', $directory);

			// For direct media set also the asset
			$args = html_entity_decode($directory);
			$form->setFieldAttribute($field->__get('fieldname'), 'asset_field', 'none', $field->__get('group'));
			$form->setFieldAttribute($field->__get('fieldname'), 'asset_id', substr($args, strpos($args, '&')), $field->__get('group'));
		}
	}
}
