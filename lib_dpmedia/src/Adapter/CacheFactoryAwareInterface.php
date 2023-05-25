<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;

/**
 * Cache factory aware interface.
 */
interface CacheFactoryAwareInterface
{
	/**
	 * Set the cache factory.
	 *
	 * @param CacheControllerFactoryInterface $cacheFactory
	 */
	public function setCacheFactory(CacheControllerFactoryInterface $cacheFactory);
}
