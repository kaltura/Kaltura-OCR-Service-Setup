<?php
require_once(dirname(__FILE__).'/util_funcs.inc');


if ($argc < 6){
    echo "\nUsage: ".$argv[0] . ' <partner id> <admin secret> <service url> <path/to/xsd> <notification URL> [comma separated category IDs to filter by]'."\n\n";
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
$xsdData=file_get_contents($argv[4]);
$notification_url=$argv[5];
$client = new KalturaClient($config);
$ks = $client->session->start($secret, $userId, $type, $partnerId, $expiry, $privileges);
$client->setKs($ks);
$metadata_prof_id=create_metadata($client,$xsdData);
$notification_id=create_event_notification($client,$notification_url);
if (isset($argv[6])){
    $filter = new KalturaMediaEntryFilter();
    $filter->categoriesIdsMatchOr = $argv[6] ;
}else{
    $filter=null;
}

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
	    update_custom_meta_data($client,$metadata_prof_id,$entry->id);
	}
    $page_index++;
}
