<?php

declare(strict_types=1);

namespace Bunny\Stream;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    private string $apiAccessKey;
    private string $streamLibraryId;
    private string $apiBaseUrl;
    private GuzzleClient $httpClient;

    public function __construct(string $apiKey, string $streamLibraryId)
    {
        $this->apiAccessKey = $apiKey;
        $this->streamLibraryId = $streamLibraryId;
        $this->apiBaseUrl = 'https://video.bunnycdn.com/library/';

        $this->httpClient = new GuzzleClient([
            'allow_redirects' => false,
            'http_errors'     => false,
            'base_uri'        => $this->apiBaseUrl . $this->streamLibraryId . '/',
            'headers'         => [
                'AccessKey' => $this->apiAccessKey,
            ],
        ]);
    }

    /**
     * Central method for handling API requests and responses.
     
     *
     * @param $method
     * @param $uri
     * @param $options     Guzzle request options (query, json, body, etc.)
     * @param $failureMsg  Default message f
     * @param $notFoundRef Reference to the resource that was not found
     * @return mixed
     *
     * @throws AuthenticationException
     * @throws VideoNotFoundException|CollectionNotFoundException
     * @throws \Exception  On other non-2xx status codes
     */
    private function requestJson(
        string $method,
        string $uri,
        array $options = [],
        string $failureMsg = 'Request failed.',
        string $notFoundRef = null
    ) {
        try {
            $response = $this->httpClient->request($method, $uri, $options);
        } catch (GuzzleException $e) {
            // Network or other transport issues
            throw new \Exception("Guzzle request error: " . $e->getMessage(), $e->getCode(), $e);
        }

        $code = $response->getStatusCode();
        $body       = (string) $response->getBody();
        $data       = json_decode($body, true);

        // Handle successful responses
        if ($code >= 200 && $code < 300) {
            return $data;
        }

        // Common error handling
        if ($code === 401) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if ($code === 404) {
            // Use whichever NotFoundException is relevant to your request
            if ($notFoundRef && preg_match('/collections/', $uri)) {
                throw new CollectionNotFoundException($notFoundRef);
            }
            if ($notFoundRef) {
                throw new VideoNotFoundException($notFoundRef);
            }
            // Otherwise a generic not found
            throw new \Exception('Resource not found.');
        }
        if ($code === 400) {
            // Attempt to provide more detail if present in JSON
            $errorMessage = $data['message'] ?? 'Bad Request';
            if (!empty($data['data']['errorList']) && is_array($data['data']['errorList'])) {
                // Append all errorList messages
                $errorDetails = implode(', ', $data['data']['errorList']);
                $errorMessage .= ' - ' . $errorDetails;
            }
            throw new \Exception($errorMessage, 400);
        }

        // Fallback for unhandled statuses:
        throw new \Exception(
            sprintf('%s (HTTP %d). Response: %s', $failureMsg, $code, $body),
            $code
        );
    }

    public function listVideos(
        string $search = null,
        int $page = 1,
        int $items = 100,
        string $collection = null,
        string $orderby = null
    ): array {
        $query = [
            'page'         => $page,
            'itemsPerPage' => $items,
        ];

        if ($search) {
            $query['search'] = $search;
        }
        if ($collection) {
            $query['collection'] = $collection;
        }
        if ($orderby) {
            $query['orderBy'] = $orderby;
        }

        return $this->requestJson(
            'GET',
            'videos',
            ['query' => $query],
            'Could not list videos.'
        );
    }

    public function getVideo(string $videoId): array
    {
        return $this->requestJson(
            'GET',
            'videos/' . $videoId,
            [],
            'Could not get video.',
            $videoId
        );
    }

    public function updateVideo(string $videoId, array $body): array
    {
        return $this->requestJson(
            'POST',
            'videos/' . $videoId,
            ['json' => $body],
            'Could not update video.',
            $videoId
        );
    }

    public function deleteVideo(string $videoId): array
    {
        return $this->requestJson(
            'DELETE',
            'videos/' . $videoId,
            [],
            'Could not delete video.',
            $videoId
        );
    }

    public function createVideo(
        string $title,
        string $collectionId = null,
        int $thumbnailTime = null
    ): array {
        $json = [
            'title' => $title,
        ];
        if ($collectionId) {
            $json['collectionId'] = $collectionId;
        }
        if ($thumbnailTime) {
            $json['thumbnailTime'] = $thumbnailTime;
        }

        return $this->requestJson(
            'POST',
            'videos',
            ['json' => $json],
            'Could not create video.'
        );
    }

    public function uploadVideoWithVideoId(
        string $videoId,
        string $path,
        string $enabledResolutions = null
    ): array {
        if (!file_exists($path)) {
            throw new \Exception("File does not exist at given location: $path");
        }

        $fileStream = fopen($path, 'r');
        if ($fileStream === false) {
            throw new \Exception('The local file could not be opened.');
        }

        $query = [];
        if ($enabledResolutions) {
            $query['enabledResolutions'] = $enabledResolutions;
        }

        return $this->requestJson(
            'PUT',
            'videos/' . $videoId,
            [
                'query' => $query,
                'body'  => $fileStream,
            ],
            'Could not upload video.',
            $videoId
        );
    }

    public function uploadVideo(
        string $title,
        string $path,
        string $collectionId = null,
        int $thumbnailTime = null,
        string $enabledResolutions = null
    ): array {
        $videoObject = $this->createVideo($title, $collectionId, $thumbnailTime);
        return $this->uploadVideoWithVideoId($videoObject['guid'], $path, $enabledResolutions);
    }

    public function setVideoThumbnail(string $videoId, string $url): array
    {
        return $this->requestJson(
            'POST',
            'videos/' . $videoId . '/thumbnail',
            [
                'query' => ['thumbnailUrl' => $url],
            ],
            'Could not set video thumbnail.',
            $videoId
        );
    }

    public function getVideoHeatmap(string $videoId): array
    {
        return $this->requestJson(
            'GET',
            'videos/' . $videoId . '/heatmap',
            [],
            'Could not get video heatmap.',
            $videoId
        );
    }

    public function getVideoPlayData(
        string $videoId,
        string $token = null,
        int $expires = null
    ): array {
        $query = [];
        if ($token) {
            $query['token'] = $token;
        }
        if ($expires) {
            $query['expires'] = $expires;
        }

        return $this->requestJson(
            'GET',
            'videos/' . $videoId . '/play',
            ['query' => $query],
            'Could not get video play data.',
            $videoId
        );
    }

    public function getVideoStatistics(string $videoId = null, array $query = null): array
    {
        if (!$query) {
            $query = [];
        }

        if ($videoId) {
            $query = ['videoId' => $videoId];
        }

        return $this->requestJson(
            'GET',
            'statistics',
            ['query' => $query]
        );
    }

    public function reencodeVideo(string $videoId): array
    {
        return $this->requestJson(
            'POST',
            'videos/' . $videoId . '/reencode',
            [],
            'Could not reencode video.',
            $videoId
        );
    }

    public function addOutputCodec(string $videoId, int $codecId): array
    {
        if (!in_array($codecId, [0, 1, 2, 3])) {
            throw new \Exception('Invalid codec value. 0 = x264, 1 = vp9, 2 = hevc, 3 = av1.');
        }

        return $this->requestJson(
            'PUT',
            'videos/' . $videoId . '/outputs/' . $codecId,
            [],
            'Could not add output codec.',
        );
    }

    public function repackageVideo(string $videoId, bool $keepOriginalFiles = true): array
    {
        $query = ['keepOriginalFiles' => $keepOriginalFiles ? 'true' : 'false'];

        return $this->requestJson(
            'GET',
            'videos/' . $videoId . '/repackage',
            ['query' => $query],
            'Could not repackage video.',
            $videoId
        );
    }

    public function fetchVideo(
        string $url,
        string $title = null,
        string $collectionId = null,
        int $thumbnailTime = null,
        array $headers = null
    ): array {
        $query = [];
        if ($collectionId) {
            $query['collectionId'] = $collectionId;
        }
        if ($thumbnailTime) {
            $query['thumbnailTime'] = $thumbnailTime;
        }

        $body = [
            'url' => $url,
        ];
        if ($title) {
            $body['title'] = $title;
        }
        if ($headers) {
            $body['headers'] = $headers;
        }

        return $this->requestJson(
            'POST',
            'videos/fetch',
            [
                'query' => $query,
                'json'  => $body,
            ],
            'Could not fetch video.'
        );
    }

    public function addCaption(
        string $videoId,
        string $srclang,
        string $path,
        string $label
    ): array {
        if (!file_exists($path)) {
            throw new \Exception("Captions file does not exist at path: $path");
        }

        $body = [
            'srclang'      => $srclang,
            'captionsFile' => base64_encode(file_get_contents($path)),
        ];
        if ($label) {
            $body['label'] = $label;
        }

        return $this->requestJson(
            'POST',
            'videos/' . $videoId . '/captions/' . $srclang,
            ['json' => $body],
            'Could not add caption.',
            $videoId
        );
    }

    public function deleteCaption(string $videoId, string $srclang): array
    {
        return $this->requestJson(
            'DELETE',
            'videos/' . $videoId . '/captions/' . $srclang,
            [],
            'Could not delete caption.',
            $videoId
        );
    }

    public function transcribeVideo(string $videoId, string $language, bool $force = false, array $options = []): array
    {
        $query = [
            'language' => $language,
            'force'    => $force ? 'true' : 'false',
        ];

        $opts = [];
        if (!empty($options)) {
            $opts = array_filter([
                'targetLanguages'     => $options['targetLanguages'] ?? null,
                'generateTitles'      => $options['generateTitles'] ?? null,
                'generateDescription' => $options['generateDescription'] ?? null,
                'sourceLanguage'     => $options['sourceLanguage'] ?? null,
            ], fn($value) => $value !== null);
        }

        return $this->requestJson(
            'POST',
            'videos/' . $videoId . '/transcribe',
            [
                'query' => $query, 
                'json'  => $opts,
            ],
            'Could not transcribe video.',
            $videoId
        );
    }

    public function requestVideoResolutionsInfo(string $videoId): array
    {
        return $this->requestJson(
            'GET',
            'videos/' . $videoId . '/resolutions',
            [],
            'Could not list video resolutions.',
            $videoId
        );
    }


    public function cleanupResolutions(string $videoId, string $resolutions, array $query = null): array
    {
        $query = [
            'resolutionsToDelete'       => $resolutions,
            'deleteNonConfiguredResolutions' => $query['deleteNonConfiguredResolutions'] ?? 'false',
            'deleteOriginal'            => $query['deleteOriginal'] ?? 'false',
            'deleteMp4Files'            => $query['deleteMp4Files'] ?? 'false',
            'dryRun'                    => $query['dryRun'] ?? 'false',
        ];

        return $this->requestJson(
            'POST',
            'videos/' . $videoId . '/resolutions/cleanup',
            ['query' => $query],
            'Could not cleanup video resolutions.',
            $videoId
        );
    }

    public function listCollections(
        string $search = null,
        int $page = 1,
        int $items = 100,
        string $orderby = 'date',
        bool $includeThumbnails = false
    ): array {
        $query = [
            'page'              => $page,
            'itemsPerPage'      => $items,
            'includeThumbnails' => $includeThumbnails ? 'true' : 'false',
            'orderBy'           => $orderby,
        ];

        if ($search) {
            $query['search'] = $search;
        }

        return $this->requestJson(
            'GET',
            'collections',
            ['query' => $query],
            'Could not list collections.'
        );
    }

    public function getCollection(string $collectionId, bool $includeThumbnails = false): array
    {
        $query = [
            'includeThumbnails' => $includeThumbnails ? 'true' : 'false',
        ];

        return $this->requestJson(
            'GET',
            'collections/' . $collectionId,
            ['query' => $query],
            'Could not get collection.',
            $collectionId
        );
    }

    public function createCollection(string $name): array
    {
        return $this->requestJson(
            'POST',
            'collections',
            ['json' => ['name' => $name]],
            'Could not create collection.'
        );
    }

    public function updateCollection(string $collectionId, string $name): array
    {
        return $this->requestJson(
            'POST',
            'collections/' . $collectionId,
            ['json' => ['name' => $name]],
            'Could not update collection.',
            $collectionId
        );
    }

    public function deleteCollection(string $collectionId): array
    {
        return $this->requestJson(
            'DELETE',
            'collections/' . $collectionId,
            [],
            'Could not delete collection.',
            $collectionId
        );
    }
}
