<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use DigitalPeak\Library\DPMedia\Service\MediaProvider;
use DigitalPeak\Plugin\Filesystem\DPPixabay\Extension\Pixabay;

\JLoader::import('lib_dpmedia.vendor.autoload', JPATH_LIBRARIES);

return new MediaProvider(Pixabay::class);
