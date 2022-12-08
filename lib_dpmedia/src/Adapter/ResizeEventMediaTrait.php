<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

use Joomla\Event\Event;

/**
 * Media resize support.
 */
trait ResizeEventMediaTrait
{
	use ResizeMediaTrait;

	/**
	 * Returns the adapters to resize on.
	 *
	 * @return array
	 */
	abstract public function getAdapters();

	/**
	 * Returns the id of the current instance.
	 *
	 * @return string
	 */
	abstract public function getID();

	/**
	 * Helper function for save events to automatically resize when required
	 */
	public function beforeSave(Event $event)
	{
		if ($event->getArgument(0) != 'com_media.file') {
			return;
		}

		$file = $event->getArgument(1);

		/** @var Adapter $adapter */
		$adapter = array_reduce($this->getAdapters(), function ($found, Adapter $adapter) use ($file) {
			return $this->getID() . '-' . $adapter->getAdapterName() == $file->adapter ? $adapter : $found;
		});

		if (!$adapter || $adapter->getConfig()->get('force_resize', '0') != '1') {
			return;
		}

		$this->resizeImage(
			$file,
			$adapter->getConfig()->get('force_width', 0),
			$adapter->getConfig()->get('force_height', 0),
			$adapter->getConfig()->get('force_quality', 80),
			$adapter->getConfig()->get('force_aspect_ratio', 0)
		);
	}
}
