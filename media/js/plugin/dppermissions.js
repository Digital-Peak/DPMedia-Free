/**
 * @package   DPMedia
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	class Queue {
		constructor() {
			this.queue = [];
		}
		enqueue(fn) {
			return new Promise((resolve, reject) => {
				this.queue.push({ fn, resolve, reject, });
				this.dequeue();
			});
		}
		dequeue() {
			if (this.workingOnPromise) {
				return false;
			}
			const item = this.queue.shift();
			if (!item) {
				return false;
			}
			try {
				this.workingOnPromise = true;
				item.fn()
					.then((value) => {
						this.workingOnPromise = false;
						item.resolve(value);
						this.dequeue();
					})
					.catch(err => {
						this.workingOnPromise = false;
						item.reject(err);
						this.dequeue();
					});
			} catch (err) {
				this.workingOnPromise = false;
				item.reject(err);
				this.dequeue();
			}
			return true;
		}
	}
	const queue = new Queue();
	function request(args, data) {
		return queue.enqueue(() => {
			return new Promise((resolve, reject) => {
				let url = Joomla.getOptions('system.paths').base;
				url += '/index.php?option=com_ajax&group=filesystem&format=json&' + args;
				url += '&' + Joomla.getOptions('csrf.token', '') + '=1';
				Joomla.request({
					url: url,
					method: !data ? 'GET' : 'POST',
					data: JSON.stringify(data ? data : {}),
					headers: { 'Content-Type': 'application/json' },
					onSuccess: (resp) => {
						const json = JSON.parse(resp);
						const infoBox = document.querySelector('.dp-modal__info-box');
						if (infoBox && json.message) {
							infoBox.innerHTML = json.message;
							infoBox.classList.remove('dp-modal__info-box_hidden');
						}
						resolve(json.data);
					},
					onError: () => reject
				});
			});
		});
	}
	let modal;
	function open(content, className) {
		return new Promise((resolve, reject) => {
			if (typeof tingle === 'object') {
				resolve(createModal(content, className));
				return;
			}
			const resource = document.createElement('script');
			resource.type = 'text/javascript';
			resource.src = Joomla.getOptions('system.paths').root + '/media/lib_dpmedia/js/vendor/tingle.min.js';
			resource.addEventListener('load', () => resolve(createModal(content, className)));
			document.head.appendChild(resource);
			const l = document.createElement('link');
			l.rel = 'stylesheet';
			l.href = Joomla.getOptions('system.paths').root + '/media/lib_dpmedia/css/vendor/tingle.min.css';
			document.head.appendChild(l);
		});
	}
	function createModal(content, className) {
		if (modal !== undefined) {
			modal.destroy();
		}
		modal = new tingle.modal({
			footer: false,
			stickyFooter: false,
			closeMethods: ['overlay', 'button', 'escape'],
			cssClass: ['lib-dpmedia-modal', className],
			closeLabel: Joomla.Text._('LIB_DPMEDIA_TEXT_CLOSE')
		});
		modal.setContent('<div class="dp-modal">' + content + '</div>');
		modal.open();
		return modal;
	}
	document.addEventListener('DOMContentLoaded', () => {
		const media = document.getElementById('com-media');
		if (!media) {
			return;
		}
		const urlParams = new URLSearchParams(window.location.search);
		const intersectionObserver = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) {
					return;
				}
				intersectionObserver.unobserve(entry.target);
				request('path=' + urlParams.get('path') + '/' + entry.target.innerText + '&plugin=getGroupsPathPermissions').then((groups) => {
					if (groups.length === 0) {
						return;
					}
					entry.target.dpMediaGroups = groups;
					const identifier = document.createElement('span');
					identifier.classList.add('plg-permissions-set');
					identifier.classList.add('icon-lock');
					entry.target.after(identifier);
				});
			});
		});
		const observer = new MutationObserver((mutationsList, observer) => {
			Array.from(mutationsList).forEach((mutation) => {
				if (mutation.target.classList.contains('media-browser-items')) {
					Array.from(mutation.addedNodes).forEach((item) => {
						const info = item.querySelector('.media-browser-item-directory .media-browser-item-info');
						if (!info) {
							return;
						}
						info.dpMediaGroups = [];
						info.dataset.dpPath = urlParams.get('path') + '/' + info.innerText;
						intersectionObserver.observe(info);
					});
				}
				if (mutation.target.classList.contains('media-browser-actions')) {
					Array.from(mutation.addedNodes).forEach((actions) => {
						const directory = actions.parentElement.parentElement;
						if (typeof actions.querySelector === 'undefined' || !directory.classList.contains('media-browser-item-directory')) {
							return;
						}
						const list = actions.querySelector('ul');
						if (!list) {
							return;
						}
						const info = directory.querySelector('.media-browser-item-info');
						if (!info) {
							return;
						}
						const actionContainer = document.createElement('li');
						const action = document.createElement('button');
						action.classList.add('action-permissions');
						action.innerHTML = '<span class="image-browser-action icon-lock" aria-hidden="true"/>';
						action.addEventListener('click', (e) => {
							e.preventDefault();
							let content = '<p class="dp-modal__info-box dp-modal__info-box_hidden alert alert-success"></p>';
							content += '<h1>' + Joomla.Text._('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_PERMISSIONS').replace('%s', info.innerText) + '</h1>';
							content += '<p>' + Joomla.Text._('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_PERMISSIONS_DESC') + '</p>';
							content += '<select name="groups" multiple autofocus>';
							Object.entries(Joomla.getOptions('DPPermissions.groups')).forEach((group) => {
								content += '<option value="' + group[1].id + '"';
								if (info.dpMediaGroups.find((groupId) => group[1].id == groupId)) {
									content += ' selected';
								}
								content += '>' + group[1].text + '</option>';
							});
							content += '</select>';
							open(content, 'plg-dppermissions-modal').then(() => {
								document.querySelector('select[name="groups"]').addEventListener('change', (e) => {
									request('plugin=saveGroupsPathPermission', { path: info.dataset.dpPath, groups: Array.from(e.target.selectedOptions).map((o) => o.value) })
										.then((groups) => {
											info.dpMediaGroups = groups;
											if (groups.length > 0 && !directory.querySelector('.plg-permissions-set')) {
												const identifier = document.createElement('span');
												identifier.classList.add('plg-permissions-set');
												identifier.classList.add('icon-lock');
												info.after(identifier);
											}
											if (groups.length === 0 && directory.querySelector('.plg-permissions-set')) {
												directory.querySelector('.plg-permissions-set').remove();
											}
										});
								});
							});
							return false;
						});
						actionContainer.appendChild(action);
						list.appendChild(actionContainer);
					});
				}
			});
		});
		observer.observe(media, { childList: true, subtree: true });
	});
})();
