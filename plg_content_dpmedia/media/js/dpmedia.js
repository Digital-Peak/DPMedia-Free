/**
 * @package   DPMedia
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		const info = Joomla.getOptions('DPMedia.cf.select');
		if (!info || !info.pathInformation) {
			return;
		}
		document.addEventListener('onMediaFileSelected', (e) => {
			const storage = Joomla.optionsStorage['media-picker-api'];
			if (!storage || !storage.apiBaseUrl) {
				return;
			}
			storage.apiBaseUrl = storage.apiBaseUrl.replace('&' + info.pathInformation, '');
			if (e.target.activeElement.src.indexOf('context=') === -1) {
				return;
			}
			storage.apiBaseUrl += '&' + info.pathInformation;
		});
		if (!info.defaultAdapter) {
			return;
		}
		const modal = document.querySelector('div[id$="editors-xtd_image_modal"]');
		if (!modal) {
			return;
		}
		modal.dataset.url += info.pathInformation;
		modal.dataset.iframe = modal.dataset.iframe.replace(
			'&author',
			info.pathInformation + (modal.dataset.iframe.indexOf('&path') === -1 ? '&path=' + info.defaultAdapter + ':/' : '') + '&author'
		);
	});
})();
