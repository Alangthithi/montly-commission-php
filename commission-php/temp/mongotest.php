<?php
 require 'vendor/autoload.php';
 $userId = $store["input"]["userId"];
 $month = $store["input"]["month"];

 $dateTimeQuery      = date("Y-m", strtotime($month));
 $dateTimeQueryStart = date("Y-m-01", strtotime($dateTimeQuery));
 $dateTimeQueryEnd   = date("Y-m-t", strtotime($dateTimeQuery));

 $start = new MongoDB\BSON\UTCDateTime(strtotime($dateTimeQueryStart)*1000);
 $end = new MongoDB\BSON\UTCDateTime(strtotime($dateTimeQueryEnd)*1000);

 $client = new MongoDB\Client(loadDbConnectionInfo($store));
 $collection = $client->test->contracts;

 $insuaranceContract = $collection->count(
     [
         'userId' => $userId,  
         'isInsuarance' => true,
         'isGrey' => false,
         'createdAt' => ['$gte' => $start, '$lte' => $end]
     ]
 );
 var_dump($insuaranceContract);
?>