<?php
function loadFormular($store)
{
    return array(
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
function loansCredits($store)
{
    return array(
        "1" => array(
            "id"          => 1,
            "productName" => "vay tín dụng 1",
            "cpqlValue"   => 20,
            ""
        ),
        
    );
}

function loadDbConnectionInfo($store)
{
    return array(
        "dbConnectionInfo" => array(
            "DATABASE_CONNECTION" => "localhost:27017",
            "DATABASE_NAME"       => "test",
            "DATABASE_COLLECTION" => "contracts"
        )
    );
}
function countContracts($store)
{
    $mongo              = new MongoDB\Driver\Manager("mongodb://localhost:27017/test");
    $dbConnectionInfo   = loadDbConnectionInfo($store);

    $moClient           = MongoClient($dbConnectionInfo['DATABASE_CONNECTION']);
    $database           = $moClient[$dbConnectionInfo['DATABASE_NAME']];
    $contracts          = $database[$dbConnectionInfo['DATABASE_CONNECTION']];

    $queryParams        = store['OPWIRE_REQUEST']['query'];
    $userId             = $queryParams["userId"][0];
    $month              = $queryParams["month"][0];

    $dateTimeQuery      = date("Y-m-d", strtotime($month));
    $dateTimeQueryStart = date("Y-m-01", strtotime($dateTimeQuery));
    $dateTimeQueryEnd   = date("Y-m-t", strtotime($dateTimeQuery));

    $filter             = array("userId" => $userId, 'createdAt'=> ['$lte'=> $dateTimeQueryEnd, '$gte'=> $dateTimeQueryStart]);
    $options            = array();

    $query              = new \MongoDB\Driver\Query($filter,$options);
    $rows               = $mongo->executeQuery('test.contracts', $query);
    $count              = '';
    foreach ($rows as $document) {
    $document = json_decode(json_encode($document), true);
    $count = $contracts->count($document);
    // echo "$document->userId : $document->contractCode : $document->createdAt \n ";
    }
    return $count;
}

function calculator($store)
{
    $contractQuatityMonth      = countContracts($store);
    $totalNoInsuaranceContract = $contractQuatityMonth["totalNoInsurance"];
    $totalInsuaranceContract   = $contractQuatityMonth["totalInsurance"];
    $totalContracts            = $totalNoInsuaranceContract + $totalInsuaranceContract;
    
    $formulas                  = loadFormular($store);
    $commissionFormulars       = $formulas["monthlyCommission"];
    $boundFormular             = $formulas["bound"];

    $condition                 = null;
    foreach ($commissionFormulars as $formula) 
    {
        if($formula['min'] <= $totalContracts && $formula['max'] >= $totalContracts)
        {
            $condition = $formula;
            break;
        }
    }
    $commission  = 0;
    $commission += $condition['unitNoInsurance']*$totalNoInsuaranceContract;
    $commission += $condition['unitInsurance']*$totalInsuaranceContract; 
    $commission += $condition['fixCommission'];
    if($condition['compensateNoInsuarance'] == $totalNoInsuaranceContract)
    {
        $commission += $condition['compensateNoInsuarance'];
    }
    if($condition['compensateInsuarance'] == $totalInsuaranceContract)
    {
        $commission += $condition['compensateInsuarance'];
    }
    if($commission < $boundFormular['min'])
    {
        $commission = $boundFormular['min'];
    } elseif($commission > $boundFormular['max'])
    {
        $commission = $boundFormular['max'];
    }
    return $commission;
}
 ?>