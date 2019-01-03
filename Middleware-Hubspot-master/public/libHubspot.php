<?php

include 'company.php';
include 'contact.php';

class hsUtilities{

    function createContactsJson($queryResult)
    {
        $lista_contatti = array();
        foreach ($queryResult as $num_row => $record)
        {
            $properties = array();
            $c = new Contact($record);
            foreach($c as $hbname => $value)
            {
                if($hbname != 'email' && $hbname != 'vid')
                    array_push($properties,  array('property' => $hbname , 'value' => $value));
            }

            $contact = array('email' => $record['email'], 'properties' => $properties );
            array_push($lista_contatti, $contact);
        }

        $json = json_encode($lista_contatti, JSON_PRETTY_PRINT);

        return $json;
    }

    // --------- OK


    function getJsonFromHubspot($hapi)
    {
        $baseurl = 'https://api.hubapi.com/';

        $hapi = '/contacts/v1/lists/all/contacts/all';

        $url = $baseurl . $hapi;

        $options = array(
            'http' => array(
                'method'  => 'GET',
                'header'=>  "Content-Type: application/json\r\n" . "Accept: application/json\r\n" . "Authorization: Bearer " . $this->getToken() . "\r\n",
                'content' => $json
                )
            );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    function pushOneByOne($type, $hapi, $queryResult, $conn, $partner_codes)
    {

        if($type == 'contact')
        {
            $c = $queryResult;
            $nameProperty = 'property';

            $data = array();
            foreach ($c as $key => $value)
            {
                if($value != null && $value != 'NULL' && $key != 'vid' && $key != 'emailhubspot')
                    array_push($data, array( $nameProperty => $key, 'value'=> $value) );
            }
            $data = array('properties' => $data);
            $json = json_encode($data, JSON_PRETTY_PRINT);

	    $this->debugsf($json);

            $result = $this->pushJsonToHubspot($hapi,$json);

            $data = json_decode($result);

            /*
            foreach ($partner_codes as $key => $value)
            {
                $sql = "UPDATE interlocutore SET vid=" . $data->vid ." WHERE partner=" . $value;
                $queryResult = mysqli_real_query($conn, $sql);
            }*/

        }

        if($type == 'company')
        {
            foreach($queryResult as $row => $record)
            {

                $c = new Company($record);
                $nameProperty = 'name';

                //$this->debugsf($c);
                $data = array();
                foreach ($c as $key => $value)
                {
                    if($value != NULL && $value != 'NULL' && $key != 'companyId')
                        array_push($data, array( $nameProperty => $key, 'value'=> $value) );
                }

                $data = array('properties' => $data);
                $json = json_encode($data, JSON_PRETTY_PRINT);

                $this->debugsf($json);

                $result = $this->pushJsonToHubspot($hapi,$json);

                $data = json_decode($result);

                $sql = "UPDATE cliente SET companyId=" . $data->companyId ." WHERE cliente='" . $c->codice_cliente . "'";

                var_dump($sql);
                $queryResult = mysqli_query($conn, $sql);

            }
        }
    }

    function pushJsonToHubspot($hapi, $json)
    {
        $baseurl = 'https://api.hubapi.com/';

        $url = $baseurl . $hapi;

        $options = array(
            'http' => array(
                'method'  => 'POST',
                'header'=>  "Content-Type: application/json\r\n" . "Accept: application/json\r\n" . "Authorization: Bearer " . $this->getToken() . "\r\n",
                'content' => $json
                )
            );

	time_nanosleep(0,115000000);

        $this->debugsf($url);
        $context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);

        $this->debugsf('PUSH TO HUBSPOT RESULT');
        $this->debugsf($result);
        $this->debugsf('------');

        return $result;
    }

    function putOneByOne ($queryResult, $conn, $updateType)
        {
            foreach($queryResult as $row => $record)
            {
                $c = new Company($record);
                $nameProperty = 'name';

                $newDate = strtotime($c->data_ultimo_ordine) * 1000;
                $c->data_ultimo_ordine = $newDate;
                $this->debugsf($c);

                $hapi = 'companies/v2/companies/' . $c->companyId;

                $baseurl = 'https://api.hubapi.com/';

                $url = $baseurl . $hapi;

                $data = array();
                foreach ($c as $key => $value)
                {
                    if($value != null && $value != 'NULL' && $key != 'companyId')
                        array_push($data, array( $nameProperty => $key, 'value'=> $value) );
                }

                $data = array('properties' => $data);
                $json = json_encode($data, JSON_PRETTY_PRINT);

                var_dump($json);
                $result = $this->putJsonToHubspot($url,$json);

                if($updateType != 'fatturato')
                {
                    date_default_timezone_set('UTC');
                    $today = date('Y-m-d');
                    $uploadinfo = "UPDATE varvel.cliente SET uploadcliente='" .  $today . "'  WHERE cliente='" . $record['cliente'] . "'";
                    $uploadResult = mysqli_query($conn, $uploadinfo);
                    $this->debugsf($uploadinfo . ' - ' . $conn->err);
                }
            }
        }

    function putJsonToHubspot($url, $json)
    {

        $options = array(
            'http' => array(
                'method'  => 'PUT',
                'header'=>  "Content-Type: application/json\r\n" . "Accept: application/json\r\n" . "Authorization: Bearer " . $this->getToken() . "\r\n",
                'content' => $json
                )
            );

        var_dump($url);
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        time_nanosleep(0, 100000000);
        $this->debugsf('PUT TO HUBSPOT RESULT');
        $this->debugsf($result);
        $this->debugsf('------');

        return $result;
    }

    function dbConnect()
    {
        //$servername = "jira-gn";
        $servername = "mysql.gnet.it";
	    $username = "varvel";
        $password = "Varvel.2017";
        $dbname = "varvel";

        return new mysqli($servername, $username, $password, $dbname);
    }

    function debugsf($str)
    {
        echo "<pre>\n";
        print_r($str);
        echo "</pre>\n";
        flush();
        ob_flush();
    }

    function getToken()
    {
        $options = array(
            'http' => array(
                'method'=> 'GET'
            )
        );
        
        $url = 'http://localhost/refreshtoken';

        var_dump($url);
        $context  = stream_context_create($options);

        time_nanosleep(0, 100000000);

        $myfile = fopen("accessToken.txt", "r");
        $token = fread($myfile, filesize("accessToken.txt"));
        fclose($myfile);
        
        return $token;
    }
}
?>
