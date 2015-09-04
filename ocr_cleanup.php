<?php
require_once('util_funcs.inc');


if ($argc < 4){
    echo "\nUsage: ".$argv[0] . ' <partner id> <admin secret> <service url>'."\n\n";
    exit (1);
}
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
$userId = null;
$expiry = null;
$privileges = null;
$partnerId=$argv[1];
$secret = $argv[2];
$type = KalturaSessionType::ADMIN;
$config = new KalturaConfiguration($partnerId);
$config->serviceUrl = $argv[3];
$client = new KalturaClient($config);
$ks = $client->session->start($secret, $userId, $type, $partnerId, $expiry, $privileges);
$client->setKs($ks);

$pager=null;
$filter = new KalturaMetadataProfileFilter();
$profile_name=METADATA_SYSTEM_NAME;
$filter->systemNameEqual = $profile_name;
$results = $client->metadataProfile->listAction($filter, $pager);
if ($results->totalCount){
    $metadata_prof_id=$results->objects[0]->id;
    $metadataPlugin = KalturaMetadataClientPlugin::get($client);
    $result = $metadataPlugin->metadataProfile->listFields($metadata_prof_id);
    echo "Found $profile_name, ID $metadata_prof_id.\n";
}else{
    echo "Could not find $profile_name. Exiting.\n";
    exit (1);
}

$filter = new KalturaMediaEntryFilter(); 
$filterAdvancedSearch = new KalturaMetadataSearchItem();
$filterAdvancedSearch->metadataProfileId = $metadata_prof_id;
 
$filterAdvancedSearchItems = new KalturaSearchCondition();
$filterAdvancedSearchItems->field = "/*[local-name()='metadata']/*[local-name()='ProcessOCR']";
$filterAdvancedSearchItems->value = 'Yes';
 
$filterAdvancedSearch->items = array($filterAdvancedSearchItems);
$filter->advancedSearch = $filterAdvancedSearch;
//$filter=null;
$total_media_entries = $client->media->count($filter);
$pager = new KalturaFilterPager();
$page_index=1;
$pager->pageSize = 500;
$processed_entries=0;
 
while ($processed_entries < $total_media_entries){
    $pager->pageIndex=$page_index;
    $result = $client->media->listAction($filter, $pager);
    foreach ($result->objects as $entry) {
	$processed_entries++;
	delete_caption_assets($client,$entry->id,CAPTION_LABEL_TO_SEARCH);
	delete_metadata_field($client,$metadata_prof_id,$entry->id,METADATA_FIELD);
    }
    $page_index++;
}
