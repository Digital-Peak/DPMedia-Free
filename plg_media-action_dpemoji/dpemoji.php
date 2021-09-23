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

class PlgMediaActionDPEmoji extends MediaActionPlugin
{
	/** @var CMSApplication */
	protected $app;

	protected function loadJs()
	{
		HTMLHelper::_('behavior.core');
		parent::loadJs();

		$this->app->getDocument()->addScriptOptions('DPEmoji.presets', $this->params->get('presets', new \stdClass()));

		Text::script('PLG_MEDIA-ACTION_DPEMOJI_MESSAGE_NO_BROWSER_SUPPORT');
	}
}
