<?php
/**
 * Image Proxy Controller
 *
 * Proxies external images (NOAA, NASA) through same-origin endpoints
 * to comply with Nextcloud CSP img-src restrictions.
 *
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\Controller;

use OCA\SpaceWeather\Service\SpaceWeatherService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ImageController extends Controller {

	/** URL map: proxy key => live external image URL */
	private const IMAGE_URLS = [
		// Aurora (NOAA SWPC) — verified 2026-05-22
		'aurora_north' => 'https://services.swpc.noaa.gov/images/animations/ovation/north/latest.jpg',
		'aurora_south' => 'https://services.swpc.noaa.gov/images/animations/ovation/south/latest.jpg',

		// D-RAP (NOAA SWPC) — verified 2026-05-22
		'drap_global'  => 'https://services.swpc.noaa.gov/images/animations/d-rap/global/latest.png',

		// SDO solar imagery (NASA) — current filename format verified 2026-05-22
		'sdo_193'        => 'https://sdo.gsfc.nasa.gov/assets/img/latest/f_094_335_193_512.jpg',
		'sdo_304'        => 'https://sdo.gsfc.nasa.gov/assets/img/latest/f_304_211_171_512.jpg',
		'sdo_171'        => 'https://sdo.gsfc.nasa.gov/assets/img/latest/f_211_193_171_512.jpg',
		'sdo_211'        => 'https://sdo.gsfc.nasa.gov/assets/img/latest/f_211_193_171_512.jpg',
		'sdo_magnetogram' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/f_HMImag_171_512.jpg',

		// GOES satellites (NOAA) — verified 2026-05-22
		'goes16_fd' => 'https://cdn.star.nesdis.noaa.gov/GOES16/ABI/FD/GEOCOLOR/678x678.jpg',
		'goes18_fd' => 'https://cdn.star.nesdis.noaa.gov/GOES18/ABI/FD/GEOCOLOR/678x678.jpg',
		'enlil'        => 'https://iswa.ccmc.gsfc.nasa.gov/iswa_data_tree/model/heliosphere/wsa-enlil-cone/animation-cme-density/',

	];

    public function __construct(
        string $appName,
        IRequest $request,
        protected LoggerInterface $logger,
        private SpaceWeatherService $weatherService,
    ) {
		parent::__construct($appName, $request);
	}

	/**
	 * Proxy an external image through a same-origin endpoint.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function getImage(string $key): DataDownloadResponse|DataResponse {
		if (!isset(self::IMAGE_URLS[$key])) {
			return new DataResponse(['error' => 'Unknown image key: ' . $key], 404);
		}

		try {
			$url = self::IMAGE_URLS[$key];
			$body = $this->fetchImage($url, $key);

			if ($body === false || $body === '') {
				return new DataResponse(['error' => 'Failed to fetch image from source'], 502);
			}

			$contentType = $this->detectMimeType($body);
			$ext = ($contentType === 'image/png') ? 'png' : 'jpg';
			$filename = "{$key}.{$ext}";

			$resp = new DataDownloadResponse($body, $filename, $contentType);
			$resp->addHeader('Content-Disposition', 'inline; filename="' . $filename . '"');
			$resp->addHeader('Cache-Control', 'public, max-age=300');
			$resp->addHeader('Pragma', 'public');

			return $resp;
		} catch (\Throwable $e) {
			$this->logger->error('Image proxy error for key ' . $key . ': ' . $e->getMessage());
			return new DataResponse(['error' => 'Failed to fetch image'], 502);
		}
	}

	/**
	 * Fetch an image from an external URL with SSL and timeout configuration.
	 */
	private function fetchImage(string $url, ?string $key = null): string|false {
        if ($key === 'enlil') {
            $enlilUrl = $this->resolveEnlilUrl();
            $opts = [
                'http' => [
                    'timeout'    => 15,
                    'user_agent' => 'Nextcloud-SpaceWeather/1.0',
                    'follow_location' => 1,
                    'max_redirects'   => 3,
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ];
            $context = stream_context_create($opts);
            return @file_get_contents($enlilUrl, false, $context);
        }

		$opts = [
			'http' => [
				'timeout'    => 15,
				'user_agent' => 'Nextcloud-SpaceWeather/1.0',
				'follow_location' => 1,
				'max_redirects'   => 3,
			],
			'ssl' => [
				'verify_peer'      => false,
				'verify_peer_name' => false,
			],
		];
		$context = stream_context_create($opts);
		return @file_get_contents($url, false, $context);
	}

	/**
	 * Detect MIME type from magic bytes in the image data.
	 */
	private function detectMimeType(string $body): string {
		// PNG magic: 89 50 4E 47
		if (str_starts_with($body, "\x89PNG")) {
			return 'image/png';
		}
		// JPEG magic: FF D8 FF
		if (str_starts_with($body, "\xFF\xD8\xFF")) {
			return 'image/jpeg';
		}
		// GIF magic: GIF89a or GIF87a
		if (str_starts_with($body, 'GIF8')) {
			return 'image/gif';
		}
		// Fallback
		return 'image/jpeg';
	}

    private function resolveEnlilUrl(): string {
        try {
            $year = date("Y");
            $month = date("m");
            $baseUrl = "https://iswa.ccmc.gsfc.nasa.gov/iswa_data_tree/model/heliosphere/wsa-enlil-cone/animation-cme-density/";
            $dirUrl = $baseUrl . $year . "/" . $month . "/";
            $ctx = stream_context_create(["http" => ["timeout" => 10, "user_agent" => "Nextcloud-SpaceWeather/1.0"]]);
            $html = @file_get_contents($dirUrl, false, $ctx);
            if ($html === false) {
                return "https://services.swpc.noaa.gov/images/animations/enlil/latest.jpg";
            }
            preg_match_all('/href="([^"]+\.gif)"/i', $html, $matches);
            $files = $matches[1] ?? [];
            if (empty($files)) {
                return "https://services.swpc.noaa.gov/images/animations/enlil/latest.jpg";
            }
            rsort($files);
            return $dirUrl . $files[0];
        } catch (\Throwable $e) {
            return "https://services.swpc.noaa.gov/images/animations/enlil/latest.jpg";
        }
    }

    /**
     * Proxy a specific WSA-Enlil animation frame through same-origin.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function getEnlilFrame(int $index): DataDownloadResponse|DataResponse {
        try {
            $manifest = $this->weatherService->getEnlilAnimationFrames();
            $frames = $manifest['frames'] ?? [];

            if (empty($frames) || $index < 0 || $index >= count($frames)) {
                return new DataResponse(['error' => 'Frame index out of range'], 404);
            }

            $url = $frames[$index];
            $opts = [
                'http' => [
                    'timeout'    => 15,
                    'user_agent' => 'Nextcloud-SpaceWeather/1.0',
                    'follow_location' => 1,
                    'max_redirects'   => 3,
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ];
            $context = stream_context_create($opts);
            $body = @file_get_contents($url, false, $context);

            if ($body === false || $body === '') {
                return new DataResponse(['error' => 'Failed to fetch frame from source'], 502);
            }

            $contentType = $this->detectMimeType($body);
            $ext = ($contentType === 'image/png') ? 'png' : 'jpg';
            $resp = new DataDownloadResponse($body, "enlil_frame_{$index}.{$ext}", $contentType);
            $resp->addHeader('Content-Disposition', 'inline; filename="enlil_frame_' . $index . '.' . $ext . '"');
            $resp->addHeader('Cache-Control', 'public, max-age=300');
            $resp->addHeader('Pragma', 'public');

            return $resp;
        } catch (\Throwable $e) {
            $this->logger->error('Enlil frame proxy error: ' . $e->getMessage());
            return new DataResponse(['error' => 'Failed to fetch frame'], 502);
        }
    }

}