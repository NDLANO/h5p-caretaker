# Coding information

## Library architecture
The term architecture may be a little far-fetched. But maybe you wonder about the structure :-)

The starting point is the `H5PCaretaker` class which loads stuff and provides the functions that users of the library can call.

The `H5PCaretaker` instance is directly connected to the `H5PFileHandler` class handles for fetching data from H5P content files (see https://h5p.org/documentation/developers/h5p-specification): Parameters, media files, etc. For this, the H5P content file's contents will be unzipped to an `uploads` folder (which can be specified), data will be extracted, and then the files will be removed. The files will not be stored permanently.

The `H5PFileHandler` also fetches additional information (external accessibility reports from LibreText, currently) and amends the H5P content information with those. The H5P Caretaker library will try to cache these reports in the designated `cache` folder (which can be specified) in order to not having to request them from remote servers again and again. This external information handling will be moved to its own class in the future when other external sources become relevant.

The H5P Caretaker library will traverse the parameters which it found in the H5P file and _model_ a `ContentTree` class which - you guesses correctly, is a tree holding `Content` classes. `Content` classes represent an H5P (sub)content and may have `ContentFile` children if (media) files are linked to it. The resulting tree will match the structure of the H5P content.

The `H5PCaretaker` instance will then pass the content tree to different _report_ generator classes that look for issues and attach messages to the respective `Content` instance.

Ultimately, the `H5PCaretaker` instance will return a response containing the messages and the "raw" data fetched from central files such as `h5p.json` or `content.json`. It will also contain some information that a client may need, e.g. translations.

Speaking of translations: [gettext](https://www.gnu.org/software/gettext/) was the system of choice for handling translations.

## Improving the library

### Adding messages
Messages are added by a respective _report_ class for each category. Those classes can be found inside the `reports` folder.

Each _report_ class provides a `generateReport` function that receives a `ContentTree` class which holds all the content information. That class usually is used to traverse over all subcontents. The `generateReport` function also receives the raw information that may be required at times. The function is not expected to return anything.

In order to populate the report, when traversing the `Content` instances found in the `ContentTree`, you call `addReportMessage` on a `Content` instance and pass a message. That message can be built using 
the `ReportUtils::buildMessage` function and passing it the required keys and values (cmp. [report properties documentation](docs/report-properties.md#messages)).

Please note: When adding new types to a _report_ class, the type should be added to the `typeNames` member of the class. It will be used as part of the `categories` property that will be passed to the client. Also, the type must be added as a translatable string in `LocaleUtils::getKeywordTranslations` in order to register it with POEdit or similar tools.
