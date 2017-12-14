<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header("Access-Control-Allow-Origin: *");

$cart = new \Slim\App;

// get all item in cart

$cart->get('/api/{user_id}/cart/list', function (Request $request, Response $response){
    $sql = "SELECT * FROM orders WHERE ID_USER = '".$request->getParam('user_id')."'";
    try
    {
        $db = new db();
        //connect 
        $db = $db->connect();
        $stmt = oci_parse($db, $sql);
        if (!oci_execute($stmt)) 
        {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        
        while ($row = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS)) 
        {
            //var_dump($row);
            $response->getBody()->write( json_encode($row));
        }
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    }
    catch(PDOException $e)
    {
        echo '{"error":{text: '.$e->getMessage().'}';
    }
});


// post new item
$cart->post('/api/cart/add/{id}', function (Request $request, Response $response){
        
    $user = $request->getParam('USERNAME');
    $item = $request->getParam('ID_ITEM');
    $quantity = $request->getParam('QUANTITY');
    $delivery = 'ordered';

    $query = "insert INTO orders(ID_USER, ID_ITEM, QUANTITY, DELIVERY_STATUS) VALUES ('".$user."', '".$item."', '".$quantity."', '".$delivery."')";
                try
                {
                    $db = new db();
                    //connect 
                    $db = $db->connect();
                    $stmt = oci_parse($db, $query);
                    //check errors
                    if (!oci_execute($stmt)) 
                    {
                        $response->getBody()->write(" not correct");
                        //$e = oci_error($stmt);
                        //trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
                    }
                    else {
                        //responce data
                        $response->getBody()->write("true");
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
            } 
            else 
            {
                $response->getBody()->write("id not correct");
            }   
        });
});



//post delete item
$cart->post('/api/cart/delete/{id}', function (Request $request, Response $response){
});

