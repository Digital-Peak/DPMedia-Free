<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Service;

defined('_JEXEC') or die;

use DigitalPeak\Library\DPMedia\Adapter\MimeTypeMapping;
use DigitalPeak\ThinHTTP;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

/**
 * General service provider class to deliver the plugin extension based on a namespace.
 */
class MediaProvider implements ServiceProviderInterface
{
	private $extensionClassName;

	public function __construct(string $extensionClassName)
	{
		$this->extensionClassName = $extensionClassName;
	}

	public function register(Container $container)
	{
		$container->set(ThinHTTP::class, function (Container $container) {
			return new ThinHTTP();
		});
		$container->set(MimeTypeMapping::class, function (Container $container) {
			return new MimeTypeMapping();
		});

		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$plugin = PluginHelper::getPlugin('filesystem', 'dp' . strtolower(basename(str_replace('\\', '/', $this->extensionClassName))));

				return new $this->extensionClassName(
					$container->get(DispatcherInterface::class),
					$container->get(ThinHTTP::class),
					$container->get(MimeTypeMapping::class),
					$container->get(CacheControllerFactoryInterface::class),
					(array) $plugin
				);
			}
		);
	}
};
