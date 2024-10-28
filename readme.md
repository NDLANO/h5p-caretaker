# H5P Caretaker
Library that is supposed to help people take care of H5P content types.

## Installation
This library is not meant to be used standalone. The common use case is to
use it as a dependency for your own application, e.g. inside a plugin of some
kind or as a standalone application.

In your own project, you can use common `composer` practices to use this library.
For now, it is not on packagist yet, so you will need to fetch the sources from
github like so: Inside your `composer.json` file, ensure that the repository is set
and that `require` is set to the library. Change "@dev" to some particular commit if
you need an older version explicitly.

```
  "repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/ndlano/h5p-caretaker"
		}
  ],
    "require": {
        "ndlano/h5p-caretaker": "@dev"
    }
```
