<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Extension;

defined('_JEXEC') or die;

use DigitalPeak\Library\DPMedia\Adapter\CacheFactoryAwareInterface;
use DigitalPeak\Library\DPMedia\Adapter\MimeTypeMapping;
use DigitalPeak\ThinHTTP;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Media\Administrator\Event\MediaProviderEvent;
use Joomla\Component\Media\Administrator\Event\OAuthCallbackEvent;
use Joomla\Component\Media\Administrator\Provider\ProviderInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class Media extends CMSPlugin implements SubscriberInterface, ProviderInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			'onSetupProviders'          => 'setupProviders',
			'onFileSystemOAuthCallback' => 'storeRefreshToken'
		];
	}

	protected $autoloadLanguage = true;
	protected $app;
	protected $db;
	protected $http;
	protected $mimeTypeMapping;
	protected $cacheFactory;
	protected $name;

	public function __construct(
		$subject,
		ThinHTTP $http,
		MimeTypeMapping $mimeTypeMapping,
		CacheControllerFactoryInterface $cacheFactory,
		$config = []
	) {
		parent::__construct($subject, $config);

		$this->http            = $http;
		$this->mimeTypeMapping = $mimeTypeMapping;
		$this->cacheFactory    = $cacheFactory;

		$this->name = strtolower((new \ReflectionClass($this))->getShortName());
	}

	/**
	 * Get a new folder configuration on an auth callback for the given uri. A new access token
	 * can be fetched here.
	 *
	 * @param string $uri
	 * @param array $config
	 *
	 * @return \stdClass
	 */
	protected function getFolderConfiguration(string $uri, array $params): ?\stdClass
	{
		return null;
	}

	public function setupProviders(MediaProviderEvent $event)
	{
		$event->getProviderManager()->registerProvider($this);
	}

	public function storeRefreshToken(OAuthCallbackEvent $event)
	{
		$uri = !isset($_SERVER['HTTP_HOST']) ? Uri::getInstance('http://localhost') : Uri::getInstance();
		if (filter_var($uri->getHost(), FILTER_VALIDATE_IP)) {
			$uri->setHost('localhost');
		}
		$uri = $uri->toString(['scheme', 'host', 'port', 'path']) . '?option=com_media&task=plugin.oauthcallback&plugin=dp' . $this->name;

		$folders = $this->params->get('folders', new \stdClass());

		// Folders can be an empty array when all cleared
		if (is_array($folders)) {
			$folders = new \stdClass();
		}

		$params = [];
		foreach ($_COOKIE as $key => $value) {
			if (strpos($key, 'dp_') !== 0) {
				continue;
			}
			$params[str_replace('dp_', '', $key)] = $value;
		}
		$folder = $this->getFolderConfiguration($uri, $params);

		for ($i = 10; $i < 1000; $i++) {
			if (isset($folders->{'__field' . $i})) {
				continue;
			}
			$folders->{'__field' . $i} = $folder;
			break;
		}
		$this->params->set('folders', $folders);

		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__extensions'))
			->set($this->db->quoteName('params') . '=' . $this->db->quote($this->params->toString()))
			->where($this->db->quoteName('element') . '=' . $this->db->quote('dp' . $this->name))
			->where($this->db->quoteName('type') . '=' . $this->db->quote('plugin'));
		$this->db->setQuery($query);
		$this->db->execute();

		$url = $_COOKIE['dp_url'];

		// Clear cookies
		foreach ($params as $key => $value) {
			setcookie('dp_' . $key, '', time() - 3600);
		}

		$this->app->redirect($url);
	}

	public function getID()
	{
		return $this->_name;
	}

	public function getDisplayName()
	{
		return $this->app->getLanguage()->_('PLG_FILESYSTEM_DP' . strtoupper($this->name)  . '_DEFAULT_NAME');
	}

	public function getAdapters()
	{
		$className = 'DigitalPeak\Plugin\Filesystem\DP' . ucfirst($this->name) . '\Adapter\\' . ucfirst($this->name) . 'Adapter';
		$className .= class_exists($className . 'Writable') ? 'Writable' : '';

		$folders = $this->params->get('folders');
		if (!$folders) {
			return [];
		}

		$data = [];
		foreach ($folders as $folder) {
			$adapter = new $className(new Registry($folder), $this->http, $this->mimeTypeMapping, $this->db, $this->app);

			if ($adapter instanceof CacheFactoryAwareInterface) {
				$adapter->setCacheFactory($this->cacheFactory);
			}

			$data[$adapter->getAdapterName()] = $adapter;
		}

		return $data;
	}
}
