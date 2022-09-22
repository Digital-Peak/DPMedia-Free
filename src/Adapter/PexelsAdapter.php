<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPPexels\Adapter;

use DigitalPeak\Library\DPMedia\Adapter\Adapter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Media\Administrator\Exception\FileNotFoundException;

/**
 * Read only Pexels adapter for Joomla 4 media manager.
 */
class PexelsAdapter extends Adapter
{
	protected function fetchFile(string $path = '/'): \stdClass
	{
		try {
			$response = $this->callAPI('/photos/' . $this->getPathId($path));

			if ($response->dp->body == 'Not Found') {
				throw new FileNotFoundException($response->dp->body);
			}

			return $this->getFileInfo($response, $this->getPath(dirname($path)));
		} catch (\Exception $e) {
			if ($e->getCode() === 404) {
				throw new FileNotFoundException($e->getMessage());
			}

			throw $e;
		}
	}

	protected function fetchFiles(string $path = '/'): array
	{
		if (strpos($path, '.') !== false) {
			return [$this->getFile($path)];
		}

		$path = $this->getPath($path);

		$params = ['query' => $this->getConfig()->get('search_query')];

		$pageSize = 80;
		$limit    = $this->getConfig()->get('result_limit', 300);

		$data = [];
		$page = 0;
		while (($page * $pageSize) < $limit) {
			// Set the actual page to fetch
			$params['page'] = $page + 1;

			// Determine the result set size
			$params['per_page'] = ($limit - ($pageSize * $page)) < $pageSize ? $limit % $pageSize : $pageSize;

			$response = $this->callAPI('/search', $params);
			foreach ($response->photos as $file) {
				$data[] = $this->getFileInfo($file, $path);
			}

			$page++;

			// Break when we reached the limit
			if (($response->total_results - ($page * $pageSize)) <= 0) {
				break;
			}
		}

		return $data;
	}

	protected function fetchSearch(string $path, string $needle, bool $recursive = false): array
	{
		$params   = ['query' => $needle, 'collections' => $this->getPathId($path)];
		$pageSize = 30;
		$limit    = $this->getConfig()->get('result_limit', 300);

		$data = [];
		$page = 0;
		while (($page * $pageSize) < $limit) {
			// Set the actual page to fetch
			$params['page'] = $page + 1;

			// Determine the result set size
			$params['per_page'] = ($limit - ($pageSize * $page)) < $pageSize ? $limit % $pageSize : $pageSize;
			$response           = $this->callAPI('/search/photos', $params);

			foreach (!empty($response->data) ? $response->data : $response->results as $file) {
				$data[] = $this->getFileInfo($file, $path);
			}

			$page++;

			// Break when we reached the limit
			if (($response->dp->headers['x-total'][0] - ($page * $pageSize)) <= 0) {
				break;
			}
		}

		return $data;
	}

	/**
	 * Calls the API on the given endpoint with the given params.
	 * Handles exceptions properly.
	 *
	 * @param $path
	 * @param $params
	 * @param $body
	 * @param $method
	 *
	 * @return \stdclass
	 */
	protected function callAPI($path, $params = [])
	{
		$response = $this->http->get(
			'https://api.pexels.com/v1' . $path . ($params ? '?' . http_build_query($params) : ''),
			null,
			null,
			['Authorization: ' . $this->getConfig()->get('api_key')]
		);

		if ($response->dp->info->http_code >= 400) {
			throw new \Exception($response->code, $response->dp->info->http_code);
		}

		return $response;
	}

	/**
	 * Extract file information from an entry of Pexels.
	 *
	 * @param \stdClass $fileEntry
	 * @param string $path
	 *
	 * @return \stdClass
	 */
	private function getFileInfo(\stdClass $fileEntry, string $path): \stdClass
	{
		$file             = new \stdClass();
		$file->name       = trim(str_replace(['-', '_', $fileEntry->id], ' ', ucfirst(basename($fileEntry->url))));
		$file->extension  = 'jpg';
		$file->mime_type  = $this->mimeTypeMapping->getMimetype($file->extension);
		$file->type       = 'file';
		$file->path       = '/' . $fileEntry->id . '.' . $file->extension;
		$file->size       = 0;
		$file->width      = 0;
		$file->height     = 0;
		$file->thumb_path = '';

		// Date is fixed and in the past
		$createDate = $this->getDate('2020-01-01');
		$updateDate = clone $createDate;

		$file->create_date_formatted   = HTMLHelper::_('date', $createDate, $this->app->getLanguage()->_('DATE_FORMAT_LC5'));
		$file->create_date             = $createDate->format('c');
		$file->modified_date_formatted = HTMLHelper::_('date', $updateDate, $this->app->getLanguage()->_('DATE_FORMAT_LC5'));
		$file->modified_date           = $updateDate->format('c');

		if (!empty($fileEntry->width)) {
			$file->width = $fileEntry->width;
		}
		if (!empty($fileEntry->height)) {
			$file->height = $fileEntry->height;
		}

		if (!empty($fileEntry->src) && !empty($fileEntry->src->tiny)) {
			$file->thumb_path = $fileEntry->src->tiny;
		}

		if (!empty($fileEntry->src) && !empty($fileEntry->src->large2x)) {
			$file->url = $fileEntry->src->large2x;
		}

		return $file;
	}

	public function getResource(string $path)
	{
		throw new \Exception('Not implemented by the Pexels API.');
	}

	public function createFolder(string $name, string $path): string
	{
		throw new \Exception('Not implemented by the Pexels API.');
	}

	public function createFile(string $name, string $path, $data): string
	{
		throw new \Exception('Not implemented by the Pexels API.');
	}

	public function updateFile(string $name, string $path, $data)
	{
		throw new \Exception('Not implemented by the Pexels API.');
	}

	public function delete(string $path)
	{
		throw new \Exception('Not implemented by the Pexels API.');
	}

	public function move(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		throw new \Exception('Not implemented by the Pexels API.');
	}

	public function copy(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		throw new \Exception('Not implemented by the Pexels API.');
	}
}
