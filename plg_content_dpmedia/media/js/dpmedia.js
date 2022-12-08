/**
 * @package   DPMedia
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', (e) => {
		const info = Joomla.getOptions('DPMedia.cf.select');
		if (!info) {
			return;
		}
		if (!info.pathInformation) {
			return;
		}
		document.addEventListener('onMediaFileSelected', (e) => {
			if (!e.detail.path) {
				return;
			}
			e.detail.path += info.pathInformation;
		});
		const modal = document.querySelector('div[id$="editors-xtd_image_modal"]');
		if (!modal) {
			return;
		}
		modal.dataset.url += info.pathInformation;
		modal.dataset.iframe = modal.dataset.iframe.replace('&author', info.pathInformation + '&author');
	});
})();
