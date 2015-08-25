<?php
function create_metadata($client,$partnerId,$xsdData)
{
    $metadataObjectType=1;
    $viewsData = null;

    $pager=null;
    $filter = new KalturaMetadataProfileFilter();
    $filter->partnerIdEqual = $partnerId;
    $profile_name=METADATA_SYSTEM_NAME;
    $filter->systemNameEqual = $profile_name;
    $results = $client->metadataProfile->listAction($filter, $pager);
    if ($results->totalCount){
	echo "NOTICE: We already have $profile_name. Exiting w/o adding.\n";
	return $results->objects[0]->id;
    }
    $metadataProfile=new KalturaMetadataProfile(); 
    $metadataProfile->name = $profile_name;

    $metadataProfile->createMode = KalturaMetadataProfileCreateMode::KMC;
    $metadataProfile->systemName = $profile_name;
    $metadataProfile->objectType = 1;
    $metadataProfile->metadataObjectType = 1;
    //try{
	$results = $client->metadataProfile->add($metadataProfile, $xsdData, $viewsData);
    //}catch(){

    //}
    return $results->id;

}

function create_event_notification($client)
{
    // list available HTTP templates:
    $filter = new KalturaEventNotificationTemplateFilter();
    $filter->systemNameEqual = 'HTTP_METADATA_FIELD_EQUALS';
    $pager = null;
    $eventNotificationTemplate = new KalturaHttpNotificationTemplate();
    $eventnotificationPlugin = KalturaEventnotificationClientPlugin::get($client);
    $notification = $eventnotificationPlugin->eventNotificationTemplate->listAction($filter, $pager);
    if (isset($notification->objects[0]->id)){
	echo 'NOTICE: We already have '.$filter->systemNameEqual. " with ID: ". $notification->objects[0]->id. ". Exiting w/o adding.\n";
	return $notification->objects[0]->id;
    }
    $notification_templates = $eventnotificationPlugin->eventNotificationTemplate->listTemplates($filter, $pager);
    // id for entry change:
    if (isset($notification_templates->objects[0]->id)){
	    $template_id=$notification_templates->objects[0]->id;
    }else{
	    die("\nNo HTTP templates exist.\n Try running:\nphp /opt/kaltura/app/tests/standAloneClient/exec.php /opt/kaltura/app/tests/standAloneClient/httpNotificationsTemplate.xml /tmp/out.xml\nPassing -2 as partner ID");
    }

    // clone the template to partner:
    $eventNotificationTemplate=$result = $eventnotificationPlugin->eventNotificationTemplate->cloneAction($template_id, $eventNotificationTemplate);

    $notification_id = $result->id;

    // activate template
    $status = KalturaEventNotificationTemplateStatus::ACTIVE;
    $eventnotificationPlugin = KalturaEventnotificationClientPlugin::get($client);
    $result = $eventnotificationPlugin->eventNotificationTemplate->updateStatus($notification_id, $status);

    // update:
    $eventNotificationTemplate1 = new KalturaHttpNotificationTemplate();
    $eventNotificationTemplate1->userParameters = $eventNotificationTemplate->userParameters;
    $eventNotificationTemplate1->userParameters[0]->value->value=METADATA_FIELD;
    $eventNotificationTemplate1->userParameters[1]->value->value=METADATA_SYSTEM_NAME;
    $eventNotificationTemplate1->userParameters[2]->value->value='Yes';
    $eventNotificationTemplate1->url = 'http://174.37.245.158:9081/IBM_Research/Test';
    $KalturaHttpNotificationDataFields=new KalturaHttpNotificationObjectData();
    $KalturaHttpNotificationDataFields->apiObjectType='KalturaMetadata';
    $KalturaHttpNotificationDataFields->format=2;
    $KalturaHttpNotificationDataFields->code='(($scope->getEvent()->getObject() instanceof Metadata) ? $scope->getEvent()->getObject() : null)';
    $eventNotificationTemplate1->data = $KalturaHttpNotificationDataFields; 
    $eventnotificationPlugin = KalturaEventnotificationClientPlugin::get($client);
    $result = $eventnotificationPlugin->eventNotificationTemplate->update($notification_id, $eventNotificationTemplate1);
    echo('ID: '. $result->id. ', URL: '.$result->url."\n");
    return $result->id;
}

function update_custom_meta_data($client,$prof_id,$entry_id)
{
    $metadataPlugin = KalturaMetadataClientPlugin::get($client);
    $filter = new KalturaMetadataFilter();
    $filter->metadataProfileIdEqual = $prof_id;
    $filter->objectIdEqual = $entry_id;
    $pager = null;
    $result = $metadataPlugin->metadata->listAction($filter, $pager);
    if ($result->totalCount){
	echo "$entry_id already has ".METADATA_FIELD." set. Skipping.\n";
	return true;
    }
    $objectType = 1;
    $objectId = $entry_id;
    $xmlData = '<metadata> <'.METADATA_FIELD.'>Yes</'.METADATA_FIELD.'></metadata>';
    $result = $metadataPlugin->metadata->add($prof_id, $objectType, $objectId, $xmlData);

}



if ($argc < 5){
    echo 'Usage: '.__FILE__ . ' <partner id> <admin secret> <service url> <path/to/xsd> [comman separated categories to filter by]'."\n";
    exit (1);
}
require_once('/opt/kaltura/web/content/clientlibs/php5/KalturaClient.php');
define('METADATA_FIELD','ProcessOCR');
define('METADATA_SYSTEM_NAME','IBMOCR');
$userId = null;
$expiry = null;
$privileges = null;
$partnerId=$argv[1];
$secret = $argv[2];
$type = KalturaSessionType::ADMIN;
$config = new KalturaConfiguration($partnerId);
$config->serviceUrl = $argv[3];
$xsdData=file_get_contents($argv[4]);
$client = new KalturaClient($config);
$ks = $client->session->start($secret, $userId, $type, $partnerId, $expiry, $privileges);
$client->setKs($ks);
$metadata_prof_id=create_metadata($client,$partnerId,$xsdData);
$notification_id=create_event_notification($client);
if (isset($argv[5])){
    $filter = new KalturaMediaEntryFilter();
    $filter->categoriesIdsMatchOr = $argv[5] ;
}else{
    $filter=null;
}
$pager = new KalturaFilterPager();
$pager->pageSize = 0;

$result = $client->media->listAction($filter, $pager);
foreach ($result->objects as $entry) {
    update_custom_meta_data($client,$metadata_prof_id,$entry->id);
}
