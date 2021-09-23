<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
?>
<div class="plg-media-action-dpemoji-icons">
	<?php foreach ($displayData['groups'] as $name => $emojis) { ?>
		<div class="dp-icon-group">
			<div class="dp-icon-group__title">
				<?php echo Text::_('PLG_MEDIA-ACTION_DPEMOJI_ICONS_GROUP_' . strtoupper(str_replace(' & ', '_', $name))); ?>
			</div>
			<div class="dp-icon-group__emojis">
				<?php foreach ($emojis as $index => $emoji) { ?>
					<div class="dp-icon" draggable="true"><?php echo $emoji->emoji; ?></div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
</div>
