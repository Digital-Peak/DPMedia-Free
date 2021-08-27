<?php
/**
 * @package   DPMedia
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Filesystem\DPPixabay\Adapter;

use DigitalPeak\Library\DPMedia\Adapter\Adapter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Media\Administrator\Exception\FileNotFoundException;

/**
 * Read only Pixabay adapter for Joomla 4 media manager.
 */
class PixabayAdapter extends Adapter
{
	protected function fetchFile(string $path = '/'): \stdClass
	{
		try {
			$response = $this->callAPI(['id' => $this->getId($path)]);

			if ($response->dp->body == 'Not Found' || empty($response->hits)) {
				throw new FileNotFoundException($response->dp->body);
			}

			return $this->getFileInfo($response->hits[0], $this->getPath(dirname($path)));
		} catch (\Exception $e) {
			if ($e->getCode() === 404) {
				throw new FileNotFoundException($e->getMessage());
			}

			throw $e;
		}
	}

	protected function fetchFiles(string $path = '/'): array
	{
		if (pathinfo($path, PATHINFO_EXTENSION)) {
			return [$this->getFile($path)];
		}

		$path = $this->getPath($path);

		$params = ['q' => $this->getConfig()->get('search_query')];

		$pageSize = 200;
		$limit    = $this->getConfig()->get('result_limit', 300);

		$data = [];
		$page = 0;
		while (($page * $pageSize) < $limit) {
			// Set the actual page to fetch
			$params['page'] = $page + 1;

			// Determine the result set size
			$params['per_page'] = ($limit - ($pageSize * $page)) < $pageSize ? $limit % $pageSize : $pageSize;

			$response = $this->callAPI($params);
			foreach ($response->hits as $file) {
				$data[] = $this->getFileInfo($file, $path);
			}

			$page++;

			// Break when we reached the limit
			if (($response->total - ($page * $pageSize)) <= 0) {
				break;
			}
		}

		return $data;
	}

	protected function fetchSearch(string $path, string $needle, bool $recursive = false): array
	{
		$path = $this->getPath($path);

		$params = ['q' => $needle];

		$pageSize = 200;
		$limit    = $this->getConfig()->get('result_limit', 300);

		$data = [];
		$page = 0;
		while (($page * $pageSize) < $limit) {
			// Set the actual page to fetch
			$params['page'] = $page + 1;

			// Determine the result set size
			$params['per_page'] = ($limit - ($pageSize * $page)) < $pageSize ? $limit % $pageSize : $pageSize;

			$response = $this->callAPI($params);
			foreach ($response->hits as $file) {
				$data[] = $this->getFileInfo($file, $path);
			}

			$page++;

			// Break when we reached the limit
			if (($response->total - ($page * $pageSize)) <= 0) {
				break;
			}
		}

		return $data;

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
	protected function callAPI($params = [])
	{
		$params['key'] = $this->getConfig()->get('api_key');
		$response      = $this->http->get('https://pixabay.com/api/' . ($params ? '?' . http_build_query($params) : ''));

		if ($response->dp->info->http_code >= 400) {
			throw new \Exception($response->code, $response->dp->info->http_code);
		}

		return $response;
	}

	/**
	 * Extract file information from an entry of Pixabay.
	 *
	 * @param \stdClass $fileEntry
	 * @param string $path
	 *
	 * @return \stdClass
	 */
	private function getFileInfo(\stdClass $fileEntry, string $path): \stdClass
	{
		$file             = new \stdClass();
		$file->name       = trim(str_replace(['-', '_', $fileEntry->id], ' ', ucfirst(basename($fileEntry->pageURL))));
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

		if (!empty($fileEntry->imageSize)) {
			$file->size = $fileEntry->imageSize;
		}

		if (!empty($fileEntry->imageWidth)) {
			$file->width = $fileEntry->imageWidth;
		}
		if (!empty($fileEntry->imageHeight)) {
			$file->height = $fileEntry->imageHeight;
		}

		if (!empty($fileEntry->previewURL)) {
			$file->thumb_path = $fileEntry->previewURL;
		}

		if (!empty($fileEntry->largeImageURL)) {
			$file->url = $fileEntry->largeImageURL;
		}

		return $file;
	}

	public function getResource(string $path)
	{
		throw new \Exception('Not implemented by the Pixabay API.');
	}

	public function createFolder(string $name, string $path): string
	{
		throw new \Exception('Not implemented by the Pixabay API.');
	}

	public function createFile(string $name, string $path, $data): string
	{
		throw new \Exception('Not implemented by the Pixabay API.');
	}

	public function updateFile(string $name, string $path, $data)
	{
		throw new \Exception('Not implemented by the Pixabay API.');
	}

	public function delete(string $path)
	{
		throw new \Exception('Not implemented by the Pixabay API.');
	}

	public function move(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		throw new \Exception('Not implemented by the Pixabay API.');
	}

	public function copy(string $sourcePath, string $destinationPath, bool $force = false): string
	{
		throw new \Exception('Not implemented by the Pixabay API.');
	}
}
