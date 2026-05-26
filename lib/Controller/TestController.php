<?php
declare(strict_types=1);

namespace OCA\SpaceWeather\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class TestController extends Controller {
	public function __construct(string $appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function ping(): DataResponse {
		return new DataResponse([
			'status' => 'ok',
			'message' => 'Space Weather app is running',
			'timestamp' => date('c'),
		]);
	}
}
