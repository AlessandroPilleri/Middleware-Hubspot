<?php
include 'libHubspot.php';

set_time_limit(6000);

$lib = new hsUtilities();
$conn = $lib->dbConnect();

/*
$mySQLColonne="ALTER TABLE cliente ADD uploadCliente VARCHAR(30);";
if (mysqli_query($conn, $mySQLColonne)) {
echo "Colonne aggiunte con successo in Cliente.";
debugsf($mySQLColonne);
}
else
echo "ERROR: Could not able to execute $mySQLColonne. " . mysqli_error($conn);

$mySQLColonne="ALTER TABLE cliente ADD uploadFatturato VARCHAR(30);";
if (mysqli_query($conn, $mySQLColonne)) {
echo "Colonne aggiunte con successo in Cliente.";
debugsf($mySQLColonne);
}
else
echo "ERROR: Could not able to execute $mySQLColonne. " . mysqli_error($conn);
*/


$query = 'SELECT firstname, lastname, interlocutore.phone, rep, email, interlocutore.cliente, partner, cliente.chiavelingua, cliente.companyId, interlocutore.vid, cliente.pse, cliente.rg, cliente.chiavesettoreindustriale, cliente.zonadistribuzionecliente ';
$query = $query .  " FROM varvel.interlocutore RIGHT JOIN varvel.cliente ON cliente.cliente = interlocutore.cliente WHERE interlocutore.isDeleted IS NULL AND fonte='Cliente SAP' ORDER BY interlocutore.cliente, email";

debugsf($conn);

debugsf($conn->err);

$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1200);

/*
if ($conn->multi_query($query))
{
    do
    {
        // store first result set
        if ($result = $conn->use_result())
        {
            while ($row = $result->fetch_row())
            {
                    debugsf($row);
	}
	}
    } while ($conn->next_result());

}
exit;
*/

$arrayResults = array();

// execute multi query
if ($conn->multi_query($query))
{
    
    do
    {
        // store first result set
        if ($result = $conn->use_result())
        {
            while ($row = $result->fetch_row())
            {
		array_push($arrayResults, $row);
            }
	    $result->close();
	}
        // print divider
        if ($conn->more_results())
        {
            printf("-----------------\n");
        }
    } while ($conn->next_result());

}



$email = null;
$partner_codes = array();

$contact = new contact();
$column_names = ['firstname', 'lastname', 'phone', 'reparti_interlocutore', 'email', 'codice_cliente', 'partner', 'lingua', 'associatedcompanyid','vid', 'country', 'state', 'settore_industriale','zona_distribuzione_cliente'];

foreach($arrayResults as $index => $row)
	{

		//debugsf($row);

               if($email == null)
                   $email = $row[4];

               if($email != $row[4])
               {
                   debugsf($contact);
                   // debugsf($partner_codes);

                   $hapi = 'contacts/v1/contact/createOrUpdate/email/'. $contact->email;

                   $lib->pushOneByOne('contact', $hapi, $contact, $conn, $partner_codes);

                   $contact= new Contact();
                   $partner_codes = array();
                   $email = $row[4];

                   foreach ($column_names as $pos => $name)
                   {
                       if($row[$pos] != null && $pos != 3)
                           $contact->$name = $row[$pos];
                   }

                   if($row[3] != null)
                       $contact->reparti_interlocutore = $row[3] . ';' . $contact->reparti_interlocutore;
                   array_push($partner_codes, $row[6]);

               }
               else
               {
                   foreach ($column_names as $pos => $name)
                   {
                       if($row[$pos] != null && $pos != 3)
                           $contact->$name = $row[$pos];
                   }

                   if($row[3] != null)
                       $contact->reparti_interlocutore = $row[3] . ';' . $contact->reparti_interlocutore;
                   array_push($partner_codes, $row[6]);
               }
           }
           debugsf($contact);
           $hapi = 'contacts/v1/contact/createOrUpdate/email/'. $contact->email;

           $lib->pushOneByOne('contact', $hapi, $contact, $conn, $partner_codes);



mysqli_close($conn);
//$conn->close();

/* -------- */ 

$conn = $lib->dbConnect();
debugsf($conn->err);
//$today = strtotime(date('Y-m-d')) * 1000;

$continua = true;
$hapi = 'contacts/v1/lists/all/contacts/all?property=vid&property=email&property=hs_email_optout&property=fonte&property=hs_email_bounce&property=hs_analytics_source_data_1&property=id_prospect&count=40&vidOffset=0';
$vidoffset = 0;
while($continua)
{
    debugsf($hapi);
    debugsf($vidoffset);
    $result = $lib->getJsonFromHubspot($hapi);
    $resultJson = json_decode($result);

    foreach ($resultJson->contacts as $num_contact => $contact)
    {
        $fonte = $contact->properties->fonte->value;
        $email_bounce = $contact->properties->hs_email_bounce->value;
        $opted_out = $contact->properties->hs_email_optout->value;
        $source_drilldown = $contact->properties->hs_analytics_source_data_1->value;
        $id_prospect = $contact->properties->id_prospect->value;

        //debugsf($contact);
        //debugsf($fonte);

        $query = "SELECT vid, email, fonte FROM varvel.interlocutore WHERE email='" . $contact->properties->email->value . "'";
        $queryResult = mysqli_query($conn, $query);

        if($queryResult->num_rows > 0)
        {
            foreach ($queryResult as $row => $record)
            {

                foreach ($contact->{'identity-profiles'} as $key => $value)
                {
                    foreach ($value->identities as $k => $v)
                        if($k == 0)
                            $sql = "UPDATE interlocutore SET vid=" . $contact->vid  ;
                }

                if($email_bounce > 0)
                    $sql .= ",isDeleted=1, bounced=" . $email_bounce;

                if($opted_out)
                    $sql .= ",isDeleted=2, optout=" . $opted_out;

                if($id_prospect != null)
                    $sql .= ",idprospect=" . $id_prospect;

                if($record['fonte'] == null)
                    $sql .= ", fonte='" . $fonte . "' ";

                $sql .= " WHERE email='" . $contact->properties->email->value . "'";
                debugsf($sql);
                $queryResult = mysqli_query($conn, $sql);

            }
        }
        else
        {
            $table_values = "0," . $contact->vid . ",'" . $contact->properties->email->value . "'";
            $sql = "INSERT INTO varvel.interlocutore (partner,vid, email," ;
            if($opted_out)
            {
                $table_values .= "," . $opted_out;
                $sql .= "optout,";
            }
            if($email_bounce > 0)
            {
                $table_values .=  "," . $email_bounce ;
                $sql .= "bounced,";
            }
            $table_values .= ",'" . $fonte . "'";
            $sql .= "fonte) VALUES(". $table_values .")";
            debugsf($sql);
            $queryResult = mysqli_query($conn, $sql);
        }
    }

    $vidoffset = $resultJson->{'vid-offset'};
    if($vidoffset ==  0 || $vidoffset == "0")
        $continua = false;

    $hapi = 'contacts/v1/lists/all/contacts/all?property=vid&property=email&property=hs_email_optout&property=fonte&property=hs_email_bounce&property=hs_analytics_source_data_1&property=id_prospect&count=40&vidOffset=' . $vidoffset;
    //$hapi = 'contacts/v1/lists/recently_updated/contacts/recent?property=vid&property=email&property=fonte&count=30&vidOffset=' . $vidoffset . '&timeOffset=' . $today;
}



$conn->close();

/* ------*/

function debugsf($str)
{
    echo "<pre>\n";
    print_r($str);
    echo "</pre>\n";
    flush();
    ob_flush();
}

?>

