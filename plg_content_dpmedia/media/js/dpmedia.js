/**
 * @package   DPMedia
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('onMediaFileSelected', () => {
		setTimeout(() => {
			const altField = document.querySelector('input[data-is="alt-value"]');
			if (!altField) {
				return;
			}
			let name = Joomla.selectedMediaFile.path;
			if (Joomla.selectedMediaFile.name) {
				name = Joomla.selectedMediaFile.name;
			}
			name = name.split('/').pop().replace(/_|-/g, ' ');
			name = name.charAt(0).toUpperCase() + name.slice(1);
			altField.value = name.split('.').slice(0, -1).join('.');
		}, 500);
	});
})();
