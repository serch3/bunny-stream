# Bunny Stream PHP Library
A simple PHP library to interact with the Bunny Stream [API](https://docs.bunny.net/reference/api-overview).

### Requires
In order to interact with the API you need the API Access Information (Stream->{Library}->API)

## Installation

```shell
composer require serch3/bunny-stream
```

## How to use: 

### Quick start
Create an instance of the \Bunny\Stream\Client with the authentication details:

```php
$client = new \Bunny\Stream\Client('API_KEY', 'LIBRARY_ID');
```
---
## Manage Videos: 

### Listing Videos:
```php
$client->listVideos();

$client->listVideos($search, $page, $items, $collection, $orderby); //filtered results
```
Optional:

- `$Search` if set, the response will be filtered to only contain videos that contain the search term `string`

- `$Page` Page number. Default is 1 `int`

- `$Items` Number of results per page. Default is 100 `int`

- `$Collection` If set, the response will only contain videos that belong to this collection Id  `string`

- `$OrderBy` Determines the ordering of the result within the response. date/title `string`

---

###  Get Video
```php
$client->getVideo($videoId);
```
`$videoId` ID of the video `string`

---

### Update Video
```php
$body = [
        'title' => '...',
        'collectionId' => '...',
        'chapters' => [
            [
                'title' => 'Chapter 1',
                'start' => 0,
                'end' => 300,
            ]
        ],
        'moments' => [
            [
                'label' => 'Awesome Scene 1',
                'timestamp' => 70,
            ],
        ],
        'metaTags' => [
            [
                'property' => 'description',
                'value' => 'My Video Description',
            ],
        ],
];
$client->updateVideo($videoId, $body);
```
`$videoId` Id of the video `string`

`$body` Updated video details `array`

---

### Delete Video
```php
$client->deleteVideo($videoId);
```
`$videoId` Id of the video that will be **permanently** deleted `string`

---

### Create Video Entry
```php
$client->createVideo($title, $collectionId, $thumbnailTime);
```
`$title` Title of the video `string`

Optional:

- `$collectionId` Collection Id `string`

- `$thumbnailTime` Video time in ms to extract the main video thumbnail `int32`

---

### Upload Video with Id
```php
$client->uploadVideoWithVideoId($videoId, $path, $enabledResolutions);
```
`$videoId` Id of the video entry `string`

`$path` Video file path `string`

Optional: 

- `$enabledResolutions` Custom resolutions for the video `string` 

---

### Upload Video
```php
$client->uploadVideo($title, $path, $collectionId, $thumbnailTime, $enabledResolutions);
```
`$title` Title of the video `string`

`$path` Video file path `string`

Optional:

- `$collectionId` Collection Id `string`

- `$thumbnailTime` Video time in ms to extract the main video thumbnail `int32`

- `$enabledResolutions` Custom resolutions for the video `string`

---

### Set Thumbnail
```php
$client->setVideoThumbnail($videoId, $url);
```
`$videoId` Id of the video `string`

`$url` accessible thumbnail url `string`

---

### Get Video Heatmap
```php
$client->getVideoHeatmap($videoId);
```
`$videoId` Id of the video `string`

---

### Get Video play data
```php
$client->getVideoPlayData($videoId, $token, $expires);
```
`$videoId` Id of the video `string`

Optional:

- `$token` Token to authenticate the request `string`

- `$expires` Expiry time of the token `int64`

---

### Get Video Statistics
```php
$query = [
    'dateFrom' => 'm-d-Y',
    'dateTo' => 'm-d-Y',
    'hourly' => false,
    'videoGuid' => '...',
];
$client->getVideoStatistics($videoId, $query);
```
`$videoId` Id of the video `string`

Optional:

- `$query` parameters `array`: 
    - *dateFrom* - The start date of the statistics. If no value is passed, the last 30 days will be returned. `date-time`
    - *dateTo* - The end date of the statistics. If no value is passed, the last 30 days will be returned. `date-time`
    - *hourly* - If true, the statistics data will be returned in hourly groupping. `boolean` 
    - *videoGuid* - The GUID of the video for which the statistics will be returned `string`

### Re-encode Video
```php
$client->reencodeVideo($videoId);
```
`$videoId` Id of the video `string`

---

### Repackage Video
```php
$client->repackageVideo($videoId, $keepOriginalFiles);
```
`$videoId` Id of the video `string`

`$keepOriginalFiles` Marks whether previous file versions should be kept in storage, allows for faster repackage later on. Default is true.

---

### Fetch Video
```php
$client->fetchVideo($url, $title, $collectionId, $thumbnailTime, $headers);
```
`$url` The URL from which the video will be fetched from. `string`

Optional:

- `$title` Title of the video `string`

- `$collectionId` Collection Id `string`

- `$thumbnailTime` Video time in ms to extract the main video thumbnail `int32`

- `$headers` Additional headers that will be sent along with the fetch request. `array`

---

### Add Caption
```php
$client->addCaption($videoId, $srclang, $path, $label);
```
`$videoId` Id of the video `string`

`$srclang` Language shortcode for the caption. `string`

`$path` Caption file path (.vtt/.srt) `string`

Optional:

- `$label` Label of the caption `string`

---

### Delete Caption
```php
$client->deleteCaption($videoId, $srclang);
```
`$videoId` Id of the video `string`

`$srclang`  Language shortcode for the caption. `string`

---

### Transcribe video
```php
$client->transcribeVideo($videoId, $language, $force);
```
`$videoId` Id of the video `string`

`$language` Language code for the transcription `string`

`$force` Default is false `bool`

---

## Collections:

### Listing Collections
```php
$client->listCollections($search, $page, $items, $orderby, $includeThumbnails);
```
Optional:

- `$search` if set, the response will be filtered to only contain collections that contain the search term `string`

- `$page` Page number. Default is 1 `int`

- `$items` Number of results per page. Default is 100 `int`

- `$orderby` Determines the ordering of the result within the response. date/title `string`

- `$includeThumbnails` If set to true, the response will include the thumbnail for each collection. Default is false `bool`

---

### Get Collection
```php
$client->getCollection($collectionId, $includeThumbnails);
```
`$collectionId` Id of the collection `string`

Optional:

- `$includeThumbnails` If set to true, the response will include the thumbnail URL for the collection. Default is false `bool`

---

### Create Collection
```php
$client->createCollection($name);
```
`$name` Name of the collection `string`

---

### Update Collection
```php
$client->updateCollection($collectionId, $name);
```
`$collectionId` Id of the collection `string`

`$name` Updated name of the collection `string`

---

### Delete Collection
```php
$client->deleteCollection($collectionId);
```
`$collectionId` Id of the collection to be deleted `string`


---
## Returns
All methods return an associative array with the response from the API, or an exception if an error occurs. Check reference for specific responses.