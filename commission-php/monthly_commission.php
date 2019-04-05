<?php
require 'vendor/autoload.php';

#GET FORMULAR FROM SETTING#
function loadFormular($store)
{
    $formular = $store["OPWIRE_CONFIG_MONTHLY_COMMISSION_FORMULAR"];
    if (empty($formula)) {
        $formula = array(
            "bound" => array(
                "min" => 0,
                "max" => 200000000
            ),
            "monthlyCommission" => array(
                array(
                    "min"                    => 0,
                    "max"                    => 3,
                    "unitNoInsurance"        => 700000,
                    "unitInsurance"          => 1000000,
                    "compensateNoInsuarance" => 0,
                    "compensateInsuarance"   => 0,
                    "fixCommission"          => 3000000
                ),
                array(
                    "min"                    => 4,
                    "max"                    => 4,
                    "unitNoInsurance"        => 0,
                    "unitInsurance"          => 0,
                    "compensateNoInsuarance" => 220000,
                    "compensateInsuarance"   => 330000,
                    "fixCommission"          => 3000000
                ),
                array(
                    "min"                    => 5,
                    "max"                    => 5,
                    "unitNoInsurance"        => 0,
                    "unitInsurance"          => 0,
                    "compensateNoInsuarance" => 220000,
                    "compensateInsuarance"   => 330000,
                    "fixCommission"          => 3000000
                ),
                array(
                    "min"                    => 6,
                    "max"                    => 6,
                    "unitNoInsurance"        => 0,
                    "unitInsurance"          => 0,
                    "compensateNoInsuarance" => 220000,
                    "compensateInsuarance"   => 330000,
                    "fixCommission"          => 3000000
                ),
                array(
                    "min"                    => 7,
                    "max"                    => 9,
                    "unitNoInsurance"        => 0,
                    "unitInsurance"          => 0,
                    "compensateNoInsuarance" => 270000,
                    "compensateInsuarance"   => 400000,
                    "fixCommission"          => 4000000
                ),
                array(
                    "min"                    => 10,
                    "max"                    => 12,
                    "unitNoInsurance"        => 0,
                    "unitInsurance"          => 0,
                    "compensateNoInsuarance" => 430000,
                    "compensateInsuarance"   => 560000,
                    "fixCommission"          => 4000000
                ),
                array(
                    "min"                    => 13,
                    "max"                    => 15,
                    "unitNoInsurance"        => 0,
                    "unitInsurance"          => 0,
                    "compensateNoInsuarance" => 480000,
                    "compensateInsuarance"   => 640000,
                    "fixCommission"          => 4000000
                ),
                array(
                    "min"                    => 16,
                    "max"                    => 100,
                    "unitNoInsurance"        => 0,
                    "unitInsurance"          => 0,
                    "compensateNoInsuarance" => 500000,
                    "compensateInsuarance"   => 720000,
                    "fixCommission"          => 4000000
                )
            )
        );
    }
    return $formula;
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
            'isInsuarance' => true,
            'isGrey' => false,
            'createdAt' => ['$gte' => $start, '$lte' => $end]
        ]
    );

    $noInsuaranceContract = $collection->count(
        [
            'userId' => $userId,  
            'isInsuarance' => false,
            'isGrey' => false,
            'createdAt' => ['$gte' => $start, '$lte' => $end]
        ]
    );

    $insuaranceGreyContract = $collection->count(
        [
            'userId' => $userId,  
            'isInsuarance' => true,
            'isGrey' => true,
            'createdAt' => ['$gte' => $start, '$lte' => $end]
        ]
    );

    $noInsuaranceGreyContract = $collection->count(
        [
            'userId' => $userId,  
            'isInsuarance' => false,
            'isGrey' => true,
            'createdAt' => ['$gte' => $start, '$lte' => $end]
        ]
    );

    $totalContracts = $insuaranceContract + $noInsuaranceContract; 

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
    $commission += $condition['fixCommission'];
    
    if($totalNoInsuaranceContract >= 1)
    {
        $commission += $condition['compensateNoInsuarance'] * $totalNoInsuaranceContract;
        $commission += $condition['unitNoInsurance'] * $totalNoInsuaranceContract;
    }

    if($totalInsuaranceContract >= 1)
    {
        $commission += $condition['compensateInsuarance'] * $totalInsuaranceContract;
        $commission += $condition['unitInsurance'] * $totalInsuaranceContract;
    }

    if($commission < $boundFormular['min'])
    {
        $commission = $boundFormular['min'];
    } elseif($commission > $boundFormular['max'])
    {
        $commission = $boundFormular['max'];
    }

    $store["body"] = [
        "commission" => $commission
    ];
    
    return $store;
}
?>