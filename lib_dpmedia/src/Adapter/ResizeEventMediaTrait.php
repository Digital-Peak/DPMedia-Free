<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Adapter;

use Joomla\Component\Media\Administrator\Adapter\AdapterInterface;
use Joomla\Event\Event;

/**
 * Media resize support.
 */
trait ResizeEventMediaTrait
{
	use ResizeMediaTrait;

	/**
	 * Returns the adapters to resize on.
	 */
	abstract public function getAdapters(): array;

	/**
	 * Returns the id of the current instance.
	 */
	abstract public function getID();

	/**
	 * Helper function for save events to automatically resize when required
	 */
	public function beforeSave(Event $event): void
	{
		if ($event->getArgument('0') != 'com_media.file') {
			return;
		}

		$file = $event->getArgument('1');

		$adapter = array_reduce(
			$this->getAdapters(),
			fn ($found, AdapterInterface $adapter): AdapterInterface|null => $this->getID() . '-' . $adapter->getAdapterName() == $file->adapter ? $adapter : $found
		);

		if (!$adapter instanceof Adapter || $adapter->getConfig()->get('force_resize', '0') != '1') {
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
