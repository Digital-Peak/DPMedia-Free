/**
 * @package   DPMedia
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	let canvasList = [];
	let mainCanvas;
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
		const width = Math.min(15, size / 4);
		ctx.strokeStyle = '#cacaca';
		ctx.lineWidth = 1;
		ctx.strokeRect(ctx.lineWidth, ctx.lineWidth, width, width);
		ctx.strokeRect(canvas.width - width - ctx.lineWidth, ctx.lineWidth, width, width);
		ctx.strokeRect(ctx.lineWidth, canvas.width - width - ctx.lineWidth, width, width);
		ctx.strokeRect(canvas.width - width - ctx.lineWidth, canvas.width - width - ctx.lineWidth, width, width);
		ctx.font = ctx.font.replace(/\d+px/, '14px');
		ctx.fillStyle = '#fff';
		const w = ctx.measureText(size + 'px').width + 4;
		ctx.fillRect((size - w) / 2, (size - 18) / 2, w, 16);
		ctx.fillStyle = '#000';
		ctx.fillText(size + 'px', size / 2, size / 2);
	};
	const save = (image) => {
		const canvas = document.createElement('canvas');
		canvas.width = image.width;
		canvas.height = image.height;
		canvas.style.maxWidth = '100%';
		canvas.getContext('2d').drawImage(image, 0, 0, image.width, image.height);
		mainCanvas.parentNode.insertBefore(canvas, mainCanvas);
		canvasList.forEach((emojiCanvas) => {
			const ratio = (1 / mainCanvas.offsetWidth) * mainCanvas.width;
			const x = emojiCanvas.offsetLeft * ratio;
			const y = emojiCanvas.offsetTop * ratio;
			canvas.getContext('2d').drawImage(emojiCanvas, x, y, emojiCanvas.width * ratio, emojiCanvas.height * ratio);
		});
		const format = Joomla.MediaManager.Edit.original.extension === 'jpg' ? 'jpeg' : Joomla.MediaManager.Edit.original.extension;
		Joomla.MediaManager.Edit.current.contents = canvas.toDataURL('image/' + format, document.getElementById('jform_dpemoji_quality').value / 100);
		window.dispatchEvent(new Event('mediaManager.history.point'));
		canvas.remove();
	};
	const init = (image) => {
		if (mainCanvas) {
			return;
		}
		const parent = document.createElement('div');
		parent.className = 'plg-media-action-dpemoji-panel';
		image.parentNode.insertBefore(parent, image);
		mainCanvas = document.createElement('canvas');
		mainCanvas.className = parent.className + '__maincanvas';
		parent.appendChild(mainCanvas);
		const setup = () => {
			parent.style.width = image.width + 'px';
			mainCanvas.width = image.width;
			mainCanvas.height = image.height;
			mainCanvas.getContext('2d').drawImage(image, 0, 0, image.width, image.height);
		};
		image.addEventListener('load', setup);
		setup();
		mainCanvas.addEventListener('drop', (event) => {
			if (!event.dataTransfer.getData('emoji')) {
				return;
			}
			event.preventDefault();
			const size = parseInt(document.getElementById('jform_dpemoji_size').value);
			const canvas = document.createElement('canvas');
			canvas.dpEmoji = event.dataTransfer.getData('emoji');
			canvas.className = 'dp-emoji-canvas dp-emoji-' + canvas.dpEmoji.codePointAt(0).toString(16);
			canvas.dpHasControls = false;
			canvas.style.left = (event.offsetX - (size / 2)) + 'px';
			canvas.style.top = (event.offsetY - (size / 2)) + 'px';
			canvas.dpResizeAction = false;
			draw(canvas, size, false);
			parent.appendChild(canvas);
			canvas.addEventListener('mousedown', (e) => {
				canvas.dpResizeAction = 'drag';
				canvas.dpStartPoint = { x: e.clientX, y: e.clientY };
				canvas.dpStartStyle = { x: parseInt(canvas.style.left), y: parseInt(canvas.style.top), size: canvas.width };
				const threshold = 20;
				const rect = canvas.getBoundingClientRect();
				if (Math.abs(e.clientX - rect.left) < threshold && Math.abs(e.clientY - rect.top) < threshold) {
					canvas.dpResizeAction = 'topleft';
					return;
				}
				if (Math.abs(e.clientX - rect.right) < threshold && Math.abs(e.clientY - rect.top) < threshold) {
					canvas.dpResizeAction = 'topright';
					return;
				}
				if (Math.abs(e.clientX - rect.left) < threshold && Math.abs(e.clientY - rect.bottom) < threshold) {
					canvas.dpResizeAction = 'bottomleft';
					return;
				}
				if (Math.abs(e.clientX - rect.right) < threshold && Math.abs(e.clientY - rect.bottom) < threshold) {
					canvas.dpResizeAction = 'bottomright';
				}
			});
			canvas.addEventListener('mousemove', (e) => {
				if (!canvas.dpResizeAction && canvas.dpHasControls) {
					return;
				}
				if (!canvas.dpResizeAction) {
					draw(canvas, canvas.width, true);
					return;
				}
				const rect = mainCanvas.getBoundingClientRect();
				if (canvas.dpResizeAction === 'drag') {
					const left = canvas.dpStartStyle.x + e.clientX - canvas.dpStartPoint.x;
					if (left < 0 || left + canvas.dpStartStyle.size > rect.right - rect.left) {
						return;
					}
					const top = canvas.dpStartStyle.y + e.clientY - canvas.dpStartPoint.y;
					if (top < 0 || top + canvas.dpStartStyle.size > rect.bottom - rect.top) {
						return;
					}
					canvas.style.left = left + 'px';
					canvas.style.top = top + 'px';
					return;
				}
				let diff = Math.max(Math.abs(e.clientX - canvas.dpStartPoint.x), Math.abs(e.clientY - canvas.dpStartPoint.y));
				if ((canvas.dpResizeAction === 'topleft' && e.clientX > canvas.dpStartPoint.x && e.clientY > canvas.dpStartPoint.y)
					|| (canvas.dpResizeAction === 'topright' && e.clientX < canvas.dpStartPoint.x && e.clientY > canvas.dpStartPoint.y)
					|| (canvas.dpResizeAction === 'bottomleft' && e.clientX > canvas.dpStartPoint.x && e.clientY < canvas.dpStartPoint.y)
					|| (canvas.dpResizeAction === 'bottomright' && e.clientX < canvas.dpStartPoint.x && e.clientY < canvas.dpStartPoint.y)) {
					diff = -1 * diff;
				}
				if (canvas.dpStartStyle.x - diff < 0 || canvas.dpStartStyle.x + canvas.dpStartStyle.size + diff > rect.right - rect.left) {
					return;
				}
				if (canvas.dpStartStyle.y - diff < 0 || canvas.dpStartStyle.y + canvas.dpStartStyle.size + diff > rect.bottom - rect.top) {
					return;
				}
				canvas.style.left = (canvas.dpStartStyle.x - diff) + 'px';
				canvas.style.top = (canvas.dpStartStyle.y - diff) + 'px';
				draw(canvas, canvas.dpStartStyle.size + (diff * 2), true);
			});
			canvas.addEventListener('mouseup', (e) => {
				if (canvas.dpResizeAction) {
					draw(canvas, canvas.width, false);
					save(image);
				}
				canvas.dpResizeAction = false;
			});
			canvas.addEventListener('mouseenter', (e) => {
				draw(canvas, canvas.width, true);
				canvas.dpResizeAction = false;
			});
			canvas.addEventListener('mouseleave', (e) => {
				draw(canvas, canvas.width, false);
				canvas.dpResizeAction = false;
			});
			canvasList.push(canvas);
			save(image);
		});
		mainCanvas.addEventListener('dragover', (event) => event.preventDefault());
		Array.from(document.querySelectorAll('.plg-media-action-dpemoji-icons .dp-icon')).forEach((icon) => {
			icon.addEventListener('dragstart', (event) => event.dataTransfer.setData('emoji', icon.textContent));
		});
		if (!Joomla.getOptions('DPEmoji.presets')) {
			return;
		}
		document.getElementById('jform_dpemoji_presets').addEventListener('change', ({ target }) => {
			if (!target.value) {
				return;
			}
			Object.entries(JSON.parse(target.value)).forEach((value) => {
				const input = document.getElementById('jform_dpemoji_' + value[0]);
				if (!input) {
					return;
				}
				input.value = value[1];
			});
		});
	};
	window.addEventListener('media-manager-edit-init', () => {
		Joomla.MediaManager.Edit.plugins.dpemoji = {
			Activate(image) {
				return new Promise((resolve, reject) => {
					if (!!window.CanvasRenderingContext2D) {
						image.style.display = 'none';
						init(image);
						resolve();
						return;
					}
					Joomla.renderMessages({ error: [Joomla.Text._('PLG_MEDIA-ACTION_DPEMOJI_MESSAGE_NO_BROWSER_SUPPORT')] });
					reject();
				});
			},
			Deactivate(image) {
				return new Promise((resolve ) => {
					if (mainCanvas) {
						mainCanvas.parentElement.remove();
						mainCanvas = null;
					}
					canvasList.forEach((canvas) => canvas.remove());
					canvasList = [];
					image.style.display = 'unset';
					resolve();
				});
			},
		};
	}, { once: true });
}());
