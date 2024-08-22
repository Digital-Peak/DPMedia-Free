<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Service;

use DigitalPeak\Library\DPMedia\Adapter\MimeTypeMapping;
use DigitalPeak\Library\DPMedia\Extension\Media;
use DigitalPeak\ThinHTTP\ClientInterface;
use DigitalPeak\ThinHTTP\CurlClient;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

/**
 * General service provider class to deliver the plugin extension based on a namespace.
 */
class MediaProvider implements ServiceProviderInterface
{
	public function __construct(private readonly string $extensionClassName)
	{
	}

	public function register(Container $container): void
	{
		require_once JPATH_LIBRARIES . '/lib_dpmedia/vendor/autoload.php';

		$container->set(ClientInterface::class, static fn (Container $container): ClientInterface => new CurlClient());
		$container->set(MimeTypeMapping::class, static fn (Container $container): MimeTypeMapping => new MimeTypeMapping());

		$container->set(
			PluginInterface::class,
			function (Container $container): object {
				$plugin = new $this->extensionClassName(
					$container->get(DispatcherInterface::class),
					$container->get(ClientInterface::class),
					$container->get(MimeTypeMapping::class),
					$container->get(CacheControllerFactoryInterface::class),
					(array)PluginHelper::getPlugin('filesystem', 'dp' . strtolower(basename(str_replace('\\', '/', $this->extensionClassName))))
				);

				if ($plugin instanceof Media) {
					$plugin->setApplication(Factory::getApplication());
					$plugin->setDatabase($container->get(DatabaseInterface::class));
				}

				return $plugin;
			}
		);
	}
}
