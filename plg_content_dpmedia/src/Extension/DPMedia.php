<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPMedia\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use stdClass;

class DPMedia extends CMSPlugin
{
	/**
	 * The mapping between the context and the id attribute.
	 */
	private static $ID_MAP = ['com_content.article' => 'a_id', 'com_plugins.plugin' => 'extension_id'];

	protected $autoloadLanguage = true;
	protected $app;

	public function onContentPrepareForm(Form $form, $data)
	{
		// Compile the real context
		$context = FieldsHelper::extract($form->getName());
		if (count($context) < 2) {
			return;
		}

		$context = implode('.', $context);

		// When custom field form load the own preferences
		if (strpos($context, 'com_fields.field') === 0 && !empty($data->type) && $data->type === 'media') {
			$form->loadFile(JPATH_PLUGINS . '/content/dpmedia/params/media-field.xml');
			return;
		}

		$customFields = FieldsHelper::getFields($context, $data instanceof stdClass ? $data : null);

		// Loop over the field
		foreach ($form->getFieldset() as $field) {
			// Check if it is a media field
			if ($field->__get('type') !== 'Accessiblemedia' || $field->group !== 'com_fields') {
				continue;
			}

			foreach ($customFields as $customField) {
				if ($customField->name !== $field->fieldname || $customField->fieldparams->get('dpmedia_accessible', 1)) {
					continue;
				}

				// Ensure the fields can be found
				FormHelper::addFieldPrefix('DigitalPeak\\Plugin\\Content\\DPMedia\\Field');
				$form->setFieldAttribute($field->__get('fieldname'), 'type', 'dpmedia', $field->__get('group'));
			}
		}

		$this->addRestrictedSupport($context, $form, $data);
	}

	/**
	 * Add support for restricted media fields.
	 */
	private function addRestrictedSupport(string $context, Form $form, $data)
	{
		// Ignore com_config
		if ($context === 'com_config.application') {
			return;
		}

		// Load data from input when not available
		if (!$data) {
			$data = $this->app->getInput()->get('jform', [], 'array');
		}

		// Ensure an object
		if (is_array($data)) {
			$data = (object)$data;
		}

		if (strpos($context, '.form') && $this->app->isClient('site') && !empty($data->typeAlias)) {
			$context = $data->typeAlias;
		}

		// Determine the id, on front end the components have special variables
		$id = !empty($data->id) ? $data->id : $this->app->getInput()->get('id', 0);
		if (empty($id) && !empty(self::$ID_MAP[$context]) && $this->app->getInput()->get(self::$ID_MAP[$context])) {
			$id = $this->app->getInput()->get(self::$ID_MAP[$context]);
		}

		// The root folder where the params are
		$root = JPATH_PLUGINS . '/content/dpmedia/params';

		// Inject into component config when possible
		if ($context === 'com_config.component' && file_exists($root . '/' . $this->app->getInput()->get('component') . '.xml')) {
			$form->loadFile($root . '/' . $this->app->getInput()->get('component') . '.xml');
			return;
		}

		// When not enough information, return
		if (!strpos($context, '.')) {
			return;
		}

		// Load the adapter from configuration
		$adapter = $this->params->get('adapter');
		if ($componentParams = ComponentHelper::getParams(substr($context, 0, strpos($context, '.')))) {
			$adapter = $componentParams->get('dpmedia_adapter', $adapter);
		}

		// Flag if the JS assets should be loaded when there is at least one media field in the form
		$found = false;

		// Loop over the field
		foreach ($form->getFieldset() as $field) {
			// Check if it is a subform field
			if ($field->__get('type') === 'Subform') {
				// Make sure the prefix exists
				FormHelper::addFieldPrefix('DigitalPeak\Plugin\Content\DPMedia\Field');

				// Transform the field into a dp one
				$name = substr($field->__get('name'), strpos($field->__get('name'), $field->__get('group') . '][') + strlen($field->__get('group')));
				$name = trim($name, '[]');
				$form->setFieldAttribute($name, 'type', 'dpmediasubform', $field->__get('group'));

				// Compile the context
				$subFormContext = '&amp;context=' . $context . '&amp;item=' . (!empty($id) ? $id : '');
				if (!empty($data->catid)) {
					$subFormContext .= '&amp;catid=' . $data->catid;
				}

				// Pass the required variables for the media fields in the subform field
				$form->setFieldAttribute($name, 'context', $subFormContext, $field->__get('group'));
				$form->setFieldAttribute($name, 'adapter', $adapter, $field->__get('group'));

				// Ensure the assets are loaded
				$found = true;

				// Process the next field
				continue;
			}

			// Check if it is a media field
			if ($field->__get('type') !== 'Media' && $field->__get('type') !== 'Accessiblemedia') {
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

			// Disable fields when no id is available
			if (empty($id) && strpos($directory, 'dprestricted') === 0) {
				// Only print warning on GET requests
				if ($_SERVER['REQUEST_METHOD'] === 'GET') {
					$this->app->enqueueMessage(Text::sprintf('PLG_CONTENT_DPMEDIA_FIELD_REMOVED_MESSAGE', $field->__get('title')), 'warning');
				}
				$form->removeField($field->__get('fieldname'), $field->__get('group'));
				continue;
			}

			// Ensure assets are loaded
			$found = true;

			// Normalize the directory
			$directory .= ':/';

			// Only show the restricted adapter when is selected
			if (strpos($directory, 'dprestricted') === 0) {
				$directory .= '&amp;force=1';
			}

			// Get more variables for adapters
			$directory .= '&amp;context=' . $context . '&amp;item=' . $id;
			if (!empty($data->catid)) {
				$directory .= '&amp;catid=' . $data->catid;
			}

			// Transform the field when is accessible media
			if (strtolower($form->getFieldAttribute($field->__get('fieldname'), 'type', '', $field->__get('group'))) === 'accessiblemedia') {
				// Make sure the prefix exists
				FormHelper::addFieldPrefix('DigitalPeak\Plugin\Content\DPMedia\Field');
				$form->setFieldAttribute($field->__get('fieldname'), 'type', 'dpmediaaccessible', $field->__get('group'));
			}
			$form->setFieldAttribute($field->__get('fieldname'), 'directory', $directory, $field->__get('group'));

			// For direct media set also the asset
			$args = html_entity_decode($directory);
			$form->setFieldAttribute($field->__get('fieldname'), 'asset_field', 'none', $field->__get('group'));
			$form->setFieldAttribute($field->__get('fieldname'), 'asset_id', substr($args, strpos($args, '&')), $field->__get('group'));
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
		if ($item = !empty($id) ? $id : null) {
			$url .= '&item=' . $item;
		}

		if ($catid = !empty($data->catid) ? $data->catid : null) {
			$url .= '&catid=' . $catid;
		}

		// Set the path on the JS store
		$this->app->getDocument()->addScriptOptions('DPMedia.cf.select', ['pathInformation' => $url, 'defaultAdapter' => $adapter]);
	}

	public function onBeforeCompileHead()
	{
		// Load the path information from the JS store
		$info = $this->app->getDocument()->getScriptOptions('DPMedia.cf.select');
		if (empty($info) || empty($info['pathInformation']) || empty($info['adapter'])) {
			return;
		}

		// Get the tinymce config
		$config = $this->app->getDocument()->getScriptOptions('plg_editor_tinymce');
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
		$this->app->getDocument()->addScriptOptions('plg_editor_tinymce', $config);
	}
}
