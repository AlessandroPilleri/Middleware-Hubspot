<?php

    include 'libHubspot.php';

    $lib = new hsUtilities();
    $mysqli = $lib->dbConnect();

    $query = 'SELECT cliente.cliente, interlocutore.email FROM cliente ';
    $query = $query . ' JOIN interlocutore ON cliente.cliente=interlocutore.cliente WHERE cliente.dominioemail IS NULL ORDER BY interlocutore.email';


    $email = null;
    $codcliente = null;
    $dominiocliente = array();
    //$notvalid = ['gmail','tiscali','yahoo','icloud','libero'];

    /* execute multi query */
    if ($mysqli->multi_query($query))
    {
        do
        {
            /* store first result set */
            if ($result = $mysqli->store_result())
            {
                while ($row = $result->fetch_row())
                {
                    if($email == null)
                    {
                        $codcliente = $row[0];
                        $email = $row[1];
                        $pos = strrpos($email ,  '@');
                        $dominio = substr($email , $pos);
                    }

                    if($codcliente != $row[0])
                    {
                        $array = array('cliente' => $codcliente , 'dominio' => $dominio );
                        //debugsf($array);
                        array_push($dominiocliente, $array);
                        $codcliente = $row[0];
                        $email = $row[1];
                        $pos = strrpos($email ,  '@');
                        $dominio = substr($email , $pos);
                    }
                    else
                    {
                        $email = $row[1];
                        $pos = strrpos($email ,  '@');
                        $dominio = substr($email , $pos);
                    }
                }
                $result->free();
            }
            /* print divider */
            if ($mysqli->more_results())
            {
                printf("-----------------\n");
            }
        } while ($mysqli->next_result());
    }

    foreach ($dominiocliente as $index => $array)
    {
        $query = "UPDATE cliente SET dominioemail='" . $array['dominio']  . "' WHERE cliente=" . $array['cliente'];
        debugsf($query);
        $queryResult = mysqli_query($mysqli, $query);
    }


    $mysqli->close();

    function debugsf($str)
    {
        echo "<pre>\n";
        print_r($str);
        echo "</pre>\n";
        flush();
        ob_flush();
    }
?>
