<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;
use Psr\Container\ContainerInterface;

class DPMedia extends CMSPlugin implements BootableExtensionInterface
{
	use ProviderManagerHelperTrait;

	protected $autoloadLanguage = true;

	public function boot(ContainerInterface $container): void
	{
		// The restricted plugin must be loaded here too to register to the content events
		PluginHelper::importPlugin('filesystem', 'dprestricted');
	}

	public function onContentPrepareForm(Form $form, mixed $data): void
	{
		// Compile the real context
		$context = FieldsHelper::extract($form->getName());
		if (count($context ?? []) < 2) {
			return;
		}

		$context = implode('.', $context);

		// When custom field form load the own preferences
		if (str_starts_with($context, 'com_fields.field') && !empty($data->type) && $data->type === 'media') {
			$form->loadFile(JPATH_PLUGINS . '/content/dpmedia/params/media-field.xml');
			return;
		}

		// Get the custom fields
		$customFields = FieldsHelper::getFields($context, is_array($data) || $data instanceof \stdClass ? $data : null);

		// Loop over the field
		foreach ($form->getFieldset() as $field) {
			// Check if it is a subform field
			if ($field->type === 'Subform') {
				// Make sure the prefix exists
				FormHelper::addFieldPrefix('DigitalPeak\Plugin\Content\DPMedia\Field');

				// Transform the field into a dp one
				$name = substr((string)$field->name, strpos((string)$field->name, $field->group . '][') + strlen((string)$field->group));
				$name = trim($name, '[]');
				$form->setFieldAttribute($name, 'type', 'dpmediasubform', $field->group);
				$form->setFieldAttribute($name, 'context', $context, $field->group);

				// Process the next field
				continue;
			}

			if ($field->type !== 'Accessiblemedia' || $field->group !== 'com_fields') {
				continue;
			}

			// Loop over the fields
			foreach ($customFields as $customField) {
				// Ensure we process only fields from the current form
				if ($customField->name !== $field->fieldname) {
					continue;
				}

				// Ensure the fields can be found
				FormHelper::addFieldPrefix('DigitalPeak\\Plugin\\Content\\DPMedia\\Field');

				// Set the DPMedia attribute field to ensure the types are set
				$form->setFieldAttribute(
					$field->fieldname,
					'type',
					'dpmedia' . ($customField->fieldparams->get('dpmedia_accessible', 1) ? 'accessible' : ''),
					$field->group
				);
			}
		}
	}
}
