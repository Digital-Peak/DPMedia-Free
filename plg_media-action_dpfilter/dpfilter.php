<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Media\Administrator\Plugin\MediaActionPlugin;

class PlgMediaActionDPFilter extends MediaActionPlugin
{
	/** @var CMSApplication */
	protected $app;

	protected function loadJs()
	{
		HTMLHelper::_('behavior.core');

		$this->app->getDocument()->addScriptOptions('DPFilter.presets', $this->params->get('presets', new \stdClass()));

		parent::loadJs();

		Text::script('PLG_MEDIA-ACTION_DPFILTER_MESSAGE_NO_BROWSER_SUPPORT');
	}
}
