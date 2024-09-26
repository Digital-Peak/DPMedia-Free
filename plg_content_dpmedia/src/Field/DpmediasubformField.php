<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Form\Form;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class DpmediasubformField extends SubformField
{
	protected string $context;

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$success = parent::setup($element, $value, $group);

		$this->context = (string)$element['context'];

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

	protected function loadFields(Form $form, array $fields): void
	{
		$customFields = FieldsHelper::getFields($this->context, null, false, null, true);

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

			// Transform the field when is accessible media
			if (strtolower((string)$form->getFieldAttribute($field->fieldname, 'type', '', $field->group)) === 'accessiblemedia') {
				$form->setFieldAttribute($field->fieldname, 'type', 'dpmediaaccessible', $field->group);
			}
		}
	}
}
