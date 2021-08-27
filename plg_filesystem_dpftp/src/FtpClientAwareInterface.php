<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPFtp;

defined('_JEXEC') or die;

use FtpClient\FtpClient;

/**
 * FTP client aware interface.
 */
interface FtpClientAwareInterface
{
	/**
	 * Get the FTP client.
	 *
	 * @return FtpClient
	 */
	public function getFtpClient(): FtpClient;

	/**
	 * Set the FTP client.
	 *
	 * @param FtpClient $ftpClient
	 */
	public function setFtpClient(FtpClient $ftpClient);
}
