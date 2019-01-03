<?php
    include 'libHubspot.php';

    set_time_limit(6000);
    date_default_timezone_set('UTC');

    $cur_year = date('Y');
    $prev_year = $cur_year-1;
    $today = date('Y-m-d');
    $today_prevyear = $prev_year . date('-m-d');

    $lib = new hsUtilities();
    $conn = $lib->dbConnect();


    $query = 'SELECT DISTINCT(customer) FROM fatturato ORDER BY customer';
    $queryResult = mysqli_query($conn, $query);
    $querySize = mysqli_num_rows($queryResult);

    /* ORDINATO ANNO CORRENTE */
    foreach($queryResult as $row => $record)
    {
        $sql = "SELECT SUM(GCNetValue), MAX(SalesDocumentDate), customer, companyId FROM fatturato JOIN cliente ON customer=cliente";
        $sql = $sql . " WHERE customer=" . $record['customer'] . " AND SalesDocumentDate BETWEEN '" . $cur_year . "-01-01' AND '". $cur_year ."-12-31'";

        $sqlResult = mysqli_query($conn, $sql);
        $size = mysqli_num_rows($sqlResult);

        $ok = false;

        foreach($sqlResult as $k => $v)
        {
            $lib->debugsf($v);
            if($v['SUM(GCNetValue)'] != null || $v['MAX(SalesDocumentDate)'] != null)
                $ok = true;
        }

        if($ok)
        {
            $lib->debugsf($sql);
            $lib->debugsf($conn->err);
            $lib->putOneByOne($sqlResult, $conn, 'fatturato');

            $uploadinfo = "UPDATE cliente SET uploadfatturato='" . $today . "' WHERE cliente='" . $record['customer'] . "'";
            $uploadResult = mysqli_query($conn, $uploadinfo);
            $lib->debugsf($uploadinfo);
        }
    }
    /* */

    /* ORDINA ANNO PRECEDENTE YTD */
    foreach($queryResult as $row => $record)
    {
        $sql = "SELECT SUM(GCNetValue), customer, companyId FROM fatturato JOIN cliente ON customer=cliente";
        $sql = $sql . " WHERE customer=" . $record['customer'] . " AND SalesDocumentDate BETWEEN '" . $prev_year . "-01-01' AND '" . $today_prevyear . "'";

        $sqlResult = mysqli_query($conn, $sql);
        $size = mysqli_num_rows($queryResult);

        $ok = false;

        foreach($sqlResult as $k => $v)
        {
            $lib->debugsf($v);
            if($v['SUM(GCNetValue)'] != null)
                $ok = true;
        }

        if($ok)
        {
            $lib->debugsf($sql);
            $lib->debugsf($conn->err);
            $lib->putOneByOne($sqlResult, $conn, 'fatturato');

            $uploadinfo = "UPDATE cliente SET uploadfatturato='" . $today . "' WHERE cliente='" . $record['customer'] . "'";
            $uploadResult = mysqli_query($conn, $uploadinfo);
            $lib->debugsf($uploadinfo);
        }
    }
    /* */

    /* ORDINATO ANNO PRECEDENTE */
    /*
    foreach($queryResult as $row => $record)
    {
        $sql = "SELECT SUM(GCNetValue), customer, companyId FROM fatturato JOIN cliente ON customer=cliente";
        $sql = $sql . " WHERE customer=" . $record['customer'] . " AND SalesDocumentDate BETWEEN '" . $prev_year . "-01-01' AND '". $prev_year ."-12-31'";

        $sqlResult = mysqli_query($conn, $sql);
        $size = mysqli_num_rows($queryResult);

        $ok = false;

        foreach($sqlResult as $k => $v)
        {
            $lib->debugsf($v);
            if($v['SUM(GCNetValue)'] != null )
                $ok = true;
        }

        if($ok)
        {
            $lib->debugsf($sql);
            $lib->debugsf($conn->err);
            $lib->putOneByOne($sqlResult, $conn, 'fatturato');

            $uploadinfo = "UPDATE cliente SET uploadfatturato='" . $today . "' WHERE cliente='" . $record['customer'] . "'";
            $uploadResult = mysqli_query($conn, $uploadinfo);
            $lib->debugsf($uploadinfo);
        }
    }
    /**/

    mysqli_close($conn);

?>
