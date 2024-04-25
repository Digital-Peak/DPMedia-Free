<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

use DigitalPeak\Plugin\User\DPMedia\Extension\DPMedia;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			static function (Container $container): DPMedia {
				$dispatcher = $container->get(DispatcherInterface::class);
				$plugin     = new DPMedia(
					$dispatcher,
					(array)PluginHelper::getPlugin('content', 'dpmedia')
				);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
