<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPFtp\Extension;

use DigitalPeak\Library\DPMedia\Extension\Media;
use DigitalPeak\Plugin\Filesystem\DPFtp\FtpClientAwareInterface;
use DigitalPeak\Plugin\Filesystem\DPFtp\FtpClientAwareTrait;

class Ftp extends Media implements FtpClientAwareInterface
{
	use FtpClientAwareTrait;

	public function getAdapters()
	{
		$adapters = parent::getAdapters();
		foreach ($adapters as $adapter) {
			$adapter->setFtpClient($this->getFtpClient());
		}

		return $adapters;
	}
}
