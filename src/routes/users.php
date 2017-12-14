<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header("Access-Control-Allow-Origin: *");

$app = new \Slim\App;

// Get all users
$app->get('/api/users/list', function (Request $request, Response $response){
    $sql = "SELECT * FROM users";
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


<<<<<<< HEAD
/*
=======
>>>>>>> f437ae26aeab8c29cbb2f4e7cb4b98ad26c16e47
// login utente
$app->post('/api/login',function (Request $request, Response $response){

    $passswordHash = hash ("sha256" , $request->getParam('PASSWORD'));

    $query = "select * from users WHERE EMAIL = '".$request->getParam('EMAIL')."' and PASSWORD = '".$passswordHash."'";

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
        //responce data
        $row = null;
        $row = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS);
        if ($row !=null) {
            $respJSON = json_encode($row);
            $response->getBody()->write("true");
        } else {
            $response->getBody()->write("false");
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

//
$app->post('/api/register',function(Request $request, Response $response){
    $user = $request->getParam('USERNAME');
    $name = $request->getParam('NAME');
    $surname = $request->getParam('SURNAME');
    $usermail = $request->getParam('EMAIL');
    $phone = $request->getParam('PHONE');
    $cap = $request->getParam('CAP');
    $city = $request->getParam('CITY');
    $country = $request->getParam('COUNTRY');
    $street = $request->getParam('STREET');
    $email = new email();
    $valid = $email->isValid($usermail);
    //var_dump($valid);
    if ($valid)
    {
        $passswordHash = hash ("sha256" , $request->getParam('PASSWORD'));
        $query = "insert INTO users (username,name,surname,email,password,phone,cap,city,id_group,country,street) VALUES ('".$user."','".$name."','".$surname."','".$usermail."','".$passswordHash."','".$phone."','".$cap."','".$city."',1,'".$country."','".$street."')";
        try
        {
            $db = new db();
            //connect
            $db = $db->connect();
            $stmt = oci_parse($db, $query);
            //check errors
            if (!oci_execute($stmt))
            {
                $response->getBody()->write("one or more rows are not correct");
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
        $response->getBody()->write("email not correct");
    }
});
