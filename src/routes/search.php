<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header("Access-Control-Allow-Origin: *");

$app = new \Slim\App;


//get a questio to find the items
$app->post('/api/search',function(Request $request, Response $response){
    echo "si";
    $q = $request->getParam('QUESTION');
    $query = "  select * 
                from items i
                    join categories c on c.id_category = i.id_category
                where i.isbn = '".$q."' or i.title = '".$q."' or i.author = '".$q."' or i.PUBBLICATION_DATE = '".$q."' or c.genre = '".$q."'";
    try
    {
        $db = new db();
        //connect 
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        //check errors
        if (!oci_execute($stmt)) 
        {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        else {
            //responce data
            while ($row = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS)) 
            {
                $response->getBody()->write( json_encode($row));
            }
        }
        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    }
    catch(PDOException $e)
    {
        echo '{"error":{text: '.$e->getMessage().'}';
    }

});