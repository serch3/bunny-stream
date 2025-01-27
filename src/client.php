<?php

declare(strict_types=1);
namespace Bunny\Stream;

class Client
{
    private string $apiAccessKey;
    private string $streamLibraryId;
    private string $apiBaseUrl;
    private \GuzzleHttp\Client $httpClient;

    public function __construct(string $apiKey, string $streamLibraryId) {
        $this->apiAccessKey = $apiKey;
        $this->streamLibraryId = $streamLibraryId;
        $this->apiBaseUrl = 'https://video.bunnycdn.com/library/';

        $this->httpClient = new \GuzzleHttp\Client([
            'allow_redirects' => false,
            'http_errors' => false,
            'base_uri' => $this->apiBaseUrl . $this->streamLibraryId . '/',
            'headers' => [
                'AccessKey' => $this->apiAccessKey,
            ],
        ]);
    }

    public function listVideos(string $search = null, int $page = 1, int $items = 100, string $collection = null, string $orderby = null): mixed
    {
        $query = [
            'page' => $page,
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

        $response = $this->httpClient->request('GET', 'videos', [
            'query' => $query,
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new Exception('Could not list videos.');
    }

    public function getVideo(string $videoId): mixed
    {
        $response = $this->httpClient->request('GET', 'videos/' . $videoId);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new Exception('Could not get video.');
    }

    public function updateVideo(string $videoId, array $body): mixed
    {
        $response = $this->httpClient->request('POST', 'videos/' . $videoId, [
            'json' => $body,
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new Exception('Could not update video.');
    }

    public function deleteVideo(string $videoId): mixed
    {
        $response = $this->httpClient->request('DELETE', 'videos/' . $videoId);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not delete video.');
    }

    public function createVideo($title, string $collectionId = null, int $thumbnailTime = null): mixed
    {
        $response = $this->httpClient->request('POST', 'videos', [
            'json' => [
                'title' => $title,
                'collectionId' => $collectionId,
                'thumbnailTime' => $thumbnailTime,
            ],
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new Exception('Could not create video.');
    }

    public function uploadVideoWithVideoId(string $videoId, string $path, string $enabledResolutions = null): mixed
    {
        if (!file_exists($path)) {
            throw new Exception("File does not exist at given location.");
        }

        $fileStream = fopen($path, 'r');
        if (false === $fileStream) {
            throw new Exception('The local file could not be opened.');
        }

        $query = [];
        if ($enabledResolutions) {
            $query['enabledResolutions'] = $enabledResolutions;
        }

        $response = $this->httpClient->request('PUT', 'videos/' . $videoId, [
            'query' => $query,
            'body' => $fileStream,
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        if (400 === $response->getStatusCode()) {
            throw new Exception('The requested video was already uploaded');
        }

        throw new Exception('Could not upload video.');
    }

    public function uploadVideo(string $title, string $path, string $collectionId = null, int $thumbnailTime = null, string $enabledResolutions = null): mixed
    {
        $videoObject = $this->createVideo($title, $collectionId, $thumbnailTime);
        return $this->uploadVideoWithVideoId($videoObject['guid'], $path, $enabledResolutions);
    }

    public function setVideoThumbnail(string $videoId, string $url): mixed
    {

        $response = $this->httpClient->request('POST', 'videos/' . $videoId . '/thumbnail', [
            'query' => ['thumbnailUrl' => $url],
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not set video thumbnail.');
    }

    public function getVideoHeatmap(string $videoId): mixed
    {
        $response = $this->httpClient->request('GET', 'videos/' . $videoId . '/heatmap');

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not get video heatmap.');
        
    }

    public function getVideoPlayData(string $videoId, string $token = null, int $expires = null): mixed
    {
        $query = [];
        if ($token) {
            $query['token'] = $token;
        }

        if ($expires) {
            $query['expires'] = $expires;
        }


        $response = $this->httpClient->request('GET', 'videos/' . $videoId . '/play', [
            'query' => $query,
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not get video statistics.');
    }

    public function getVideoStatistics(string $videoId, array $query): mixed
    {
        $response = $this->httpClient->request('GET', 'statistics', [
            'query' => $query,
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not get video statistics.');
    }

    public function reencodeVideo(string $videoId): mixed
    {
        $response = $this->httpClient->request('POST', 'videos/' . $videoId . '/reencode');

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not reencode video.');
    }

    public function repackageVideo(string $videoId, bool $keepOriginalFiles = True): mixed
    {
        $response = $this->httpClient->request('GET', 'videos/' . $videoId . '/repackage', [
            'query' => ['keepOriginalFiles' => $keepOriginalFiles],
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (400 === $response->getStatusCode()) {
            throw new Exception('Enterprise DRM is disabled for the library, repackaging not available');
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not repackage video.');
    }

    public function fetchVideo(string $url, string $title = null, string $collectionId = null, int $thumbnailTime = null, array $headers = null): mixed
    {
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
        
        $response = $this->httpClient->request('POST', 'videos/fetch', [
            'query' => $query,
            'json' => $body,
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (400 === $response->getStatusCode()) {
            throw new Exception('Failed fetching the video');
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($url);
        }

        throw new Exception('Could not fetch video.');
    }

    public function addCaption(string $videoId, string $srclang, string $path, $label = null): mixed
    {
        $body = [
            'srclang' => $srclang,
            'captionsFile' => base64_encode(file_get_contents($path)),
        ];
        if ($label) {
            $body['label'] = $label;
        }

        $response = $this->httpClient->request('POST', 'videos/' . $videoId . '/captions/' . $srclang, [
            'json' => $body,
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (400 === $response->getStatusCode()) {
            throw new Exception('Failed uploading the captions');
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not add caption.');
    }

    public function deleteCaption(string $videoId, string $srclang): mixed
    {
        $response = $this->httpClient->request('DELETE', 'videos/' . $videoId . '/captions/' . $srclang);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (400 === $response->getStatusCode()) {
            throw new Exception('Failed deleting the caption');
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not delete caption.');
    }

    public function transcribeVideo(string $videoId, string $language, bool $force = False): mixed
    {
        $response = $this->httpClient->request('POST', 'videos/' . $videoId . '/transcribe', [
            'query' => ['language' => $language, 'force' => $force ? 'true' : 'false'],
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (400 === $response->getStatusCode()) {
            throw new Exception('Invalid request for transcription queue');
        }

        if (404 === $response->getStatusCode()) {
            throw new VideoNotFoundException($videoId);
        }

        throw new Exception('Could not transcribe video.');
    }


    public function listCollections(string $search = null, int $page = 1, int $items = 100, string $orderby = 'date', bool $includeThumbnails = false): mixed
    {
        $query = [
            'page' => $page,
            'itemsPerPage' => $items,
            'includeThumbnails' => $includeThumbnails ? 'true' : 'false',
            'orderBy' => $orderby,
        ];

        if ($search) {
            $query['search'] = $search;
        }
        

        $response = $this->httpClient->request('GET', 'collections', [
            'query' => $query,
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new Exception('Could not list collections.'); 
    }

    public function getCollection(string $collectionId, bool $includeThumbnails = False): mixed
    {
        $response = $this->httpClient->request('GET', 'collections/' . $collectionId, [
            'query' => ['includeThumbnails' => $includeThumbnails ? 'true' : 'false'],
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new CollectionNotFoundException($collectionId);
        }

        throw new Exception('Could not get collection.');
    }

    public function createCollection(string $name): mixed
    {
        $response = $this->httpClient->request('POST', 'collections', [
            'json' => [
                'name' => $name,
            ],
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new Exception('Could not create collection.');
    }

    public function updateCollection(string $collectionId, string $name): mixed
    {
        $response = $this->httpClient->request('POST', 'collections/' . $collectionId, [
            'json' => [
                'name' => $name,
            ],
        ]);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new CollectionNotFoundException($collectionId);
        }
        
        throw new Exception('Could not update collection.');
    }

    public function deleteCollection(string $collectionId): mixed
    {
        $response = $this->httpClient->request('DELETE', 'collections/' . $collectionId);

        if (401 === $response->getStatusCode()) {
            throw new AuthenticationException($this->apiAccessKey);
        }

        if (200 === $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents(), true);
        }

        if (404 === $response->getStatusCode()) {
            throw new CollectionNotFoundException($collectionId);
        }

        throw new Exception('Could not delete collection.');
    }

}
