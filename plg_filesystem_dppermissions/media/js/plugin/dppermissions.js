/**
 * @package   DPMedia
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	function getGroups(path, callback) {
		let url = Joomla.getOptions('system.paths').base;
		url += '/index.php?option=com_ajax&plugin=getGroupsPathPermissions&group=filesystem&format=json';
		url += '&path=' + path;
		url += '&' + Joomla.getOptions('csrf.token', '') + '=1';
		Joomla.request({
			url: url,
			method: 'GET',
			onSuccess: (resp) => {
				const response = JSON.parse(resp);
				callback(response.data);
			}
		});
	}
	function save(path, groups, target) {
		let url = Joomla.getOptions('system.paths').base;
		url += '/index.php?option=com_ajax&plugin=saveGroupsPathPermission&group=filesystem&format=json&';
		url += Joomla.getOptions('csrf.token', '') + '=1';
		Joomla.request({
			url: url,
			method: 'POST',
			data: JSON.stringify({ groups: groups, path: path }),
			headers: { 'Content-Type': 'application/json' },
			onSuccess: (resp) => {
				const response = JSON.parse(resp);
				if (response.data.length > 0 && !target.querySelector('.plg-permissions-set')) {
					const identifier = document.createElement('span');
					identifier.classList.add('plg-permissions-set');
					identifier.classList.add('icon-lock');
					target.appendChild(identifier);
				}
				if (response.data.length === 0 && target.querySelector('.plg-permissions-set')) {
					target.querySelector('.plg-permissions-set').remove();
				}
				const infoBox = document.querySelector('.dp-modal__info-box');
				if (!infoBox || !response.message) {
					return;
				}
				infoBox.innerHTML = response.message;
				infoBox.classList.remove('dp-modal__info-box_hidden');
			}
		});
	}
	let modal;
	function open(target) {
		if (typeof tingle == 'object') {
			createModal(target);
			return;
		}
		const resource = document.createElement('script');
		resource.type = 'text/javascript';
		resource.src = Joomla.getOptions('system.paths').root + '/media/plg_filesystem_dppermissions/js/vendor/tingle.min.js';
		resource.addEventListener('load', () => createModal(target));
		document.head.appendChild(resource);
		const l = document.createElement('link');
		l.rel = 'stylesheet';
		l.href = Joomla.getOptions('system.paths').root + '/media/plg_filesystem_dppermissions/css/vendor/tingle.min.css';
		document.head.appendChild(l);
	}
	function createModal(target) {
		if (modal !== undefined) {
			modal.destroy();
		}
		const name = target.parentElement.querySelector('.media-browser-item-info').innerHTML;
		const urlParams = new URLSearchParams(window.location.search);
		const path = urlParams.get('path') + '/' + name;
		getGroups(path, (groups) => {
			modal = new tingle.modal({
				footer: false,
				stickyFooter: false,
				closeMethods: ['overlay', 'button', 'escape'],
				cssClass: ['plg-dppermissions-modal'],
				closeLabel: Joomla.Text._('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_CLOSE'),
				onOpen: () => {
					const options = Array.from(document.querySelectorAll('select[name="groups"] option'));
					groups.forEach((groupId) => {
						const option = options.find((o) => o.value == groupId);
						if (!option) {
							return;
						}
						option.selected = true;
					});
				}
			});
			let content = '<p class="dp-modal__info-box dp-modal__info-box_hidden alert alert-success"></p>';
			content += '<h1>' + Joomla.Text._('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_PERMISSIONS').replace('%s', name) + '</h1>';
			content += '<p>' + Joomla.Text._('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_PERMISSIONS_DESC') + '</p>';
			content += '<select name="groups" multiple autofocus>';
			Object.entries(Joomla.getOptions('DPPermissions.groups')).forEach((group) => {
				content += '<option value="' + group[1].id + '">' + group[1].text + '</option>';
			});
			content += '</select>';
			modal.setContent('<div class="dp-modal">' + content + '</div>');
			modal.open();
			document.querySelector('select[name="groups"]').addEventListener('change', (e) => {
				save(path, Array.from(e.target.selectedOptions).map((o) => o.value), target);
			});
		});
	}
	document.addEventListener('DOMContentLoaded', () => {
		const media = document.getElementById('com-media');
		if (!media) {
			return;
		}
		const callback = (mutationsList, observer) => {
			Array.from(mutationsList).forEach((mutation) => {
				if (mutation.target.classList.contains('media-browser-items')) {
					const urlParams = new URLSearchParams(window.location.search);
					Array.from(mutation.addedNodes).forEach((item) => {
						const directory = item.querySelector('.media-browser-item-directory .media-browser-item-info');
						if (!directory) {
							return;
						}
						getGroups(
							urlParams.get('path') + '/' + directory.innerHTML,
							(groups) => {
								if (groups.length === 0) {
									return;
								}
								const identifier = document.createElement('span');
								identifier.classList.add('plg-permissions-set');
								identifier.classList.add('icon-lock');
								item.appendChild(identifier);
							}
						);
					});
				}
				if (mutation.target.classList.contains('media-browser-actions')) {
					Array.from(mutation.addedNodes).forEach((actions) => {
						if (typeof actions.querySelector === 'undefined'
							|| !actions.parentElement.parentElement.classList.contains('media-browser-item-directory')) {
							return;
						}
						const list = actions.querySelector('ul');
						if (!list) {
							return;
						}
						const actionContainer = document.createElement('li');
						const action = document.createElement('button');
						action.classList.add('action-permissions');
						action.innerHTML = '<span class="image-browser-action icon-lock" aria-hidden="true"/>';
						action.addEventListener('click', (e) => {
							e.preventDefault();
							open(mutation.target.parentElement);
							return false;
						});
						actionContainer.appendChild(action);
						list.appendChild(actionContainer);
					});
				}
			});
		};
		const observer = new MutationObserver(callback);
		observer.observe(media, { childList: true, subtree: true });
	});
})();
