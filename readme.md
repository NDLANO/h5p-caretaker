# H5P Caretaker
The H5P Caretaker is a library that is supposed to help people take care of H5P content types. It expects an H5P content files as input and will return a JSON object that contains structured information about issues that may hinder sharing the content, e.g. missing alternative texts for images, missing or incompatible license information of subcontent, etc.

## Installation
This library is not meant to be used standalone. The common use case is to use it as a dependency for your own application, e.g. inside a plugin of some kind or as a standalone application.

In fact, there is a [reference H5P Caretaker client](https://github.com/ndlano/h5p-caretaker-client)
which displays the results found by this library and a PHP based [reference H5P Caretaker server](https://github.com/ndlano/h5p-caretaker-server) that glues this library to the client.

## Using the library
In your own project, you can use common `composer` practices to use this library. Inside your `composer.json` file, you should add an entry like this:

```
"require": {
    "ndlano/h5p-caretaker": "^1.0.0"
}
```

The common use case then is to fetch an H5P content file (.h5p), pass it to the library and then to further process the results.

Your own project either uses the common `autoload` procedure itself, meaning that the library will be loaded as a depencency automatically. Alternatively, you'll have to run something like

```
require_once __DIR__ . '/vendor/autoload.php';
use Ndlano\H5PCaretaker\H5PCaretaker;
```

You many need to adjust the path, of course, depending on where your PHP code lives at.

### Instantiating
You can then instantiate an H5PCaretaker instance via something like.
```
$h5pCaretaker = new H5PCaretaker($config);
```

`$config` is expected to be an associative array like this:

| __Property__ | __Type__ | __Description__                                                                                                                                             | __Required__ |
| ------------ | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| locale       | String   | ISO 639 Set 1 language code, representing the requested language for the reporting data. _Default: en_                                                      | Optional     |
| uploadsPath  | String   | Path to where the H5P content files may be unpacked to temporarily. _Default: `uploads` inside the h5p-caretaker folder (must be writable by server)_       | Optional     |
| cachePath    | String   | Path to where external reports that are fetched from the web can be cached. _Default: `cache` inside the h5p-caretaker folder (must be writable by server)_ | Optional     |
| cacheTimeout | Int      | Define how long (in seconds) external ressources (e.g. Libretext accessibility data) will be cached. _Default: `86400` (24 hours)                           | Optional     |

After instantiating, you can use certain methods

### analyze($params: array) => array
Use analyze to retrieve the report.

$params is expected to be an associative array that at least contains the file path to the H5P content that is supposed to be analyzed.

| __Property__ | __Type__ | __Description__                                        | __Required__ |
| ------------ | -------- | ------------------------------------------------------ | ------------ |
| file         | String   | File path to H5P content file that should be analyzed. | Required     |

More properties may become available in the future in order to tweak the analysis.

The return value is going to be a JSON object represented in an associative array. Please refer to the [report properties documentation](docs/report-properties.md) for details.

## Contribution
If you think that some feature of the H5P content should be checked, please raise an issue in order to discuss it. Please note though that this library does not render the content, but merely assesses the parmeters, media and libraries that are found on the server. Therefore, it is e.g. not possible to detect contrast issues impeding accessibility, etc.

If you want to contribute code, you are welcome. Please also raise an issue beforehand to talk about your ideas. Please see the [coding information documentation](docs/coding-information.md) for technical details.

If you want to contribute translation, you will find easily understandable translation files in `app/lang`.

## Future Development
There are some ideas on what this library should be able to do in the future:
- Allow to write to H5P content files, so issues such as missing license information can be rectified without having to start H5P.
- Allow to use large language models to fill in missing data, e.g. set missing alternative texts for images.
