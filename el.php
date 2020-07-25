<?php
// File: el.php
// Version: 0.2.0 2nd latest amount added (the day before yesterday)
// Version: 0.1.0 Initial version
// Location: https://github.com/stephanDK/elfona
// This file is part of the elfona-project which extracts DK electricity consumer data and prepares them to be shown e.g. on an ePaper display managed by an Arduino.


// Write your personal access token from eloverblik.dk below:
$personalAccessToken = "???";
$meterPoints = array(); // Containing all the meeters the user has (e.g. home, summer house,...)
date_default_timezone_set("Europe/Copenhagen");


// *** 0. *** Get the timed token
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.eloverblik.dk/CustomerApi/api/Token",
  CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array("Authorization: Bearer {$personalAccessToken}"),
));

$response = curl_exec($curl);
curl_close($curl);
$accessToken = json_decode($response)->result; 
//echo $accessToken;


// *** 1. *** Get Metering points
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.eloverblik.dk/CustomerApi/api/MeteringPoints/MeteringPoints?includeAll=false",
  CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array("Authorization: Bearer {$accessToken}"),
));

$responseTxt = curl_exec($curl);
curl_close($curl);
$responseJson = json_decode($responseTxt); 
//var_dump($responseJson); echo("============</br>\r\n");

foreach($responseJson->result as $meteringPoint)
{
	$meterPt = array(
		"id" => $meteringPoint->meteringPointId,
		"adress" => "{$meteringPoint->streetName} {$meteringPoint->buildingNumber}",
		"city" => $meteringPoint->cityName,
	);	
	$meterPoints[] = $meterPt;
}
// var_dump($meterPoints); echo("</br>\r\n============</br>\r\n");


// *** 2. *** Get the latest measurement (ask for last week and take newest amount (yesterday's data is not always available)
$today = date('Y-m-d');
$lastweek = date('Y-m-d', strtotime("-1 week"));
$urlStr = "https://api.eloverblik.dk/CustomerApi/api/MeterData/GetTimeSeries/{$lastweek}/{$today}/Day";
for ($i=0, $len=count($meterPoints); $i<$len; $i++)
{
	$curl = curl_init();
	curl_setopt_array($curl, array(CURLOPT_URL => $urlStr, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS =>"{\"meteringPoints\": {\"meteringPoint\": [\"{$meterPoints[$i]["id"]}\"]}}",
	CURLOPT_HTTPHEADER => array("Authorization: Bearer {$accessToken}", "Content-Type: application/json"),
	));
	
	$responseTxt = curl_exec($curl);
	curl_close($curl);
	$responseJson = json_decode($responseTxt); 

	$periode = $responseJson->result[0]->MyEnergyData_MarketDocument->TimeSeries[0]->Period;

	if(count($periode)>1)
	{
		$pLatest2nd = $periode[count($periode)-2];
		$meterPoints[$i]["latestAmount2"] = $pLatest2nd->Point[0]->{'out_Quantity.quantity'};
	}
	else
	{
		$meterPoints[$i]["latestAmount2"] = "???";
	}
	if(count($periode)>0)
	{
		$pLatest = $periode[count($periode)-1];
		$meterPoints[$i]["latestAmount"] = $pLatest->Point[0]->{'out_Quantity.quantity'};
		$meterPoints[$i]["latestAmountUnit"] = $responseJson->result[0]->MyEnergyData_MarketDocument->TimeSeries[0]->{'measurement_Unit.name'};
		$meterPoints[$i]["latestAmountDate"] = substr($pLatest->timeInterval->{'end'}, 0, 10);
	}
	else
	{
		$meterPoints[$i]["latestAmount"] = "???";
		$meterPoints[$i]["latestAmountUnit"] = "???";
		$meterPoints[$i]["latestAmountDate"] = "no data";
	}
}


