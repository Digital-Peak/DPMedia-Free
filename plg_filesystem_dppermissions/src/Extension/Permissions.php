<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPPermissions\Extension;

use Exception;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Component\Media\Administrator\Event\FetchMediaItemEvent;
use Joomla\Component\Media\Administrator\Event\FetchMediaItemsEvent;
use Joomla\Component\Media\Administrator\Event\MediaProviderEvent;
use Joomla\Component\Users\Administrator\Helper\UsersHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use stdClass;

class Permissions extends CMSPlugin implements SubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			'onSetupProviders'               => 'setupProviders',
			'onFetchMediaItems'              => 'adaptFiles',
			'onFetchMediaItem'               => 'adaptFile',
			'onAjaxSaveGroupsPathPermission' => 'saveGroups',
			'onAjaxGetGroupsPathPermissions' => 'getGroups'
		];
	}

	/** @var CMSApplication */
	protected $app;

	/** @var DatabaseInterface */
	protected $db;

	protected $autoloadLanguage = true;

	public function setupProviders(MediaProviderEvent $event)
	{
		if ($this->app->getDocument()->getType() !== 'html' || !$this->canSetPermissions()) {
			return;
		}

		HTMLHelper::_('behavior.core');
		HTMLHelper::_('script', 'plg_filesystem_dppermissions/plugin/dppermissions.min.js', ['relative' => true, 'version' => 'auto']);
		HTMLHelper::_('stylesheet', 'plg_filesystem_dppermissions/plugin/dppermissions.min.css', ['relative' => true, 'version' => 'auto']);

		$this->app->getDocument()->addScriptOptions('DPPermissions.groups', UsersHelper::getGroups());

		Text::script('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_CLOSE');
		Text::script('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_PERMISSIONS');
		Text::script('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_PERMISSIONS_DESC');
	}

	public function adaptFiles(FetchMediaItemsEvent $event)
	{
		// When global admin then return all to prevent lock out
		if ($this->app->getIdentity()->authorise('core.admin')) {
			return;
		}

		$items = $event->getArgument('items');
		if (!$items) {
			return;
		}

		$dirs = [];
		foreach ($items as $item) {
			$parent = dirname($item->path);
			while ($item->adapter . ':' !== $parent) {
				$dirs[$parent] = new stdClass();
				$parent        = dirname($parent);
			}

			if ($item->type !== 'dir') {
				continue;
			}

			$dirs[$item->path] = $item;
		}

		if (!$dirs) {
			return;
		}

		$query = $this->db->getQuery(true);
		$query->select('path, group_id')->from('#__dppermissions')->where('path in (' . implode(',', $query->bindArray(array_keys($dirs))) . ')');

		$this->db->setQuery($query);

		$permissions = [];
		foreach ($this->db->loadObjectList() as $permission) {
			if (!array_key_exists($permission->path, $permissions)) {
				$permissions[$permission->path] = [];
			}
			$permissions[$permission->path][] = $permission->group_id;
		}

		$userGroups = Access::getGroupsByUser($this->app->getIdentity()->id);
		foreach ($permissions as $path => $groups) {
			foreach ($items as $index => $item) {
				if (strpos($item->path, $path) === 0 && !array_intersect($groups, $userGroups)) {
					unset($items[$index]);
				}
			}
		}

		$event->setArgument('items', $items);
	}

	public function adaptFile(FetchMediaItemEvent $event)
	{
		// When global admin then return all to prevent lock out
		if ($this->app->getIdentity()->authorise('core.admin')) {
			return;
		}

		$item = $event->getArgument('item');
		if (!$item) {
			return;
		}

		$dirs   = [$item->path => $item];
		$parent = dirname($item->path);
		while ($item->adapter . ':' !== $parent) {
			$dirs[$parent] = new stdClass();
			$parent        = dirname($parent);
		}

		$query = $this->db->getQuery(true);
		$query->select('path, group_id')->from('#__dppermissions')->where('path in (' . implode(',', $query->bindArray(array_keys($dirs))) . ')');

		$this->db->setQuery($query);

		$permissions = [];
		foreach ($this->db->loadObjectList() as $permission) {
			if (!array_key_exists($permission->path, $permissions)) {
				$permissions[$permission->path] = [];
			}
			$permissions[$permission->path][] = $permission->group_id;
		}

		$userGroups = Access::getGroupsByUser($this->app->getIdentity()->id);
		foreach ($permissions as $path => $groups) {
			if (strpos($item->path, $path) === 0 && !array_intersect($groups, $userGroups)) {
				$event->removeArgument('item');
			}
		}
	}

	public function saveGroups()
	{
		Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

		if (!$this->canSetPermissions()) {
			throw new Exception('Not allowed', 403);
		}

		$path = $this->app->getInput()->json->getString('path');
		if (!$path) {
			return;
		}
		$path = Path::clean($path, '/');

		$query = $this->db->getQuery(true);
		$query->delete('#__dppermissions')->where('path=:path')->bind(':path', $path);

		$this->db->setQuery($query);
		$this->db->execute();

		$groups = $this->app->getInput()->json->getString('groups');
		if (!$groups) {
			echo new JsonResponse($groups, $this->app->getLanguage()->_('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_SUCCESSFULLY_SAVED_GROUPS'));
			$this->app->close();
			return;
		}

		$query = $this->db->getQuery(true)->insert('#__dppermissions')->columns('path, group_id');
		foreach ($groups as $groupId) {
			$query->values(implode(',', $query->bindArray([$path, $groupId], [ParameterType::STRING, ParameterType::INTEGER])));
		}
		$this->db->setQuery($query);
		$this->db->execute();

		echo new JsonResponse($groups, $this->app->getLanguage()->_('PLG_FILESYSTEM_DPPERMISSIONS_TEXT_SUCCESSFULLY_SAVED_GROUPS'));
		$this->app->close();
	}

	public function getGroups()
	{
		Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

		if (!$this->canSetPermissions()) {
			throw new Exception('Not allowed', 403);
		}

		$path = $this->app->getInput()->getString('path');
		if (!$path) {
			return;
		}
		$path = Path::clean($path, '/');

		$query = $this->db->getQuery(true);
		$query->select('group_id')->from('#__dppermissions')->where('path=:path')->bind(':path', $path);

		$this->db->setQuery($query);

		$groups = array_map(function ($group) {
			return $group['group_id'];
		}, $this->db->loadAssocList());

		echo new JsonResponse($groups);

		$this->app->close();
	}

	private function canSetPermissions()
	{
		// When global admin then return all to prevent lock out
		if ($this->app->getIdentity()->authorise('core.admin')) {
			return true;
		}

		// Check if the current user groups are in the allowed groups
		$userGroups = Access::getGroupsByUser($this->app->getIdentity()->id);
		return count(array_intersect($userGroups, $this->params->get('allowed_groups', []))) > 0;
	}
}
