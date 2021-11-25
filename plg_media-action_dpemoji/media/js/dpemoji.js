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
		canvas.style.position = 'absolute';
		getRoot(image).appendChild(canvas);
		canvas.width = image.naturalWidth;
		canvas.height = image.naturalHeight;
		layers.forEach((layer) => {
			const ratio = (1 / layers[0].offsetWidth) * layers[0].width;
			const x = layer.offsetLeft * ratio;
			const y = layer.offsetTop * ratio;
			canvas.getContext('2d').drawImage(layer, x, y, layer.width, layer.height);
		});
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
	function getRatio(canvas) {
		return canvas.clientWidth / canvas.width;
	}
	function getMousePosition(e, canvas) {
		const mouseX = e.offsetX * canvas.width / canvas.clientWidth | 0;
		const mouseY = e.offsetY * canvas.height / canvas.clientHeight | 0;
		return { x: mouseX, y: mouseY };
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
	const loader = { inject, loadPresets, printImageInfo };
	const canvas = { load, getRoot, createLayer, getRatio, save, destroy, isSupported, getActiveLayer, getMousePosition };
	loader.inject(
		'emoji',
		(image) => {
			if (!canvas.isSupported()) {
				return false;
			}
			canvas.load(image, 'emoji_quality');
			loader.loadPresets('emoji');
		},
		(image) => {
			loader.printImageInfo(image, 'emoji');
			const root = canvas.getRoot(image);
			root.addEventListener('drop', (event) => {
				if (!event.dataTransfer.getData('emoji')) {
					return;
				}
				event.preventDefault();
				const layer = canvas.createLayer(image);
				layer.dpEmoji = event.dataTransfer.getData('emoji');
				layer.className = 'dp-emoji-canvas dp-emoji-' + layer.dpEmoji.codePointAt(0).toString(16);
				layer.dpHasControls = false;
				const size = parseInt(document.getElementById('jform_dpemoji_size').value) * canvas.getRatio(layer);
				layer.style.left = Math.max(event.offsetX - (size / 2), 0) + 'px';
				layer.style.top = Math.max(event.offsetY - (size / 2), 0) + 'px';
				layer.style.width = size + 'px';
				layer.dpResizeAction = false;
				draw(layer, parseInt(document.getElementById('jform_dpemoji_size').value), false);
				layer.addEventListener('mousedown', (e) => {
					layer.dpResizeAction = 'drag';
					layer.dpStartPoint = { x: e.clientX, y: e.clientY };
					layer.dpStartStyle = { x: parseInt(layer.style.left), y: parseInt(layer.style.top), size: layer.width, width: parseInt(layer.style.width) };
					const threshold = Math.max(15, layer.width / 4) * canvas.getRatio(layer);
					const rect = layer.getBoundingClientRect();
					if (Math.abs(e.clientX - rect.left) < threshold && Math.abs(e.clientY - rect.top) < threshold) {
						layer.dpResizeAction = 'topleft';
						return;
					}
					if (Math.abs(e.clientX - rect.right) < threshold && Math.abs(e.clientY - rect.top) < threshold) {
						layer.dpResizeAction = 'topright';
						return;
					}
					if (Math.abs(e.clientX - rect.left) < threshold && Math.abs(e.clientY - rect.bottom) < threshold) {
						layer.dpResizeAction = 'bottomleft';
						return;
					}
					if (Math.abs(e.clientX - rect.right) < threshold && Math.abs(e.clientY - rect.bottom) < threshold) {
						layer.dpResizeAction = 'bottomright';
					}
				});
				layer.addEventListener('mousemove', (e) => {
					if (!layer.dpResizeAction && layer.dpHasControls) {
						return;
					}
					if (!layer.dpResizeAction) {
						draw(layer, layer.width, true);
						return;
					}
					const scaledSize = layer.dpStartStyle.size * (layer.clientWidth / layer.width);
					const rect = root.getBoundingClientRect();
					if (layer.dpResizeAction === 'drag') {
						const left = layer.dpStartStyle.x + e.clientX - layer.dpStartPoint.x;
						if (left < 0 || left + scaledSize > rect.right - rect.left) {
							return;
						}
						const top = layer.dpStartStyle.y + e.clientY - layer.dpStartPoint.y;
						if (top < 0 || top + scaledSize > rect.bottom - rect.top) {
							return;
						}
						layer.style.left = left + 'px';
						layer.style.top = top + 'px';
						return;
					}
					let diff = Math.max(Math.abs(e.clientX - layer.dpStartPoint.x), Math.abs(e.clientY - layer.dpStartPoint.y));
					if ((layer.dpResizeAction === 'topleft' && e.clientX > layer.dpStartPoint.x && e.clientY > layer.dpStartPoint.y)
						|| (layer.dpResizeAction === 'topright' && e.clientX < layer.dpStartPoint.x && e.clientY > layer.dpStartPoint.y)
						|| (layer.dpResizeAction === 'bottomleft' && e.clientX > layer.dpStartPoint.x && e.clientY < layer.dpStartPoint.y)
						|| (layer.dpResizeAction === 'bottomright' && e.clientX < layer.dpStartPoint.x && e.clientY < layer.dpStartPoint.y)) {
						diff = -1 * diff;
					}
					if (layer.dpStartStyle.x - diff < 0 || layer.dpStartStyle.x + scaledSize + diff > rect.right - rect.left) {
						return;
					}
					if (layer.dpStartStyle.y - diff < 0 || layer.dpStartStyle.y + scaledSize + diff > rect.bottom - rect.top) {
						return;
					}
					layer.style.left = (layer.dpStartStyle.x - diff) + 'px';
					layer.style.top = (layer.dpStartStyle.y - diff) + 'px';
					layer.style.width = (layer.dpStartStyle.width + (2 * diff)) + 'px';
					draw(layer, Math.round(layer.dpStartStyle.size + ((diff * (layer.width / layer.clientWidth)) * 2)), true);
				});
				layer.addEventListener('mouseup', (e) => {
					if (layer.dpResizeAction) {
						draw(layer, layer.width, false);
						canvas.save(image);
					}
					layer.dpResizeAction = false;
				});
				layer.addEventListener('mouseenter', (e) => {
					draw(layer, layer.width, true);
					layer.dpResizeAction = false;
				});
				layer.addEventListener('mouseleave', (e) => {
					draw(layer, layer.width, false);
					layer.dpResizeAction = false;
				});
				canvas.save(image, document.getElementById('jform_dpemoji_quality').value);
			});
			root.addEventListener('dragover', (event) => event.preventDefault());
			Array.from(document.querySelectorAll('.plg-media-action-dpemoji-icons .dp-icon')).forEach((icon) => {
				icon.addEventListener('dragstart', (event) => event.dataTransfer.setData('emoji', icon.textContent));
			});
		},
		() => canvas.destroy()
	);
	let svgCache = {};
	const draw = async (canvas, size, controls) => {
		const ctx = canvas.getContext('2d');
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		canvas.width = size;
		canvas.height = size;
		ctx.textBaseline = 'middle';
		ctx.textAlign = 'center';
		const name = canvas.dpEmoji.codePointAt(0).toString(16);
		const provider = document.getElementById('jform_dpemoji_provider').value;
		if (provider === 'browser') {
			ctx.font = ctx.font.replace(/\d+px/, Math.round(size * 0.8) + 'px');
			ctx.fillText(canvas.dpEmoji, size / 2, size / 2);
		} else {
			if (svgCache[provider] === undefined) {
				svgCache[provider] = {};
			}
			if (svgCache[provider][name] === undefined) {
				svgCache[provider][name] = '';
				let url = '/twitter/twemoji/master/assets/svg/' + name;
				if (provider === 'openmoji') {
					url = '/hfg-gmuend/openmoji/master/color/svg/' + name.toUpperCase();
				}
				if (provider === 'noto') {
					url = '/googlefonts/noto-emoji/main/svg/emoji_u' + name;
				}
				svgCache[provider][name] = await fetch('https://raw.githubusercontent.com' + url + '.svg')
					.then((response) => response.text())
					.catch(console.error.bind(console));
			}
			if (svgCache[provider][name] === '') {
				console.log(name + 'not found on provider');
				return;
			}
			const img = await new Promise((resolve, reject) => {
				const img = new Image();
				img.addEventListener('load', () => resolve(img));
				img.onerror = reject;
				img.src = 'data:image/svg+xml;base64,' + btoa(svgCache[provider][name].replace('<svg', '<svg width="' + size + '" height="' + size + '"'));
			});
			ctx.drawImage(img, 0, 0);
		}
		canvas.dpHasControls = controls;
		if (!controls) {
			return;
		}
		const width = Math.max(15, size / 4);
		ctx.strokeStyle = '#cacaca';
		ctx.lineWidth = Math.max(1, size / 100);
		ctx.strokeRect(ctx.lineWidth, ctx.lineWidth, width, width);
		ctx.strokeRect(canvas.width - width - ctx.lineWidth, ctx.lineWidth, width, width);
		ctx.strokeRect(ctx.lineWidth, canvas.width - width - ctx.lineWidth, width, width);
		ctx.strokeRect(canvas.width - width - ctx.lineWidth, canvas.width - width - ctx.lineWidth, width, width);
		const fontSize = Math.max(14, size / 5);
		ctx.font = ctx.font.replace(/\d+px/, fontSize + 'px');
		ctx.fillStyle = '#fff';
		const w = ctx.measureText(size + 'px').width + 4;
		ctx.fillRect((size - w) / 2, (size - fontSize - 4) / 2, w, fontSize + 2);
		ctx.fillStyle = '#000';
		ctx.fillText(size + 'px', size / 2, size / 2);
	};
})();
