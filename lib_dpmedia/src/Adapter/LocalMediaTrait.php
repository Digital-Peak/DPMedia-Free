<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Local media support trait for media adapters.
 */
trait LocalMediaTrait
{
	use DownloadMediaTrait;

	/**
	 * Fetch the url for the given path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	abstract protected function fetchUrl(string $path): string;

	/**
	 * Returns the config.
	 *
	 * @return Registry
	 */
	abstract protected function getConfig(): Registry;

	public function getUrl(string $path): string
	{
		if (!$this->getConfig()->get('local_media')) {
			return $this->fetchUrl($path);
		}

		return rtrim(Uri::root(), '/') . $this->download($this->getFile($path), $this->getConfig());
	}
}
