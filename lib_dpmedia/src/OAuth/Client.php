<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\OAuth;

use DigitalPeak\ThinHTTP\ClientInterface;
use Joomla\Utilities\ArrayHelper;

/**
 * Authentication class for OAuth 1 requests.
 */
class Client
{
	/**
	 * Calls the given service with the parameters by using the http instance.
	 * It creates a proper signature and and makes a HTTP get request with the needed auth headers.
	 * The body parameters can be either a string or an array. When array, then the $parameters are merged in as well.
	 * The response from the service is returned.
	 */
	public static function call(
		string $service,
		array $parameters,
		ClientInterface $http,
		string $method = 'get',
		string $bodyParameters = '',
		array $headers = []
	): \stdClass {
		// Setup parameters
		$parameters = array_merge($parameters, [
			'oauth_nonce'            => sha1(uniqid('', true) . time() . $service),
			'oauth_timestamp'        => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version'          => '1.0'
		]);
		uksort($parameters, strcmp(...));

		// The key to sign
		$key = $parameters['oauth_consumer_secret'] . '&' . $parameters['oauth_token_secret'];

		// Unset the secrets so we do not send it as parameter
		unset($parameters['oauth_consumer_secret']);
		unset($parameters['oauth_token_secret']);

		// Build the base string
		$baseStringParams = array_map(static fn ($k, $p): string => $k . '=' . rawurlencode((string)$p), array_keys($parameters), array_values($parameters));
		$baseStr          = strtoupper($method) . '&' . rawurlencode($service) . '&' . rawurlencode(implode('&', $baseStringParams));

		// Create the signature
		$parameters['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $baseStr, $key, true)));

		// Compile the auth header
		$authParameters = array_filter($parameters, static fn ($k): bool => str_starts_with((string)$k, 'oauth'), ARRAY_FILTER_USE_KEY);
		uksort($authParameters, strcmp(...));
		$headers[] = 'Authorization: OAuth ' . ArrayHelper::toString($authParameters, '=', ',');

		// Extract the parameters for the url
		$urlParameters = array_filter($parameters, static fn ($k): bool => !str_starts_with((string)$k, 'oauth'), ARRAY_FILTER_USE_KEY);
		$urlParameters = array_map(static fn ($k, $p): string => $k . '=' . rawurlencode((string)$p), array_keys($urlParameters), array_values($urlParameters));

		// Make the request
		return $http->request(
			$service . ($urlParameters !== [] ? '?' . implode('&', $urlParameters) : ''),
			$bodyParameters !== '' && $bodyParameters !== '0' ? $bodyParameters : null,
			null,
			null,
			$headers,
			[],
			$method
		);
	}
}
