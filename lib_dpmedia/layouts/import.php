<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;

extract($displayData);

Form::addFormPath(JPATH_PLUGINS . '/filesystem/dp' . $plugin);

/** @var Form $form */
$form = Factory::getContainer()->get(FormFactoryInterface::class)->createForm('dp' . $plugin, ['control' => 'dp']);
$form->loadFile('dp' . $plugin, false, '//form/fields');
?>
<div style="padding: 2rem">
	<p><?php echo Text::sprintf($text, $uri, $uri); ?></p>
	<hr/>
	<?php foreach ($form->getFieldset() as $field) { ?>
		<?php if (!$form->getFieldAttribute($field->__get('fieldname'), 'import')) { ?>
			<?php continue; ?>
		<?php } ?>
		<?php $field->__set('required', 'false'); ?>
		<?php $field->__set('class', str_replace('required','',$field->__get('class'))); ?>
		<div class="control-group">
			<div class="control-label">
				<?php echo $field->label; ?>
			</div>
			<div class="controls">
				<?php echo $field->input; ?>
				<br/><b><?php echo Text::_($field->description) ?></b>
			</div>
		</div>
	<?php } ?>
	<input type="hidden" name="dp_redirect_url" id="dp_redirect_url" value="<?php echo $uri; ?>">
</div>
