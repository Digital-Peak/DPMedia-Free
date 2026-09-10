<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\Extension;

use DigitalPeak\Library\DPMedia\Adapter\CacheFactoryAwareInterface;
use DigitalPeak\Library\DPMedia\Adapter\MimeTypeMapping;
use DigitalPeak\Library\DPMedia\Adapter\ResizeEventMediaTrait;
use DigitalPeak\ThinHTTP\ClientInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Media\Administrator\Adapter\AdapterInterface;
use Joomla\Component\Media\Administrator\Event\MediaProviderEvent;
use Joomla\Component\Media\Administrator\Event\OAuthCallbackEvent;
use Joomla\Component\Media\Administrator\Provider\ProviderInterface;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class Media extends CMSPlugin implements SubscriberInterface, ProviderInterface, DatabaseAwareInterface
{
	use ResizeEventMediaTrait;
	use DatabaseAwareTrait;

	public static function getSubscribedEvents(): array
	{
		return [
			'onSetupProviders'          => 'setupProviders',
			'onFileSystemOAuthCallback' => 'storeRefreshToken',
			'onContentBeforeSave'       => 'beforeSave'
		];
	}

	protected $autoloadLanguage = true;

	protected string $name;

	public function __construct(
		DispatcherInterface $subject,
		protected ClientInterface $http,
		protected MimeTypeMapping $mimeTypeMapping,
		protected CacheControllerFactoryInterface $cacheFactory,
		array $config = []
	) {
		parent::__construct($subject, $config);

		$this->name = strtolower((new \ReflectionClass($this))->getShortName());
	}

	/**
	 * Get a new folder configuration on an auth callback for the given uri. A new access token
	 * can be fetched here.
	 */
	protected function getFolderConfiguration(string $uri, array $params): ?\stdClass
	{
		return null;
	}

	public function setupProviders(MediaProviderEvent $event): void
	{
		$event->getProviderManager()->registerProvider($this);
	}

	public function storeRefreshToken(OAuthCallbackEvent $event): void
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return;
		}

		// OAuth2 based providers complete the flow with a "code" parameter. Make sure this callback
		// was actually initiated by the current browser and not forged/replayed by a third party.
		if ($app->getInput()->getString('code', '') !== '') {
			$state = $app->getInput()->getString('state', '');
			if ($state === '' || !hash_equals((string)($_COOKIE['dp_state'] ?? ''), $state)) {
				setcookie('dp_state', '', ['expires' => time() - 3600]);
				return;
			}
		}

		$uri = isset($_SERVER['HTTP_HOST']) ? Uri::getInstance() : Uri::getInstance('http://localhost');
		if (filter_var($uri->getHost(), FILTER_VALIDATE_IP)) {
			$uri->setHost('localhost');
		}
		$currentHost = $uri->getHost();
		$uri         = $uri->toString(['scheme', 'host', 'port', 'path']) . '?option=com_media&task=plugin.oauthcallback&plugin=dp' . $this->name;

		$folders = $this->params->get('folders', new \stdClass());

		// Folders can be an empty array when all cleared
		if (\is_array($folders)) {
			$folders = new \stdClass();
		}

		$params = [];
		foreach ($_COOKIE as $key => $value) {
			if (!str_starts_with((string)$key, 'dp_')) {
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

		$query = $this->getDatabase()->getQuery(true)
			->update('#__extensions')
			->set('params =' . $this->getDatabase()->quote($this->params->toString()))
			->where('element =' . $this->getDatabase()->quote('dp' . $this->name))
			->where('type =' . $this->getDatabase()->quote('plugin'));
		$this->getDatabase()->setQuery($query);
		$this->getDatabase()->execute();

		$url = $this->getSafeRedirectUrl((string)($_COOKIE['dp_url'] ?? ''), $currentHost);

		// Clear cookies (dp_url and dp_state are cleared here too since they match the "dp_" prefix)
		foreach (array_keys($params) as $key) {
			setcookie('dp_' . $key, '', ['expires' => time() - 3600]);
		}

		$app->redirect($url);
	}

	/**
	 * Returns the given url when it is relative or points to the current host, a safe default otherwise.
	 * Used to avoid an open redirect through the "dp_url" cookie set before the OAuth flow starts.
	 */
	private function getSafeRedirectUrl(string $url, string $currentHost): string
	{
		$default = 'index.php?option=com_media';

		if ($url === '') {
			return $default;
		}

		$parts = parse_url($url);
		if ($parts === false) {
			return $default;
		}

		if (isset($parts['host']) && strcasecmp($parts['host'], $currentHost) !== 0) {
			return $default;
		}

		if (isset($parts['scheme']) && !\in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
			return $default;
		}

		return $url;
	}

	public function getID()
	{
		return $this->_name;
	}

	public function getDisplayName()
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return '';
		}

		return $app->getLanguage()->_('PLG_FILESYSTEM_DP' . strtoupper($this->name) . '_DEFAULT_NAME');
	}

	public function getAdapters(): array
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return [];
		}

		$name = (new \ReflectionClass($this))->getShortName();

		$className = 'DigitalPeak\Plugin\Filesystem\\' . $name . '\Adapter\\' . $name . 'Adapter';
		$className .= class_exists($className . 'Writable') ? 'Writable' : '';

		$folders = $this->params->get('folders');
		if (!$folders) {
			return [];
		}

		if (\is_string($folders)) {
			$folders = json_decode($folders);
		}

		$data = [];
		foreach ($folders as $folder) {
			/** @var AdapterInterface $adapter */
			$adapter = new $className(new Registry($folder), $this->http, $this->mimeTypeMapping, $this->getDatabase(), $app);

			if ($adapter instanceof CacheFactoryAwareInterface) {
				$adapter->setCacheFactory($this->cacheFactory);
			}

			$data[$adapter->getAdapterName()] = $adapter;
		}

		return $data;
	}
}