// *** 3. *** Get measurement avg. last month
$lastmonth1 = date('Y-m-01', strtotime("-1 month"));
$lastmonth2 = date('Y-m-t', strtotime("-1 month"));
$daycount = intval(substr($lastmonth2, 8, 2));
$urlStr = "https://api.eloverblik.dk/CustomerApi/api/MeterData/GetTimeSeries/{$lastmonth1}/{$lastmonth2}/Month";
for ($i=0, $len=count($meterPoints); $i<$len; $i++)
{
	$curl = curl_init();
	curl_setopt_array($curl, array(CURLOPT_URL => $urlStr, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS =>"{\"meteringPoints\": {\"meteringPoint\": [\"{$meterPoints[$i]["id"]}\"]}}",
	CURLOPT_HTTPHEADER => array("Authorization: Bearer {$accessToken}", "Content-Type: application/json"),
	));
	
	$responseTxt = curl_exec($curl);
	curl_close($curl);
	$responseJson = json_decode($responseTxt); 

	$pLatest = $responseJson->result[0]->MyEnergyData_MarketDocument->TimeSeries[0]->Period[0]->Point[0]->{'out_Quantity.quantity'};
	$meterPoints[$i]["lastMonth"] = floatval($pLatest);
	$meterPoints[$i]["lastMonthPrDay"] = number_format($meterPoints[$i]["lastMonth"] / $daycount, 2);
}


// *** 4. *** Get measurement avg. last year
$lastmonth1 = date('Y-01-01', strtotime("-1 year"));
$lastmonth2 = date('Y-12-31', strtotime("-1 year"));
$daycount = date_diff(date_create($lastmonth1), date_create($lastmonth2))->days;

$urlStr = "https://api.eloverblik.dk/CustomerApi/api/MeterData/GetTimeSeries/{$lastmonth1}/{$lastmonth2}/Year";
for ($i=0, $len=count($meterPoints); $i<$len; $i++)
{
	$curl = curl_init();
	curl_setopt_array($curl, array(CURLOPT_URL => $urlStr, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS =>"{\"meteringPoints\": {\"meteringPoint\": [\"{$meterPoints[$i]["id"]}\"]}}",
	CURLOPT_HTTPHEADER => array("Authorization: Bearer {$accessToken}", "Content-Type: application/json"),
	));
	
	$responseTxt = curl_exec($curl);
	curl_close($curl);
	$responseJson = json_decode($responseTxt); 

	$pLatest = $responseJson->result[0]->MyEnergyData_MarketDocument->TimeSeries[0]->Period[0]->Point[0]->{'out_Quantity.quantity'};
	$meterPoints[$i]["lastYear"] = floatval($pLatest);
	$meterPoints[$i]["lastYearPrDay"] = number_format($meterPoints[$i]["lastYear"] / $daycount, 2);
}


/* *** 5. *** Print nicely - Compact
for ($i=0, $len=count($meterPoints); $i<$len; $i++)
{
	$laDate = date('j.n.', strtotime($meterPoints[$i]["latestAmountDate"]));
	echo "{$meterPoints[$i]["city"]}: {$meterPoints[$i]["latestAmount"]} kWh ({$laDate}), ";
	echo "alm: {$meterPoints[$i]["lastMonthPrDay"]} kWh, aly: {$meterPoints[$i]["lastYearPrDay"]} kWh\r\n"; 
}
*/

// *** 5. *** Print nicely - Full text
echo "#";
date_default_timezone_set('Europe/Copenhagen');
$date = date('d.m.Y G:i', time());
echo "{$date}#";
$meterCount = count($meterPoints);
echo "{$meterCount}#";

for ($i=0; $i<$meterCount; $i++)
{
	$laDate = date('j.n.', strtotime($meterPoints[$i]["latestAmountDate"]));
	$laMonth = date('F', strtotime("-1 month"));
	$laYear = date('Y', strtotime("-1 year"));
	
	$secondLatest = "";
	if($meterPoints[$i]["latestAmount2"] <> "???")
	{
		$secondLatest = floatval($meterPoints[$i]["latestAmount"]) - floatval($meterPoints[$i]["latestAmount2"]);
		if( $secondLatest > 0)
		{
			$secondLatest = "+{$secondLatest}";
		}
	}
	
	echo "{$meterPoints[$i]["city"]}#";
	echo "Latest ({$laDate}): {$meterPoints[$i]["latestAmount"]} kWh ";
	echo "({$secondLatest})#";
	echo "Average {$laMonth}: {$meterPoints[$i]["lastMonthPrDay"]} kWh#";
	echo "Average {$laYear}: {$meterPoints[$i]["lastYearPrDay"]} kWh#"; 
}

//echo("</br>\r\n============</br>\r\n"); var_dump($meterPoints); echo("</br>\r\n============</br>\r\n");
?>
