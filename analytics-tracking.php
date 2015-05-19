<?php

/*
 * Sample Use Case to catch output from IfByPhone/DialogTech IVR (or frankly any webhooks)
 *
 */



include('googleAnalyticsMeasurementProtocolEvent.php');

/* instantiate object */
$event = new googleAnalyticsMeasurementProtocolEvent('UA-XXXXX-12', "IVR Call");

/* Give it a nice name */
$event->setDataSource('DialogTech.com');

/* choose POST/GET for inbound data */
$event->setDefaultBehavior('POST');

/* label the event */
$event->createEventData('Lead', 'call', 'IVR Call', 50);

/* set UTM params if you want to, as an associative array of utm_ values */
$event->createCampaignParameters($campaign);

?>
