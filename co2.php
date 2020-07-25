<?php
// File: co2.php
// Version: 0.1.0 Initial version
// Location: https://github.com/stephanDK/elfona
// This file is part of the elfona-project which extracts DK electricity consumer data and prepares them to be shown e.g. on an ePaper display managed by an Arduino.

$area = "DK1"; // Jutland, Fynen
//$area = "DK2"; // Seeland

date_default_timezone_set("Europe/Copenhagen");
$now = date('Y-m-d\TH:00:00.000\Z');
$nowPlusNine = date('Y-m-d\TH:00:00.000\Z', strtotime("+9 hour"));

$urlString = "https://www.energidataservice.dk/proxy/api/datastore_search_sql?sql=SELECT%20%22Minutes5DK%22,%20%22CO2Emission%22%20FROM%20%22d856694b-5e0e-463b-acc4-d9d7d895128a%22%20WHERE%20%22PriceArea%22%20=%20'{$area}'%20AND%20%22Minutes5DK%22%20%3E=%20'{$now}'%20AND%20%22Minutes5DK%22%20%3C=%20'{$nowPlusNine}'%20ORDER%20BY%20%22Minutes5DK%22%20ASC";

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => $urlString, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => array(),
));

$responseTxt = curl_exec($curl);
curl_close($curl);
$responseJson = json_decode($responseTxt); 
//var_dump($responseJson); echo("</br>\r\n============</br>\r\n");

$hourResults = array();
$hourResult = null;

foreach($responseJson->result->records as $prognosis)
{
	$dateHour = date('j.n. H:00', strtotime($prognosis->Minutes5DK));

	if($dateHour <> $hourResult["dateHour"]) // If new hour
	{
		if(empty($hourResult) == false) // If data for previous hour exist => add them to array
		{
			$hourResult["amount"] = intval($hourResult["amount"]/$hourResult["counter"]);
			$hourResults[] = $hourResult;
		}

		$hourResult	= array(
			"dateHour" => $dateHour,
			"counter" => 1,
			"amount" => $prognosis->CO2Emission,
		);
	}
	else // Add additional timestep data for same hour
	{
		$hourResult[counter] += 1;
		$hourResult[amount] += $prognosis->CO2Emission;
	}
}

// Print compact
for ($i=0, $len=count($hourResults); $i<$len && $i<9; $i++)
{
	echo("#");
	$intco2 = intval($hourResults[$i]["amount"]);
	$hour =  substr($hourResults[$i]["dateHour"], -5); 
	echo "{$hour}: {$intco2}";
}
echo("#");

// echo("============</br>\r\n"); var_dump($hourResults); echo("============</br>\r\n");
?>
