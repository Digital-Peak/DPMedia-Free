<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
	protected string $context;
	protected string $adapter;

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

	private function loadFields(Form $form, array $fields): void
	{
		parse_str(htmlspecialchars_decode($this->context), $data);

		$context      = $data['context'];
		$customFields = empty($context) ? [] : FieldsHelper::getFields(is_array($context) ? implode('', $context) : $context, null, false, null, true);

		// The removed fields
		$removedFieldTitles = [];

		// Loop over the field
		foreach ($fields as $field) {
			// Check if it is a media field
			if ($field->type !== 'Media' && $field->type !== 'Accessiblemedia') {
				continue;
			}

			foreach ($customFields as $customField) {
				if ($customField->label !== (string)$field->element->attributes()->label || $customField->fieldparams->get('dpmedia_accessible', 1)) {
					continue;
				}
				$form->setFieldAttribute($field->fieldname, 'type', 'dpmedia', $field->group);
			}

			// Get the directory
			$directory = $field->directory;
			if (!str_contains((string)$directory, 'context')) {
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
				if (str_starts_with($directory, 'dprestricted')) {
					$directory .= '&amp;force=1';
				}
				$directory .= $this->context;

				// Disable fields when no id is available
				if (empty($data['item']) && str_starts_with($directory, 'dprestricted')) {
					$form->removeField($field->fieldname, $field->group);
					$removedFieldTitles[] = $field->title;

					continue;
				}
			}

			// Transform the field when is accessible media
			if (strtolower((string)$form->getFieldAttribute($field->fieldname, 'type', '', $field->group)) === 'accessiblemedia') {
				$form->setFieldAttribute($field->fieldname, 'type', 'dpmediaaccessible', $field->group);
			}
			$form->setFieldAttribute($field->fieldname, 'directory', $directory, $field->group);
			$field->__set('directory', $directory);

			// For direct media set also the asset
			$args = html_entity_decode((string)$directory);
			$form->setFieldAttribute($field->fieldname, 'asset_field', 'none', $field->group);
			$form->setFieldAttribute($field->fieldname, 'asset_id', substr($args, strpos($args, '&') ?: 0), $field->group);
		}
		// Only print warning on GET requests
		if ($removedFieldTitles === [] || $_SERVER['REQUEST_METHOD'] !== 'GET') {
			return;
		}
		Factory::getApplication()->enqueueMessage(Text::sprintf('PLG_CONTENT_DPMEDIA_FIELD_REMOVED_MESSAGE', implode(', ', $removedFieldTitles)), 'warning');
	}
}
