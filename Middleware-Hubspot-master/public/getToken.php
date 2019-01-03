<?php
    include 'libHubspot.php';

    $lib = new hsUtilities();

    $var = $lib->getToken();
    echo $var;
?>