<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class DPMedia extends CMSPlugin
{
	/**
	 * The mapping between the context and the id attribute.
	 */
	private static array $ID_MAP = ['com_content.article' => 'a_id', 'com_plugins.plugin' => 'extension_id'];

	protected $autoloadLanguage = true;

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

		$customFields = FieldsHelper::getFields($context, is_array($data) || $data instanceof \stdClass ? $data : null);

		// Loop over the field
		foreach ($form->getFieldset() as $field) {
			// Check if it is a media field
			if ($field->type !== 'Accessiblemedia' || $field->group !== 'com_fields') {
				continue;
			}

			foreach ($customFields as $customField) {
				if ($customField->name !== $field->fieldname || $customField->fieldparams->get('dpmedia_accessible', 1)) {
					continue;
				}

				// Ensure the fields can be found
				FormHelper::addFieldPrefix('DigitalPeak\\Plugin\\Content\\DPMedia\\Field');
				$form->setFieldAttribute($field->fieldname, 'type', 'dpmedia', $field->group);
			}
		}

		$this->addRestrictedSupport($context, $form, $data ?: []);
	}

	/**
	 * Add support for restricted media fields.
	 */
	private function addRestrictedSupport(string $context, Form $form, mixed $data): void
	{
		$app = $this->getApplication();

		// Only work in web context
		if (!$app instanceof CMSWebApplicationInterface) {
			return;
		}

		// Ignore com_config
		if ($context === 'com_config.application') {
			return;
		}

		// Load data from input when not available
		if (!$data) {
			$data = $app->getInput()->get('jform', [], 'array');
		}

		// Ensure an object
		if (is_array($data)) {
			$data = (object)$data;
		}

		if (strpos($context, '.form') && $app->isClient('site') && !empty($data->typeAlias)) {
			$context = $data->typeAlias;
		}

		// Determine the id, on front end the components have special variables
		$id = empty($data->id) ? $app->getInput()->get('id', 0) : $data->id;
		if (empty($id) && !empty(self::$ID_MAP[$context]) && $app->getInput()->get(self::$ID_MAP[$context])) {
			$id = $app->getInput()->get(self::$ID_MAP[$context]);
		}

		// The root folder where the params are
		$root = JPATH_PLUGINS . '/content/dpmedia/params';

		// Inject into component config when possible
		if ($context === 'com_config.component' && file_exists($root . '/' . $app->getInput()->get('component') . '.xml')) {
			$form->loadFile($root . '/' . $app->getInput()->get('component') . '.xml');
			return;
		}

		if (str_starts_with((string) $context, 'com_categories.category')) {
			$form->loadFile($root . '/com_categories.xml');
		}

		// When not enough information, return
		if (str_starts_with((string) $context, '.') || !str_contains((string) $context, '.')) {
			return;
		}

		// Load the adapter from configuration
		$adapter = $this->params->get('adapter');

		// It can be that the component does not exist
		// @phpstan-ignore-next-line
		if ($componentParams = ComponentHelper::getParams(substr((string) $context, 0, strpos((string) $context, '.')))) {
			$adapter = $componentParams->get('dpmedia_adapter', $adapter);
		}

		// Load the adapter from the category
		if (($catId = $this->getCategoryId($data, $form)) !== 0) {
			[$component, $section] = explode('.', (string) $context);
			$componentInstance     = $app->bootComponent($component);
			if ($componentInstance instanceof CategoryServiceInterface) {
				$categoryInstance = $componentInstance->getCategory();

				if (($category = $categoryInstance->get($catId)) !== null) {
					$adapter = $category->getParams()->get('dpmedia_adapter', $adapter);
				}
			}
		}

		// Flag if the JS assets should be loaded when there is at least one media field in the form
		$found = false;

		// The removed fields
		$removedFieldTitles = [];

		// Loop over the field
		foreach ($form->getFieldset() as $field) {
			// Check if it is a subform field
			if ($field->type === 'Subform') {
				// Make sure the prefix exists
				FormHelper::addFieldPrefix('DigitalPeak\Plugin\Content\DPMedia\Field');

				// Transform the field into a dp one
				$name = substr($field->name, strpos($field->name, $field->group . '][') + strlen($field->group));
				$name = trim($name, '[]');
				$form->setFieldAttribute($name, 'type', 'dpmediasubform', $field->group);

				// Compile the context
				$subFormContext = '&amp;context=' . $context . '&amp;item=' . (empty($id) ? '' : $id);
				if (!empty($data->catid)) {
					$subFormContext .= '&amp;catid=' . $data->catid;
				}

				// Pass the required variables for the media fields in the subform field
				$form->setFieldAttribute($name, 'context', $subFormContext, $field->group);
				$form->setFieldAttribute($name, 'adapter', $adapter, $field->group);

				// Ensure the assets are loaded
				$found = true;

				// Process the next field
				continue;
			}

			// Check if it is a media field
			if ($field->type !== 'Media' && $field->type !== 'Accessiblemedia') {
				continue;
			}

			// Get the adapter
			$directory = $field->__get('directory');

			// Banners has a hardcoded directory
			if ($directory === 'banners') {
				$directory = '';
			}

			// When none is set, use the adapter
			if (empty($directory) && $adapter) {
				$directory = $adapter;
			}

			// When no directory, we leave the field as it is
			if (!$directory) {
				continue;
			}

			$directory = urlencode((string) $directory);

			/**
			 * There is a double encoding needed here for the front end, otherwise the media field is rendering &
			 * characters on the front encoded which count as new arg as part of the route call.
			 * https://github.com/joomla/joomla-cms/blob/4.4-dev/layouts/joomla/form/field/media.php#L115
			 * `$url = Route::_($url);`
			 */
			if ($app->isClient('site')) {
				$directory = urlencode($directory);
			}

			// Disable fields when no id is available
			if (empty($id) && str_starts_with($directory, 'dprestricted')) {
				$form->removeField($field->fieldname, $field->group);
				$removedFieldTitles[] = $field->__get('title');

				continue;
			}

			// Ensure assets are loaded
			$found = true;

			// Normalize the directory
			$directory .= ':/';

			// Only show the restricted adapter when is selected
			if (str_starts_with($directory, 'dprestricted')) {
				$directory .= '&amp;force=1';
			}

			// Get more variables for adapters
			$directory .= '&amp;context=' . $context . '&amp;item=' . $id;
			if (!empty($data->catid)) {
				$directory .= '&amp;catid=' . $data->catid;
			}

			// Transform the field when is accessible media
			if (strtolower((string) $form->getFieldAttribute($field->fieldname, 'type', '', $field->group)) === 'accessiblemedia') {
				// Make sure the prefix exists
				FormHelper::addFieldPrefix('DigitalPeak\Plugin\Content\DPMedia\Field');
				$form->setFieldAttribute($field->fieldname, 'type', 'dpmediaaccessible', $field->group);
			}
			$form->setFieldAttribute($field->fieldname, 'directory', $directory, $field->group);

			// For direct media set also the asset
			$args = html_entity_decode($directory);
			$form->setFieldAttribute($field->fieldname, 'asset_field', 'none', $field->group);
			$form->setFieldAttribute($field->fieldname, 'asset_id', substr($args, strpos($args, '&') ?: 0), $field->group);
		}

		// Only print warning on GET requests
		if ($removedFieldTitles && $_SERVER['REQUEST_METHOD'] === 'GET') {
			$app->enqueueMessage(Text::sprintf('PLG_CONTENT_DPMEDIA_FIELD_REMOVED_MESSAGE', implode(', ', $removedFieldTitles)), 'warning');
		}

		// When no media field is loaded, then do nothing more
		if (!$found) {
			return;
		}

		// Load the JS files
		HTMLHelper::_('behavior.core');
		HTMLHelper::_('script', 'plg_content_dpmedia/dpmedia.min.js', ['relative' => true, 'version' => 'auto']);

		// Set the path information
		$url = '&force=1&context=' . $context;
		if ($item = empty($id) ? null : $id) {
			$url .= '&item=' . $item;
		}

		if ($catid = empty($data->catid) ? null : $data->catid) {
			$url .= '&catid=' . $catid;
		}

		// Set the path on the JS store
		$app->getDocument()->addScriptOptions('DPMedia.cf.select', ['pathInformation' => $url, 'defaultAdapter' => $adapter]);
	}

	public function onBeforeCompileHead(): void
	{
		$app = $this->getApplication();

		// Only work in web context
		if (!$app instanceof CMSWebApplicationInterface) {
			return;
		}

		// Load the path information from the JS store
		$info = $app->getDocument()->getScriptOptions('DPMedia.cf.select');
		if (empty($info) || empty($info['pathInformation']) || empty($info['defaultAdapter'])) {
			return;
		}

		// Get the select config
		$config = $app->getDocument()->getScriptOptions('media-picker-api');
		if ($config && !empty($config['apiBaseUrl'])) {
			$config['apiBaseUrl'] .= $info['pathInformation'];

			// Save the config in JS store
			$app->getDocument()->addScriptOptions('media-picker-api', $config);
		}

		// Get the tinymce config
		$config = $app->getDocument()->getScriptOptions('plg_editor_tinymce');
		if (empty($config)
		|| empty($config['tinyMCE'])
		|| empty($config['tinyMCE']['default'])
		|| empty($config['tinyMCE']['default']['comMediaAdapter'])) {
			return;
		}

		// Set the default adapter in tinymce
		$config['tinyMCE']['default']['comMediaAdapter']    = $info['defaultAdapter'] . ':/' . $info['pathInformation'];
		$config['tinyMCE']['default']['parentUploadFolder'] = '';

		// Save the config in JS store
		$app->getDocument()->addScriptOptions('plg_editor_tinymce', $config);
	}

	/**
	 * Does return the category ID for the given data with a fallback to the form.
	 *
	 * The logic is borrowed from com_fields.
	 *
	 * @param Form $form
	 *
	 * return int
	 */
	private function getCategoryId(object $data, Form $form): int
	{
		$id = $data->catid ?? $form->getValue('catid');

		$id = \is_array($id) ? (int) reset($id) : (int) $id;

		$formField = $form->getField('catid');
		if ($id === 0 && $formField instanceof FormField) {
			$id = $formField->getAttribute('default', null);

			if (!$id) {
				// Choose the first category available
				$catOptions = $formField->__get('options');

				if ($catOptions && !empty($catOptions[0]->value)) {
					$id = (int) $catOptions[0]->value;
				}
			}
		}

		if ($form->getField('catid')) {
			[$component, $section] = FieldsHelper::extract($form->getName(), $data) ?: ['', ''];
			$form->setFieldAttribute('catid', 'refresh-enabled', true);
			$form->setFieldAttribute('catid', 'refresh-cat-id', $id);
			$form->setFieldAttribute('catid', 'refresh-section', $section);
		}

		return $id;
	}
}
