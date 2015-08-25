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
