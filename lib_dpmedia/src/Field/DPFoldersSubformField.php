<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

class DPFoldersSubformField extends SubformField
{
	protected $type = 'Dpfolders';
	protected $pluginName;

	public function getInput()
	{
		HTMLHelper::_('script', 'plg_filesystem_dp' . $this->pluginName . '/dptoken.min.js', ['relative' => true, 'version' => 'auto']);

		$html = parent::getInput();
		$html .= HTMLHelper::_(
			'bootstrap.renderModal',
			'dp' . $this->pluginName . '-modal',
			[
				'title'      => Text::_('PLG_FILESYSTEM_DP' . strtoupper($this->pluginName) . '_IMPORT_MODAL_TITLE'),
				'bodyHeight' => 70,
				'modalWidth' => 80,
				'footer'     => '<button type="button" class="btn btn-secondary dp-import-button" data-bs-dismiss="modal">'
									. Text::_('PLG_FILESYSTEM_DP' . strtoupper($this->pluginName) . '_IMPORT_MODAL_TITLE') . '</button>',
			],
			LayoutHelper::render(
				'import',
				[
					'plugin' => $this->pluginName,
					'text'   => 'PLG_FILESYSTEM_DP' . strtoupper($this->pluginName) . '_IMPORT_MODAL_TEXT',
					'uri'    => $this->getRedirectUri()
				],
				JPATH_LIBRARIES . '/lib_dpmedia/layouts'
			)
		);

		return $html;
	}

	protected function getRedirectUri()
	{
		$uri = !isset($_SERVER['HTTP_HOST']) ? Uri::getInstance('http://localhost') : Uri::getInstance();
		if (filter_var($uri->getHost(), FILTER_VALIDATE_IP)) {
			$uri->setHost('localhost');
		}

		return $uri->toString(['scheme', 'host', 'port', 'path']) . '?option=com_media&task=plugin.oauthcallback&plugin=dp' . $this->pluginName;
	}
}
