<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use DigitalPeak\Library\DPMedia\Service\MediaProvider;
use DigitalPeak\Plugin\Filesystem\DPFtp\Extension\Ftp;
use FtpClient\FtpClient;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\DI\Container;

\JLoader::import('lib_dpmedia.vendor.autoload', JPATH_LIBRARIES);
\JLoader::import('filesystem.dpftp.vendor.autoload', JPATH_PLUGINS);

return new class() extends MediaProvider {
	public function __construct()
	{
		parent::__construct(Ftp::class);
	}

	public function register(Container $container)
	{
		parent::register($container);

		$container->set(FtpClient::class, function (Container $container) {
			return new FtpClient();
		});

		$container->extend(
			PluginInterface::class,
			function (Ftp $extension, Container $container) {
				$extension->setFtpClient($container->get(FtpClient::class));

				return $extension;
			}
		);
	}
};
