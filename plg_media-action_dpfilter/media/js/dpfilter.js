/**
 * @package   DPMedia
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	let layers = [];
	function createLayer(image) {
		const parent = getRoot(image);
		const canvas = document.createElement('canvas');
		canvas.style.position = 'absolute';
		canvas.className = parent.className + '__layer';
		parent.appendChild(canvas);
		canvas.width = image.naturalWidth;
		canvas.height = image.naturalHeight;
		canvas.style.maxWidth = '100%';
		layers.push(canvas);
		return canvas;
	}
	function save(image, quality) {
		const canvas = document.createElement('canvas');
		getRoot(image).appendChild(canvas);
		canvas.width = image.naturalWidth;
		canvas.height = image.naturalHeight;
		layers.forEach((layer) => canvas.getContext('2d').drawImage(layer, 0, 0, layer.width, layer.height));
		const format = Joomla.MediaManager.Edit.original.extension === 'jpg' ? 'jpeg' : Joomla.MediaManager.Edit.original.extension;
		Joomla.MediaManager.Edit.current.contents = canvas.toDataURL('image/' + format, quality / 100);
		window.dispatchEvent(new Event('mediaManager.history.point'));
		canvas.remove();
	}
	function load(image, qualityId) {
		window.addEventListener('keyup', (event) => {
			if (getActiveLayer() === null) {
				return;
			}
			if ((event.key !== 'Escape' && event.key !== 'z') || !event.ctrlKey) {
				return;
			}
			layers.pop().remove();
			save(image, document.getElementById('jform_dp' + qualityId).value);
		});
	}
	function getActiveLayer() {
		if (layers.length < 2) {
			return null;
		}
		return layers.slice(-1)[0];
	}
	function getRoot(image) {
		let parent = document.querySelector('.plg-media-action-dpmedia');
		if (parent !== null) {
			return parent;
		}
		parent = document.createElement('div');
		parent.className = 'plg-media-action-dpmedia';
		parent.style.position = 'relative';
		parent.style.maxWidth = '100%';
		image.parentNode.insertBefore(parent, image);
		const mainCanvas = document.createElement('canvas');
		mainCanvas.className = parent.className + '__maincanvas';
		mainCanvas.style.maxWidth = '100%';
		parent.appendChild(mainCanvas);
		layers.push(mainCanvas);
		parent.style.width = image.naturalWidth + 'px';
		mainCanvas.width = image.naturalWidth;
		mainCanvas.height = image.naturalHeight;
		mainCanvas.getContext('2d').drawImage(image, 0, 0, image.naturalWidth, image.naturalHeight);
		image.parentNode.style.maxWidth = '100%';
		image.style.display = 'none';
		return parent;
	}
	function destroy() {
		if (layers.length === 0) {
			return;
		}
		layers[0].parentNode.parentNode.querySelector('img').style.display = 'unset';
		layers[0].parentNode.remove();
		layers = [];
	}
	function isSupported() {
		return !!window.CanvasRenderingContext2D;
	}
	let isInitialized = false;
	function inject(name, init, load, deactivate) {
		window.addEventListener('media-manager-edit-init', () => {
			Joomla.MediaManager.Edit.plugins['dp' + name] = {
				Activate(image) {
					return new Promise(async (resolve, reject) => {
						if (!image.complete || image.naturalHeight === 0) {
							await new Promise((r) => image.onload = () => r());
						}
						if (!isInitialized && init(image) === false) {
							isInitialized = false;
							Joomla.renderMessages({ error: [Joomla.Text._('PLG_MEDIA-ACTION_DP' + name.toUpperCase() + '_MESSAGE_NO_BROWSER_SUPPORT')] });
							reject();
							return;
						}
						isInitialized = true;
						load(image);
						resolve();
					});
				},
				Deactivate(image) {
					return new Promise((resolve ) => {
						deactivate(image);
						resolve();
					});
				},
			};
		}, { once: true });
	}
	function loadPresets(name, colorFieldnames) {
		if (!Joomla.getOptions('DP' + name.charAt(0).toUpperCase() + name.slice(1) + '.presets')) {
			return;
		}
		if (!Array.isArray(colorFieldnames)) {
			colorFieldnames = [];
		}
		document.getElementById('jform_dp' + name + '_presets').addEventListener('change', ({ target }) => {
			if (!target.value) {
				return;
			}
			Object.entries(JSON.parse(target.value)).forEach((value) => {
				const input = document.getElementById('jform_dp' + name + '_' + value[0]);
				if (!input) {
					return;
				}
				if (input.nodeName.toLowerCase() === 'fieldset') {
					Array.from(input.querySelectorAll('input')).forEach((input) => input.checked = input.id == 'jform_dp' + name + '_' + value[0] + value[1]);
					return;
				}
				input.value = value[1];
				input.dispatchEvent(new Event('change'));
				colorFieldnames.forEach((colorFieldName) => {
					if (input.id !== 'jform_dp' + name + '_' + colorFieldName) {
						return;
					}
					if (!input.nextSibling) {
						return;
					}
					const panel = input.nextSibling.querySelector('.minicolors-swatch-color');
					if (!panel) {
						return;
					}
					panel.style.backgroundColor = value[1];
				});
			});
		});
	}
	function printImageInfo(image, name) {
		document.querySelector('#fieldset-dp' + name + ' legend').innerHTML = image.naturalWidth + 'px - ' + image.naturalHeight + 'px';
	}
	const applyFilter = (layer, image) => {
		let filter = 'sepia(' + document.getElementById('jform_dpfilter_sepia_percentage').value + '%) ';
		filter += 'blur(' + document.getElementById('jform_dpfilter_blur_length').value + ') ';
		filter += 'brightness(' + document.getElementById('jform_dpfilter_brightness_percentage').value + '%) ';
		filter += 'contrast(' + document.getElementById('jform_dpfilter_contrast_percentage').value + '%) ';
		filter += 'grayscale(' + document.getElementById('jform_dpfilter_grayscale_percentage').value + '%) ';
		filter += 'hue-rotate(' + document.getElementById('jform_dpfilter_hue_rotate_percentage').value + 'deg) ';
		filter += 'invert(' + document.getElementById('jform_dpfilter_invert_percentage').value + '%) ';
		filter += 'opacity(' + document.getElementById('jform_dpfilter_opacity_percentage').value + '%) ';
		filter += 'saturate(' + document.getElementById('jform_dpfilter_saturate_percentage').value + '%)';
		const ctx = layer.getContext('2d');
		ctx.clearRect(0, 0, layer.width, layer.height);
		ctx.filter = filter;
		ctx.drawImage(image, 0, 0, layer.width, layer.height);
	};
	inject(
		'filter',
		(image) => {
			if (!isSupported()) {
				return false;
			}
			load(image, 'filter_quality');
			loadPresets('filter');
			Array.from(document.querySelectorAll('#fieldset-dpfilter input, #fieldset-dpfilter select')).forEach(
				(input) => input.addEventListener('change', () => {
					applyFilter(getActiveLayer() ? getActiveLayer() : createLayer(image), image);
					save(image, document.getElementById('jform_dpfilter_quality').value);
				})
			);
		},
		(image) => printImageInfo(image, 'filter'),
		() => destroy()
	);
}());
