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

// From v1.13.0 to case 10766
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

// From v1.14.1 to case 10543
'/plugins/media-action/dpconvert/media/codecs/squoosh_png.min.js',
'/plugins/media-action/dpconvert/media/codecs/squoosh_png_bg.wasm',

// From v1.15.1 to case 11158
'/plugins/content/dpmedia/media/js/dpmedia.js',
'/plugins/content/dpmedia/media/js/dpmedia.js.map',
'/plugins/filesystem/dpdropbox/media/js/dptoken.js',
'/plugins/filesystem/dpdropbox/media/js/dptoken.js.map',
'/plugins/filesystem/dpflickr/media/js/dptoken.js',
'/plugins/filesystem/dpflickr/media/js/dptoken.js.map',
'/plugins/filesystem/dpgoogle/media/js/dptoken.js',
'/plugins/filesystem/dpgoogle/media/js/dptoken.js.map',
'/plugins/filesystem/dpmicrosoft/media/js/dptoken.js',
'/plugins/filesystem/dpmicrosoft/media/js/dptoken.js.map',
'/plugins/filesystem/dppermissions/media/css/plugin/dppermissions.css',
'/plugins/filesystem/dppermissions/media/css/plugin/dppermissions.css.map',
'/plugins/filesystem/dppermissions/media/js/plugin/dppermissions.js',
'/plugins/filesystem/dppermissions/media/js/plugin/dppermissions.js.map',
'/plugins/filesystem/dpreferences/media/css/plugin/dpreferences.css',
'/plugins/filesystem/dpreferences/media/css/plugin/dpreferences.css.map',
'/plugins/filesystem/dpreferences/media/js/plugin/dpreferences.js',
'/plugins/filesystem/dpreferences/media/js/plugin/dpreferences.js.map',
'/plugins/filesystem/dprestricted/media/js/restricted.js',
'/plugins/filesystem/dprestricted/media/js/restricted.js.map',
'/plugins/filesystem/dpsmugmug/media/js/dptoken.js',
'/plugins/filesystem/dpsmugmug/media/js/dptoken.js.map',
'/plugins/media-action/dpborder/media/css/dpborder.css',
'/plugins/media-action/dpborder/media/css/dpborder.css.map',
'/plugins/media-action/dpborder/media/js/dpborder.js',
'/plugins/media-action/dpborder/media/js/dpborder.js.map',
'/plugins/media-action/dpconvert/media/css/dpconvert.css',
'/plugins/media-action/dpconvert/media/css/dpconvert.css.map',
'/plugins/media-action/dpconvert/media/js/dpconvert.js',
'/plugins/media-action/dpconvert/media/js/dpconvert.js.map',
'/plugins/media-action/dpemoji/media/css/dpemoji.css',
'/plugins/media-action/dpemoji/media/css/dpemoji.css.map',
'/plugins/media-action/dpemoji/media/js/dpemoji.js',
'/plugins/media-action/dpemoji/media/js/dpemoji.js.map',
'/plugins/media-action/dpfilter/media/css/dpfilter.css',
'/plugins/media-action/dpfilter/media/css/dpfilter.css.map',
'/plugins/media-action/dpfilter/media/js/dpfilter.js',
'/plugins/media-action/dpfilter/media/js/dpfilter.js.map',
'/plugins/media-action/dpline/media/css/dpline.css',
'/plugins/media-action/dpline/media/css/dpline.css.map',
'/plugins/media-action/dpline/media/js/dpline.js',
'/plugins/media-action/dpline/media/js/dpline.js.map',
'/plugins/media-action/dpshape/media/css/dpshape.css',
'/plugins/media-action/dpshape/media/css/dpshape.css.map',
'/plugins/media-action/dpshape/media/js/dpshape.js',
'/plugins/media-action/dpshape/media/js/dpshape.js.map',
'/plugins/media-action/dptext/media/css/dptext.css',
'/plugins/media-action/dptext/media/css/dptext.css.map',
'/plugins/media-action/dptext/media/js/dptext.js',
'/plugins/media-action/dptext/media/js/dptext.js.map',

// From v1.15.2 to case 11203
'/media/plg_media-action/dpconvert/codecs/avif.min.js',
'/media/plg_media-action/dpconvert/codecs/jxl.min.js',
'/media/plg_media-action/dpconvert/codecs/mozjpeg.min.js',
'/media/plg_media-action/dpconvert/codecs/squoosh_oxipng.min.js',
'/media/plg_media-action/dpconvert/codecs/squoosh_oxipng_bg.wasm',
'/media/plg_media-action/dpconvert/codecs/webp_enc.min.js',
'/media/plg_media-action/dpconvert/codecs/wp2_enc.min.js',

// From v1.15.3 to case 11262
'/media/lib_dpmedia/js',
'/media/lib_dpmedia/css',
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
