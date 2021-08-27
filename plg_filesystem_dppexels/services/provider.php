<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

use DigitalPeak\Library\DPMedia\Service\MediaProvider;
use DigitalPeak\Plugin\Filesystem\DPPexels\Extension\Pexels;

\JLoader::import('lib_dpmedia.vendor.autoload', JPATH_LIBRARIES);

return new MediaProvider(Pexels::class);
