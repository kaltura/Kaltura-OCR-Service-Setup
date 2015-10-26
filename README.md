# Kaltura-OCR-Service-Setup

## Abstract:
Setup custom metadata profile and event notifications for OCR and update entries accordingly

- checks if the account doesn’t have the schema yet, and then sets the schema file
- checks if the Event Notification exists, and if not, sets up the http event notification
- loops through all entries in the account (using categories filter if provided), and then sets the a value to all entries:
     1. Schema system name: IBMOCR
     2. Field Name: ProcessOCR
     3. Field Value: Yes

## Requirements:
- PHP 5.3 and above
- [Kaltura php5 clientlibs] (http://www.kaltura.com/api_v3/testme/client-libs.php)

## Setup instructions
- Download the PHP5 client library and extract it into the same folder ("php5" should be the directory alongside the files in this repository). 
- Edit "ocr_cleanup.php" and "ocr_setup.php" and replace `require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');` with -  `require_once('./php5/KalturaClient.php');` (or change the path to whereever you decided to extract the Kaltura PHP5 client lib to).
- Open command line, cd to this repo directory, and run ocr_setup.php to configure an account (it will add the metadata profile, configure the http event notification, and loop through all entries to submit for OCR processing), or ocr_cleanup.php to clean an account that was already configured and processed (caution it will delete all OCR caption files from that account).
