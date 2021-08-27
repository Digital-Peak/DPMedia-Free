/**
 * @package   DPMedia
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	let listenersAdded = false;
	const applyFilter = (image, save, filter) => {
		if (!filter) {
			filter = 'sepia(' + document.getElementById('jform_dpfilter_sepia_percentage').value + '%) ';
			filter += 'blur(' + document.getElementById('jform_dpfilter_blur_length').value + ') ';
			filter += 'brightness(' + document.getElementById('jform_dpfilter_brightness_percentage').value + '%) ';
			filter += 'contrast(' + document.getElementById('jform_dpfilter_contrast_percentage').value + '%) ';
			filter += 'grayscale(' + document.getElementById('jform_dpfilter_grayscale_percentage').value + '%) ';
			filter += 'hue-rotate(' + document.getElementById('jform_dpfilter_hue_rotate_percentage').value + 'deg) ';
			filter += 'invert(' + document.getElementById('jform_dpfilter_invert_percentage').value + '%) ';
			filter += 'opacity(' + document.getElementById('jform_dpfilter_opacity_percentage').value + '%) ';
			filter += 'saturate(' + document.getElementById('jform_dpfilter_saturate_percentage').value + '%)';
		}
		const canvas = image.parentNode.querySelector('canvas');
		if (!canvas) {
			return;
		}
		const ctx = canvas.getContext('2d');
		ctx.filter = filter;
		ctx.drawImage(image, 0, 0, image.width, image.height);
		if (!save) {
			return;
		}
		const format = Joomla.MediaManager.Edit.original.extension === 'jpg' ? 'jpeg' : Joomla.MediaManager.Edit.original.extension;
		Joomla.MediaManager.Edit.current.contents = canvas.toDataURL('image/' + format, document.getElementById('jform_dpfilter_quality').value / 100);
		window.dispatchEvent(new Event('mediaManager.history.point'));
	};
	const init = (image) => {
		image.style.display = 'none';
		const canvas = document.createElement('canvas');
		image.parentNode.insertBefore(canvas, image);
		if (listenersAdded) {
			canvas.width = image.width;
			canvas.height = image.height;
			canvas.style.maxWidth = '100%';
			applyFilter(image, false);
			return;
		}
		image.addEventListener('load', () => {
			const canvas = image.parentNode.querySelector('canvas');
			if (!canvas) {
				return;
			}
			canvas.width = image.width;
			canvas.height = image.height;
			canvas.style.maxWidth = '100%';
			applyFilter(image, false);
		});
		let ids = '#jform_dpfilter_sepia_percentage,#jform_dpfilter_blur_length,#jform_dpfilter_brightness_percentage,#jform_dpfilter_contrast_percentage,';
		ids += '#jform_dpfilter_grayscale_percentage,#jform_dpfilter_hue_rotate_percentage,#jform_dpfilter_invert_percentage,#jform_dpfilter_opacity_percentage,#jform_dpfilter_saturate_percentage';
		Array.from(document.querySelectorAll(ids)).forEach((input) => input.addEventListener('change', () => applyFilter(image, true)));
		if (!Joomla.getOptions('DPFilter.presets')) {
			return;
		}
		document.getElementById('jform_dpfilter_presets').addEventListener('change', ({ target }) => {
			if (!target.value) {
				return;
			}
			Object.entries(JSON.parse(target.value)).forEach((value) => {
				const input = document.getElementById('jform_dpfilter_' + value[0]);
				if (!input) {
					return;
				}
				input.value = value[1];
				input.dispatchEvent(new Event('change'));
			});
			applyFilter(image, true);
		});
		listenersAdded = true;
	};
	window.addEventListener('media-manager-edit-init', () => {
		Joomla.MediaManager.Edit.plugins.dpfilter = {
			Activate(image) {
				return new Promise((resolve, reject) => {
					if (!!window.CanvasRenderingContext2D) {
						init(image);
						resolve();
						return;
					}
					Joomla.renderMessages({ error: [Joomla.Text._('PLG_MEDIA-ACTION_DPFILTER_NO_BROWSER_SUPPORT')] });
					reject();
				});
			},
			Deactivate(image) {
				return new Promise((resolve ) => {
					image.style.display = '';
					const canvas = image.parentNode.querySelector('canvas');
					if (canvas) {
						canvas.remove();
					}
					resolve();
				});
			},
		};
	}, { once: true });
}());
