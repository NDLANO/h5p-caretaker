<?php

// phpcs:disable Generic.Files.LineLength

// Base strings
$string["accessibility"] = "accessibility";
$string["audio"] = "audio";
$string["category"] = "category";
$string["caution"] = "caution";
$string["cautions"] = "caution messages";
$string["contentTypeCount"] = "content type count";
$string["description"] = "description";
$string["details"] = "details";
$string["discouragedLicenseAdaptation"] = "discouraged license adaptation";
$string["efficiency"] = "efficiency";
$string["error"] = "error";
$string["errors"] = "errors";
$string["features"] = "features";
$string["file"] = "file";
$string["hasLicenseExtras"] = "has license extras";
$string["image"] = "image";
$string["imageResolution"] = "image resolution";
$string["imageSize"] = "image size";
$string["info"] = "info";
$string["infos"] = "infos";
$string["invalidLicenseAdaptation"] = "invalid license adaptation";
$string["invalidLicenseRemix"] = "invalid license remix";
$string["learnMore"] = "Learn more about this topic.";
$string["level"] = "level";
$string["libreText"] = "libreText";
$string["license"] = "license";
$string["licenseNote"] = "license note";
$string["missingAltText"] = "missing alternative text";
$string["missingAuthor"] = "missing author";
$string["missingChanges"] = "missing changes";
$string["missingLibrary"] = "missing library";
$string["missingLicense"] = "missing license";
$string["missingLicenseExtras"] = "missing license extras";
$string["missingSource"] = "missing source";
$string["missingTitle"] = "missing title";
$string["noAuthorComments"] = "no author comments";
$string["notCulturalWork"] = "not cultural work";
$string["path"] = "path";
$string["questionTypeContract"] = "question type contract";
$string["recommendation"] = "recommendation";
$string["reference"] = "reference";
$string["resume"] = "resume";
$string["semanticsPath"] = "semanticsPath";
$string["statistics"] = "statistics";
$string["status"] = "status";
$string["subContentId"] = "subContentId";
$string["summary"] = "summary";
$string["title"] = "title";
$string["type"] = "type";
$string["untitled"] = "Untitled";
$string["video"] = "video";
$string["warning"] = "warning";
$string["warnings"] = "warnings";
$string["xAPI"] = "xAPI";

// Error strings
$string["error:couldNotCreateUploadDirectory"] = "Could not create upload directory %s.";
$string["error:decodingH5PJSON"] = "Error decoding h5p.json file.";
$string["error:fileEmpty"] = "The file is empty.";
$string["error:fileTooLarge"] = "The file is larger than the limit of %s bytes.";
$string["error:H5PFileDirectoryDoesNotExist"] = "Directory with extracted H5P files does not exist.";
$string["error:noFile"] = "It seems that no file was provided.";
$string["error:noH5PJSON"] = "h5p.json file does not exist in the archive.";
$string["error:notAnH5Pfile"] = "The file is not a valid H5P file / ZIP archive.";
$string["error:notH5PSpecification"] = "The file does not seem to follow the H5P specification.";
$string["error:unknownError"] = "Something went wrong, but I dunno what, sorry!";
$string["error:unzipFailed"] = "Error extracting H5P file ZIP archive.";
$string["error:uploadDirectoryNotWritable"] = "Upload directory %s is not writable.";

// Statistics Report strings
$string["statistics:contentTypeCount"] = "Numbers of content type uses";

// Reuse Report strings
$string["reuse:licenseHasAdditionalInfo"] = "License of %s contains additional information.";
$string["reuse:licenseHasAdditionalInfoRecommendation"] = "The license of this content contains additional information, potentially amending the reuse terms. If it's your work, think about whether using a more open license without extra terms might be possible, too.";
$string["reuse:licenseNotApproved"] = "License of %s is not approved for free cultural works.";
$string["reuse:licenseNotApprovedRecommendation"] = "Think about using a license that is approved for free cultural works if this is your work, or think about reaching out to the original author and ask whether this work could be released under a license that is approved for free cultural works.";
$string["reuse:noAuthorComments"] = "Content %s does not provide author comments.";
$string["reuse:noAuthorCommentsRecommendation"] = "Think about adding author comments to the metadata in order to describe the context and use case of your resource to give others a better understanding of how you use it.";

// Accessibility Report strings
$string["accessibility:libreTextEvaluation"] = "LibreText evaluation for %s";
$string["accessibility:missingAltText"] = "Missing alt text for image inside %s";
$string["accessibility:setAltTextBackground"] = "Set an alternative text for the background image.";
$string["accessibility:setAltTextCard"] = "Set an alternative text for the image of the card.";
$string["accessibility:setAltTextImage"] = "Set an alternative text for the image.";
$string["accessibility:setAltTextIntro"] = "Set an alternative text for the introduction image.";
$string["accessibility:setAltTextMatching"] = "Set an alternative text for the matching image.";
$string["accessibility:setAltTextOriginal"] = "Set an alternative text for the original image.";
$string["accessibility:setAltTextStartScreen"] = "Set an alternative text for the start screen image.";
$string["accessibility:setCaptionText"] = "Set a caption text for the asset thumbnail image.";
$string["accessibility:setDescriptionText"] = "Set a description text for the image.";

