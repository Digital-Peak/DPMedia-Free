<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\User\DPMedia\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Psr\Container\ContainerInterface;

class DPMedia extends CMSPlugin implements BootableExtensionInterface
{
	public function boot(ContainerInterface $container): void
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return;
		}

		// @phpstan-ignore-next-line
		$app->bootPlugin('dpmedia', 'content')->registerListeners();
	}
}
