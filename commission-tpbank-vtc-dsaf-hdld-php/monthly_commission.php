<?php
require 'vendor/autoload.php';

#GET FORMULAR FROM SETTING#
function loadFormular($store)
{
    $formular = $store["OPWIRE_SETTINGS"]["TPBank_VTC_DSAF_HDLD"];
    return $formular;
}

function loadDbConnectionInfo($store)
{
    $databaseConnectionUrl = $store["OPWIRE_SETTINGS"]["MONGODB_URL"];
    return $databaseConnectionUrl;
}

function countContracts($store)
{
    $userId = $store["input"]["userId"];
    $month = $store["input"]["month"];
    $greyConvertUnit = $store["input"]["greyConvertUnit"];
    $applicationNumber = $store["input"]["applicationNumber"];

    $dateTimeQuery      = date("Y-m", strtotime($month));
    $dateTimeQueryStart = date("Y-m-01", strtotime($dateTimeQuery));
    $dateTimeQueryEnd   = date("Y-m-t", strtotime($dateTimeQuery));

    $start = new MongoDB\BSON\UTCDateTime(strtotime($dateTimeQueryStart)*1000);
    $end = new MongoDB\BSON\UTCDateTime(strtotime($dateTimeQueryEnd)*1000);
    $mongodbURL = loadDbConnectionInfo($store);
    $client = new MongoDB\Client($mongodbURL);
    $collection = $client->test->contracts;

    $insuaranceContract = $collection->count(
        [
            'userId' => $userId,
            'applicationNumber' => $applicationNumber,
            'isInsuarance' => true,
            'isGrey' => false,
            'createdAt' => ['$gte' => $start, '$lte' => $end],
        ]
    );

    $noInsuaranceContract = $collection->count(
        [
            'userId' => $userId,
            'applicationNumber' => $applicationNumber,  
            'isInsuarance' => false,
            'isGrey' => false,
            'createdAt' => ['$gte' => $start, '$lte' => $end]
        ]
    );

    $insuaranceGreyContract = $collection->count(
        [
            'userId' => $userId,  
            'applicationNumber' => $applicationNumber,
            'isInsuarance' => true,
            'isGrey' => true,
            'createdAt' => ['$gte' => $start, '$lte' => $end]
        ]
    );

    $noInsuaranceGreyContract = $collection->count(
        [
            'userId' => $userId, 
            'applicationNumber' => $applicationNumber, 
            'isInsuarance' => false,
            'isGrey' => true,
            'createdAt' => ['$gte' => $start, '$lte' => $end]
        ]
    );
    $totalGrey = ($insuaranceGreyContract + $noInsuaranceGreyContract) / $greyConvertUnit;
    $totalContracts = $insuaranceContract + $noInsuaranceContract + round($totalGrey); 
    
    return array(
        "totalContracts" => $totalContracts,
        "insuaranceContract" => $insuaranceContract,
        "noInsuaranceContract" => $noInsuaranceContract,
        "insuaranceGreyContract" => $insuaranceGreyContract,
        "noInsuaranceGreyContract" => $noInsuaranceGreyContract
    );
}

function calcMonthlyCommision($store)
{
    // Prepare Paramater
    $contractQuatityMonth = countContracts($store);
    $totalContracts = $contractQuatityMonth["totalContracts"];
    $totalInsuaranceContract = $contractQuatityMonth["insuaranceContract"];
    $totalNoInsuaranceContract = $contractQuatityMonth["noInsuaranceContract"];
    $totalInsuaranceGreyContract = $contractQuatityMonth["insuaranceGreyContract"];
    $totalNoInsuaranceGreyContract = $contractQuatityMonth["noInsuaranceGreyContract"];
   
    $formulas                  = loadFormular($store);
    $commissionFormulars       = $formulas["monthlyCommission"];
    $boundFormular             = $formulas["bound"];

    // Main Calculate
    $condition                 = '';
    foreach ($commissionFormulars as $formula) 
    {
         if($formula['min'] <= $totalContracts && $formula['max'] >= $totalContracts)
         {
            $condition = $formula;
            break;
         }
    }

    $commission  = 0;
    $commission += $condition['compensateNoInsurance'] * $totalNoInsuaranceContract;
    $commission += $condition['compensateInsurance'] * $totalInsuaranceContract;
    $commission += $condition['compensateGreyInsuarance'] * $totalInsuaranceGreyContract;
    $commission += $condition['compensateGreyNoInsuarance'] * $totalNoInsuaranceGreyContract;
    $commission += $condition['basicSalary'];
    $commission += $condition['allowance'];
    if($commission < $boundFormular['min'])
    {
        $commission = $boundFormular['min'];
    } elseif($commission > $boundFormular['max'])
    {
        $commission = $boundFormular['max'];
    }
    $store = [
        "commission" => $commission
    ];
    
    return $store;
}
?>