// Efficiency Report strings
$string["efficiency:imageCouldScaleDown"] = "Image file inside %s could be scaled down.";
$string["efficiency:imageConvertJPEG"] = "You might consider converting the image to a JPEG file which often take less space";
$string["efficiency:imageFileSize"] = "The image file size is %s bytes.";
$string["efficiency:imageMaxHeight"] = "The image will usually not be displayed larger than %d pixels in height.";
$string["efficiency:imageMaxWidth"] = "The image will usually not be displayed larger than %d pixels in width.";
$string["efficiency:imageReduceQuality"] = "You might consider reducing the quality level of the JPEG image.";
$string["efficiency:imageReduceResolution"] = "You might consider reducing the image's resolution if it does not need to be this high.";
$string["efficiency:imageRecommendedSize"] = "For this image type, we recommend a maximum file size of %s bytes in a web based context to reduce the loading time. Currently, the size is %s bytes.";
$string["efficiency:imageResolution"] = "The image has a resolution of %dx%d pixels.";
$string["efficiency:imageScaleDownHeight"] = "The image could safely be scaled down to a height of %d pixels without any visual quality loss.";
$string["efficiency:imageScaleDownWidth"] = "The image could safely be scaled down to a width of %d pixels without any visual quality loss.";
$string["efficiency:imageTooLarge"] = "Image file inside %s feels quite large.";
$string["efficiency:imageType"] = "The image type is %s.";
$string["efficiency:imageTypeUnknown"] = "unknown";
$string["efficiency:imageUnknownResolution"] = "The image has an unknown resolution.";

// Feature Report strings
$string["features:missingLibrary"] = "It seems that the library files for content %s are not included in the H5P file. Can't check features.";
$string["features:noQuestionType"] = "Content %s does not seem to support the H5P question type contract.";
$string["features:noResume"] = "Content %s does not seem to support resuming.";
$string["features:noXAPI"] = "Content %s does not seem to support xAPI.";
$string["features:partialQuestionType"] = "Content %s seems to partially support the full H5P question type contract.";
$string["features:supportedFunctions"] = "Supported functions/variables: %s.";
$string["features:supportsQuestionType"] = "Content %s seems to support the full H5P question type contract.";
$string["features:supportsResume"] = "Content %s seems to support resuming.";
$string["features:supportsXAPI"] = "Content %s seems to support xAPI.";
$string["features:unsupportedFunctions"] = "Unsupported functions/variables: %s.";

// Libretext Report strings
$string["libretext:licenseNote"] = "The \"H5P Accessibility Guide\" (%s) is shared under a \"CC BY 4.0\" license (%s) and was authored, remixed, and/or curated by LibreTexts (%s)";

// License Report strings
$string["license:addAuthor"] = "Add the author name or creator name in the metadata.";
$string["license:addChanges"] = "List any changes you made in the metadata.";
$string["license:addGPLText"] = "Add the original GPL license text in the \"license extras\" field.";
$string["license:addSource2030"] = "Creative Commons licenses of version from version 2.0 to 3.0 require you to add a link to the source material if contains a copyright notice or licensing information. If you are the original author, you might skip this, but you would make the life of people who want to reuse your content harder. Add the link to the content in the metadata.";
$string["license:addSource40"] = "Creative Commons licenses of version 4.0 require you to add a link to the source material. If you are the original author, you might skip this, but you would make the life of people who want to reuse your content harder. Add the link to the content in the metadata.";
$string["license:addTitle"] = "Add the title of the content (if supplied) in the metadata.";
$string["license:changesIn1030"] = "Creative Commons licenses from version 1.0 to 3.0 require you to list any changes you made to the content if you create a derivative. This does not apply, of course, if you are releasing a new work.";
$string["license:changesIn40"] = "Creative Commons licenses of version 4.0 require you to list any changes you made to the content. This does not apply, of course, if you are releasing a new work.";
$string["license:checkMaterial"] = "Check the license of the material you are using and consider alternatives.";
$string["license:contentLicenseSiblingContentLicense"] = "Content %s is licensed under a %s license while sibling content %s is licensed under a %s license.";
$string["license:discouragedAdaptation"] = "Discouraged license adaptation for %s";
$string["license:invalidAdaptation"] = "Invalid license adaptation for %s";
$string["license:invalidRemix"] = "Probably invalid license remix regarding %s inside %s";
$string["license:invalidVersion"] = "Subcontent %s is licensed under a CC BY-SA 1.0 license, but parent content is not.";
$string["license:legalCouncil"] = "It's a good idea to consult a legal council if you are unsure in this case.";
$string["license:missingAuthor"] = "Missing author information for %s";
$string["license:missingChanges"] = "Potentially missing changes information for %s";
$string["license:missingExtras"] = "Missing license extras information for %s";
$string["license:missingInside"] = "Missing license information for %s inside %s";
$string["license:missingMain"] = "Missing license information for %s as H5P main content";
$string["license:missingSource"] = "Missing source information for %s";
$string["license:missingTitle"] = "Missing title information for %s";
$string["license:missingVersion"] = "Missing license version information for %s";
$string["license:moreLicensed"] = "Subcontent %s is licensed under a CC BY license, but content is more openly licensed.";
$string["license:noCommercialUse"] = "Subcontent %s does not allow commercial use, but parent content %s does.";
$string["license:noDerivates"] = "Content %s uses subcontent %s, which does not allow derivates to be created from it.";
$string["license:potentiallyMissingSource"] = "Potentially missing source information for %s";
$string["license:remixCollectionOnly"] = "This combination is not allowed for remixes, but only for collections, and most often mixed content is a collection.";
$string["license:setVersion"] = "Set the license version in the metadata.";
