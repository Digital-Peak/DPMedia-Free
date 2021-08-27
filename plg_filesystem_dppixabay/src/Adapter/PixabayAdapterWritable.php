<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPPixabay\Adapter;

defined('_JEXEC') or die;

use DigitalPeak\Library\DPMedia\Adapter\CacheFactoryAwareInterface;
use DigitalPeak\Library\DPMedia\Adapter\CacheTrait;

/**
 * Cache Pixabay adapter for Joomla 4 media manager.
 */
class PixabayAdapterWritable extends PixabayAdapter implements CacheFactoryAwareInterface
{
	use CacheTrait;
}
