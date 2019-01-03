<?php

include 'libHubspot.php';

set_time_limit(6000);

$lib = new hsUtilities();
$conn = $lib->dbConnect();

$query = 'SELECT FirstName,LastName, Cliente, Email, vid FROM interlocutore WHERE isDeleted=2 AND datacancellazione IS NULL AND vid IS NOT NULL order by cliente ';

$queryResult = mysqli_query($conn, $query);
$querySize = mysqli_num_rows($queryResult);

$lib->debugsf($queryResult);

foreach ($queryResult as $row => $record)
{

$lib->debugsf($record);

    $result = deleteHubspotContact($record['vid']);
    $lib->debugsf($result);

    $query = "UPDATE interlocutore SET datacancellazione='" . date("Y-m-d H:i:s")  ."' WHERE vid=" . $record['vid'];
    $lib->debugsf($query);
    $queryResult = mysqli_query($conn, $query);
}



function deleteHubspotContact($vid)
{
    $baseurl = 'https://api.hubapi.com/';
    $hapi = 'contacts/v1/contact/vid/' . $vid . '?hapikey=';
    $hapikey = 'f65f2574-292c-4d56-93cd-3b2584356eb6';

    $url = $baseurl . $hapi . $hapikey;

    $options = array(
        'http' => array(
            'method'  => 'DELETE'
            )
        );

    var_dump($url);
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    time_nanosleep(0, 100000000);
    debugsf('DELETE ON HUBSPOT RESULT');
    debugsf($result);
    debugsf('------');
}

function debugsf($str)
{
    echo "<pre>\n";
    print_r($str);
    echo "</pre>\n";
    flush();
    ob_flush();
}

function debug($str)
{
    echo $str."<br>\n";
    flush();
    ob_flush();
}

?>

