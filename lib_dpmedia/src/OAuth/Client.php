<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Library\DPMedia\OAuth;

use DigitalPeak\ThinHTTP;
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
	 *
	 * @param string   $service
	 * @param array    $parameters
	 * @param ThinHTTP $http
	 * @param string   $method
	 * @param mixed    $bodyParameters
	 * @param array    $headers
	 *
	 * @return \stdClass
	 */
	public static function call(
		string $service,
		array $parameters,
		ThinHTTP $http,
		string $method = 'get',
		$bodyParameters = null,
		array $headers = []
	): \stdClass {
		// Setup parameters
		$parameters = array_merge($parameters, [
			'oauth_nonce'            => sha1(uniqid('', true) . time() . $service),
			'oauth_timestamp'        => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_version'          => '1.0'
		]);
		uksort($parameters, 'strcmp');

		// The key to sign
		$key = $parameters['oauth_consumer_secret'] . '&' . $parameters['oauth_token_secret'];

		// Unset the secrets so we do not send it as parameter
		unset($parameters['oauth_consumer_secret']);
		unset($parameters['oauth_token_secret']);

		// Build the base string
		$baseStringParams = array_map(fn ($k, $p) => $k . '=' . rawurlencode($p), array_keys($parameters), array_values($parameters));
		$baseStr          = strtoupper($method) . '&' . rawurlencode($service) . '&' . rawurlencode(implode('&', $baseStringParams));

		// Create the signature
		$parameters['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $baseStr, $key, true)));

		// Compile the auth header
		$authParameters = array_filter($parameters, fn ($k) => strpos($k, 'oauth') === 0, ARRAY_FILTER_USE_KEY);
		uksort($authParameters, 'strcmp');
		$headers[] = 'Authorization: OAuth ' . ArrayHelper::toString($authParameters, '=', ',');

		// Extract the parameters for the url
		$urlParameters = array_filter($parameters, fn ($k) => strpos($k, 'oauth') !== 0, ARRAY_FILTER_USE_KEY);
		$urlParameters = array_map(fn ($k, $p) => $k . '=' . rawurlencode($p), array_keys($urlParameters), array_values($urlParameters));

		// Make the request
		return $http->request(
			$service . ($urlParameters ? '?' . implode('&', $urlParameters) : ''),
			$bodyParameters ?: null,
			null,
			null,
			$headers,
			[],
			$method
		);
	}
}