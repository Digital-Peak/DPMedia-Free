<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;


/**
 * Stream support for media adapters.
 */
trait StreamSupportTrait
{
	/**
	 * Creates a resource for the given path and content.
	 *
	 * @param string $path
	 * @param string $content
	 *
	 * @return resource
	 */
	public function createResource(string $path, string $content)
	{
		return fopen('data://' . $this->mimeTypeMapping->getMimetype(pathinfo($path, PATHINFO_EXTENSION)) . ';base64,' . base64_encode($content), 'r');
	}
}
