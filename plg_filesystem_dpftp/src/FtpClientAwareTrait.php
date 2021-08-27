<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPFtp;

use FtpClient\FtpClient;

/**
 * FTP client aware trait.
 */
trait FtpClientAwareTrait
{
	/** @var FtpClient $ftpClient */
	private $ftpClient;

	/**
	 * Get the FTP client.
	 *
	 * @return FtpClient
	 */
	public function getFtpClient(): FtpClient
	{
		return $this->ftpClient;
	}

	/**
	 * Set the FTP client.
	 *
	 * @param FtpClient $ftpClient
	 */
	public function setFtpClient(FtpClient $ftpClient)
	{
		$this->ftpClient = $ftpClient;
	}
}
