<?php

include 'libHubspot.php';

set_time_limit(6000);
date_default_timezone_set('UTC');

$lib = new hsUtilities();
$conn = $lib->dbConnect();

/* UPDATE DELLE COMPANY */
$sql = "SELECT RagioneSociale, RagioneSociale2, piva, cliente, classificazione, gruppoclienti, fornitore, phonetelefax, pivacee, gav, addVendite, pse, localita, via ,rg ,cap, phone, dominioemail, companyId, chiavesettoreindustriale FROM cliente WHERE companyId IS NOT NULL AND isDeleted IS NULL LIMIT 10";
$queryResult = mysqli_query($conn, $sql);
$querySize = mysqli_num_rows($queryResult);

$counter = 1;

do
{

    var_dump($counter);
    $json = $lib->putOneByOne($queryResult, $conn, 'company');

    $offset = $counter * 10;
    $counter++;
    $newsql = $sql . ' OFFSET ' .$offset;
    $lib->debugsf($newsql);
    $queryResult = mysqli_query($conn, $newsql);
    $querySize = mysqli_num_rows($queryResult);
} while($querySize != 0);

mysqli_close($conn);
/* */

/* INSERIMENTO NUOVE COMPANY */
$conn = $lib->dbConnect();

$hapi = 'companies/v2/companies/';
$sql = "SELECT RagioneSociale, RagioneSociale2, piva, cliente, classificazione, gruppoclienti, fornitore, phonetelefax, pivacee, gav, addVendite, pse, localita, via ,rg ,cap, phone, dominioemail, companyId, chiavesettoreindustriale FROM cliente WHERE companyId IS NULL AND isDeleted IS NULL LIMIT 10";
$queryResult = mysqli_query($conn, $sql);
$querySize = mysqli_num_rows($queryResult);

$counter = 1;

do
{
    $json = $lib->pushOneByOne('company', $hapi, $queryResult, $conn, '');

    $offset = $counter * 10;
    $counter++;
    $newsql = $sql . ' OFFSET ' .$offset;
    $lib->debugsf($newsql);
    $queryResult = mysqli_query($conn, $newsql);
    $querySize = mysqli_num_rows($queryResult);
} while($querySize != 0);

mysqli_close($conn);

 ?>
