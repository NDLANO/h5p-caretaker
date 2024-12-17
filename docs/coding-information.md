# Coding information
## Library architecture
_TODO_

## Improving the library

### Adding messages
Messages are added by a respective _report_ class for each category. Those classes can be found inside the `reports` folder.

Each _report_ class provides a `generateReport` function that receives a `ContentTree` class which holds all the content information. That class usually is used to traverse over all subcontents. The `generateReport` function also receives the raw information that may be required at times. The function is not expected to return anything.

In order to populate the report, when traversing the `Content` instances found in the `ContentTree`, you call `addReportMessage` on a `Content` instance and pass a message. That message can be built using 
the `ReportUtils::buildMessage` function and passing it the required keys and values (cmp. [report properties documentation](docs/report-properties.md#messages)).

Please note: When adding new types to a _report_ class, the type should be added to the `typeNames` member of the class. It will be used as part of the `categories` property that will be passed to the client. Also, the type must be added as a translatable string in `LocaleUtils::getKeywordTranslations` in order to register it with POEdit or similar tools and to add a _no space text_ version for English if required.
