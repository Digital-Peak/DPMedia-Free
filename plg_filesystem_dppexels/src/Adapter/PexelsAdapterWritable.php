<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPPexels\Adapter;

use DigitalPeak\Library\DPMedia\Adapter\CacheFactoryAwareInterface;
use DigitalPeak\Library\DPMedia\Adapter\CacheTrait;
use DigitalPeak\Library\DPMedia\Adapter\LocalMediaTrait;
use Joomla\Registry\Registry;

/**
 * Cache Pexels adapter for Joomla 4 media manager.
 */
class PexelsAdapterWritable extends PexelsAdapter implements CacheFactoryAwareInterface
{
	use CacheTrait;
	use LocalMediaTrait;

	protected function getContent(\stdclass $file, Registry $config): string
	{
		return $this->http->get($file->url)->dp->body;
	}
}
