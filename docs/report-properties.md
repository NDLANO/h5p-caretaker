# Report Properties
The report is a JSON object containing data that can be processed, e.g. for displaying information in a client.

| __Property__                  | __Type__         | __Description__                                   | __Required__ |
| ----------------------------- | ---------------- | --------------------------------------------------| ------------ |
| [client](#client)             | Object           | Information relevant for the client               | Required     |
| [messages](#messages)         | Array of Objects | Messages with information                         | Required     |
| [contentTree](#contenttree)   | Object           | Tree structure of H5P content and its subcontents | Required     |
| [raw](#raw)                   | Object           | Raw data for individual analysis                  | Required     |

## Client
The _client_ object holds information that may be relevant for the client display.

| __Property__                  | __Type__  | __Description__                                                    | __Required__ |
| ----------------------------- | --------- | ------------------------------------------------------------------ | ------------ |
| [categories](#categories)     | Object    | Info about all categories and types that are potentially available | Required     |
| [translations](#translations) | Object    | Translations information for the client or other receiving entity  | Required     |

### Categories
The _categories_ object holds information about all categories that the library handles. This can be useful for a client to know if not all categories are covered in the messages, but all categories should have a separate section or similar.

| __Property__                  | __Type__         | __Description__                                                                                                                                                                                                                                             | __Required__ |
| ----------------------------- | ---------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| accessibility                 | Array of Strings | Accessibility types. Possible values are: `libreText`, `missingA11yTitle`, `missingAltText`.                                                                                                                                                                                   | Required     |
| efficiency                    | Array of Strings | Efficiency types. Possible values are: `imageSize`, `imageResolution`.                                                                                                                                                                                      | Required     |
| features                      | Array of Strings | Feature types. Possible values are: `resume`, `xAPI`, `questionTypeContract`.                                                                                                                                                                               | Required     |
| license                       | Array of Strings | License types. Possible values are: `missingLicense`, `missingLicenceVersion`, `missingAuthor`, `missingTitle`, `missingLink`, `missingChanges`, `missingLicenseExtras`, `missingLicenseRemix`, `invalidLicenseAdaptation`, `discouragedLicenseAdaptation`. | Required     |
| reuse                         | Array of Strings | Reuse types. Possible values are: `notCultutalWork`, `noAuthorComments`, `hasLicenseExtras`.                                                                                                                                                                | Required     |
| statistics                    | Array of Strings | Statistics types. Possible values are: `contentTypeCount`.                                                                                                                                                                                                  | Required     |

### Translations
The _translations_ object holds the key value pairs for translatable strings in the language that was set when initializing. The following list only holds examples.

| __Property__                  | __Type__ | __Description__                                                                                        | __Required__ |
| ----------------------------- | ---------| ------------------------------------------------------------------------------------------------------ | ------------ |
| accessibility                 | String   | Translation/no space text of the _accessibility_ category, e.g. "Barrieregfreiheit" in German          | Required     |
| missingAltText                | String   | Translation/no space text of the _missinAltText_ type, e.g. "fehlender Alternativtext" in German       | Required     |
| questionTypeContract          | String   | Translation/no space text of the _questionTypeContract_ type, e.g. "question type contract" in English | Required     |

## Messages
The _messages_ array holds one object for each message. Strings will be translated into the language that was configured during initialization.

| __Property__                  | __Type__         | __Description__                                                                                                                                 | __Required__ |
| ----------------------------- | ---------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| category                      | String           | The category of the issue. Possible values are: `accessibility`, `efficiency`, `feature`, `license`, `reuse` or `statistics`.                   | Required     |
| type                          | String           | Type of issue within the category. Possible values are: `libreText`, `missignAltText`, `missingLicense` and others.                             | Required     |
| level                         | String           | Level of message. Possible values are `error` when user must change things, `caution` when user may need to change things and `info` otherwise. | Required     |
| summary                       | String           | Textual summary of the issue that was found.                                                                                                    | Required     |
| description                   | String           | Textual description of the issue that was found.                                                                                                | Required     |
| subContentId                  | String           | Subcontent id of H5P subcontent or 'root' for main content                                                                                      | Required     |               
| recommendation                | String           | Textual hint to the user what he/she should do.                                                                                                 | Recommended  |
| [details](#details)           | Object           | Extendable list of detail information                                                                                                           | Recommended  |

### Details
The _details_ section that does not have a fixed list of properties. While there are some common ones that are likely to be set, it can be amended when useful for the specific reporting case.

| __Property__  | __Type__  | __Description__                                                | __Required__ |
| ------------- | --------- | -------------------------------------------------------------- | ------------ |
| semanticsPath | String    | Path to to respective parameter within `semantics.json`.       | Recommended  |
| title         | String    | Title of the H5P content type set in the metadata.             | Recommended  |
| path          | String    | Path to file within H5P content file, e.g. media.              | Optional     |
| reference     | String    | URL to ressource that provided more information for the user.  | Optional     |
| description   | String    | E.g. used for LibreText accessibility report content.          | Optional     |
| licenseNote   | String    | E.g. used for Libretext accessibility report license note.     | Optional     |
| status        | String    | E.g. used for Libretext accessibility report usability status. | Optional     |
| type          | String    | E.g. used for Libretext accessibility report type field.       | Optional     |

### EditDirectly
The _editDirectly_ section can hold a structure similar to H5P's semantics.json to allow adding fields that the user can interact with in order to change property values.

| __Property__  | __Type__  | __Description__                                                  | __Required__ |
| ------------- | --------- | ---------------------------------------------------------------- | ------------ |
| checkBoxLabel | String    | Label of checkboxes (required for type of `boolean`)             | Optional     |
| fields        | Array     | Array of fields (required for type group)                        | Optional     |
| filePath      | String    | File path that the field option is linked to (only relevant for file changes) | Optional     |
| initialValue  | String    | Current value of the field within the H5P file                   | Recommended  |
| label         | String    | Label of the section/field                                       | Required     |
| options       | Array     | Array of objects `{value, label}` for `select` fields            | Optional     |
| pattern       | String    | Regular expression pattern for field validation                  | Optional     |
| placeholder   | String    | Placeholder for textual fields                                   | Optional     |
| semanticsPath | String    | Path to the semantics field that this field belongs to           | Required     |
| type          | String    | Field type (`boolean`\|`text`\|`group`\|`select`\|`date`\|`textarea`) | Required     |
| uuid          | String    | Unique identifier for the requested change                       | Required     |
| valueFalse    | String    | Value for unchecked checkbox (required for type of `boolean`)    | Optional     |
| valueTrue     | String    | Value for checked checkbox (required for type of `boolean`)      | Optional     |

Client may interpret those fields as they seem fit, but usually these `type` values will mean:
- `boolean`: checkbox
- `text`: text input field, also used for numbers in H5P parameters
- `group`: a collection of other types
- `select`: a dropdown menu
- `date`: a date picker
- `textarea`: a textarea for input

## ContentTree
The _content tree_ object represents the tree structure that the H5P content has with its subcontents.

| __Property__         | __Type__                     | __Description__                                                                                                                                                                           | __Required__ |
| -------------------- | ---------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| label                | String                       | Suggested label as content type `{title} ({machineName})`.                                                                                                                                | Required     |
| subContentId         | String                       | Subcontent id of H5P subcontent or 'root' for main content. May begin with `fake-` if it is not a real subcontent buy content using one of  H5P core's internal editor widgets for media. | Required     |
| title                | String                       | Title of the H5P content type set in the metadata.                                                                                                                                        | Required     |
| versionedMachineName | String                       | Versioned machine name of the content type, e.g. `H5P.CoursePresentation 1.26`. Not set for internal editor widget contents.                                                              | Optional     |
| children             | Array of ContentTree Objects | List of all subcontents as ContentTree objects as well.                                                                                                                                   | Optional     |

## Raw
The _raw_ object holds raw information that was extracted from the `h5p.json` file, etc.

| __Property__            | __Type__ | __Description__                                                                                                               | __Required__ |
| ----------------------- | -------- | ----------------------------------------------------------------------------------------------------------------------------- | ------------ |
| contentJson             | Object   | JSON object holding the parameters that were configured by the H5P content author.                                            | Required     |
| h5pJson                 | Object   | JSON object holding the contents of the `h5p.json` file (see https://h5p.org/documentation/developers/json-file-definitions). | Required     |
| [libraries](#libraries) | Object   | Content type library specific information.                                                                                    | Required     |
| [media](#media)         | Object   | Media information.                                                                                                            | Required     |

### Libraries
The _libraries_ object holds content type specific information indexed by machine name, e.g. `H5P.AdvancedText`.

| __Property__                   | __Type__ | __Description__                   | __Required__ |
| ------------------------------ | -------- | --------------------------------- | ------------ |
| [$machineName](#library-item) | Object   | Content type specific information | Required     |

#### Library item
A _library item_ holds content type specific information.

| __Property__      | __Type__ | __Description__                                                                     | __Required__ |
| ------------------| -------- | ----------------------------------------------------------------------------------- | ------------ |
| languages         | Object   | Available translation files indexed by ISO 639 Set 1 code.                          | Required     |
| libraryJson       | Object   | JSON contents of the `library.json` file (see https://h5p.org/library-definition).  | Required     |
| machineName       | String   | Machine name of the content type library, e.g. `H5P.AdvancedText`.                  | Required     |
| majorVersion      | String   | Major version of the content type library.                                          | Required     |
| minorVersion      | String   | Minor version of the content type library.                                          | Required     |
| semanticsJson     | Array of Objects | JSON contents of the `semantics.json` file (see https://h5p.org/semantics). | Required     |

### Media
The _media_ object holds information about media used in the content type (except videos).

| __Property__          | __Type__ | __Description__           | __Required__ |
| --------------------- | -------- | ------------------------- | ------------ |
| [audios](#media-item) | Object   | Audio file information.   | Optional     |
| [files](#media-item)  | Object   | General file information. | Optional     |
| [images](#media-item) | Object   | Image file information    | Optional     |

#### Media item
A media item object holds information index by the content file names.

| __Property__                            | __Type__ | __Description__           | __Required__ |
| --------------------------------------- | -------- | ------------------------- | ------------ |
| [$contentFileName](#media-content-item) | Object   | Media content information | Required     |

##### Media content item

A media content item object holds information about the medium depending on the type.

| __Property__ | __Type__ | __Description__                          | __Required__ |
| -------------| -------- | ---------------------------------------- | ------------ |
| size         | Number   | Size of the file in bytes.               | Required     |
| base64       | String   | Base64 encoded representation of images. | Optional     |
| height       | Number   | Height of an image in px.                | Optional     |
| width        | Number   | Width of an image in px.                 | Optional     |
