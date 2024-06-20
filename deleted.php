<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2023 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

$files = [
// From v1.2.0 to v1.3.0
'/plugins/filesystem/dpamazon/dpamazon.php',
'/plugins/filesystem/dpdropbox/dpdropbox.php',
'/plugins/filesystem/dpflickr/dpflickr.php',
'/plugins/filesystem/dpftp/dpftp.php',
'/plugins/filesystem/dpgoogle/dpgoogle.php',
'/plugins/filesystem/dppexels/dppexels.php',
'/plugins/filesystem/dppixabay/dppixabay.php',
'/plugins/filesystem/dpunsplash/dpunsplash.php',
'/plugins/filesystem/dpwebdav/dpwebdav.php',

// From v1.4.0 to v1.5.0
'/plugins/filesystem/dppermissions/media/css/vendor',
'/plugins/filesystem/dppermissions/media/js/vendor',

// From v1.6.0 to v1.7.0
'/plugins/filesystem/dpflickr/src/Extension/FlickrAuth.php',

// From v1.8.0 to case 9988
'/plugins/content/dpmedia/src/Field/AdaptersField.php',

// From v1.8.0 to case 10026
'/plugins/media-action/dpborder/media/icons/mobile-alt.svg',
'/plugins/media-action/dpborder/media/icons/tablet-alt.svg',

// From v1.13.0 to case 10792
'/plugins/filesystem/dpsmugmug/src/Adapter/SmugmugAdapter.php',
'/plugins/filesystem/dpsmugmug/src/Adapter/SmugmugAdapterWritable.php',
'/plugins/filesystem/dpsmugmug/src/Extension/Smugmug.php',

// From v1.13.0 to case 10874
'/plugins/content/dpmedia/media',
'/plugins/content/dpmedia/params/com_banners.xml',
'/plugins/content/dpmedia/params/com_categories.xml',
'/plugins/content/dpmedia/params/com_contact.xml',
'/plugins/content/dpmedia/params/com_content.xml',
'/plugins/content/dpmedia/params/com_dpcalendar.xml',
'/plugins/content/dpmedia/params/com_newsfeeds.xml',
'/plugins/content/dpmedia/params/com_tags.xml',
'/plugins/content/dpmedia/params/com_users.xml',

// From v1.13.0 to case 10767
'/plugins/content/dpmedia/media',
'/plugins/content/dpmedia/params/com_banners.xml',
'/plugins/content/dpmedia/params/com_categories.xml',
'/plugins/content/dpmedia/params/com_contact.xml',
'/plugins/content/dpmedia/params/com_content.xml',
'/plugins/content/dpmedia/params/com_dpcalendar.xml',
'/plugins/content/dpmedia/params/com_newsfeeds.xml',
'/plugins/content/dpmedia/params/com_tags.xml',
'/plugins/content/dpmedia/params/com_users.xml',
'/plugins/filesystem/dpsmugmug/src/Adapter/SmugmugAdapter.php',
'/plugins/filesystem/dpsmugmug/src/Adapter/SmugmugAdapterWritable.php',
'/plugins/filesystem/dpsmugmug/src/Extension/Smugmug.php',
];

foreach ($files as $file) {
	$fullPath = JPATH_ROOT . $file;

	if (empty($file) || !file_exists($fullPath)) {
		continue;
	}

	if (pathinfo($fullPath, PATHINFO_EXTENSION)) {
		unlink($fullPath);
		continue;
	}

	try {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileinfo) {
			$todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
			$todo($fileinfo->getRealPath());
		}

		rmdir($fullPath);
	} catch (Exception $e) {
	}
}
