<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

// Get all users 
$app->get('/api/users', function (Request $request, Response $response){
    $sql = "select * FROM user_data";
    try
    {
        $db = new db();
        //connect 
        $db = $db->connect();
        $stmt = oci_parse($db, 'SELECT * FROM users');
        $r = oci_execute($stmt);
        if (!$r) 
        {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        while ($row = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS)) 
        {

            echo json_encode($row);
            
        }
        $customers = oci_free_statement($stmt);
        oci_close($db);
    }
    catch(PDOException $e)
    {
        echo '{"error":{text: '.$e->getMessage().'}';
    }
});

$app->post('/api/login',function (Request $request, Response $response){
    $passswordHash = hash ("sha256" , $request->getParam('PASSWORD'));
    $query = "select * from users WHERE EMAIL = '".$request->getParam('EMAIL')."' and PASSWORD = '".$passswordHash."'";
    try
    {
        $db = new db();
        //connect 
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        $r = oci_execute($stmt);
        //check errors
        if (!$r) 
        {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        //responce data
        while ($row = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS)) 
        {
                return json_encode(true);
        }
        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
    }
    catch(PDOException $e)
    {
        echo '{"error":{text: '.$e->getMessage().'}';
    }

});

$app->post('/api/register',function(Request $request, Response $response){
    $user = $request->getParam('USERNAME');
    $name = $request->getParam('NAME');
    $surname = $request->getParam('SURNAME');
    $email = $request->getParam('EMAIL');
    $phone = $request->getParam('PHONE');
    $cap = $request->getParam('CAP');
    $city = $request->getParam('CITY');
    $country = $request->getParam('COUNTRY');
    $street = $request->getParam('STREET');
    $passswordHash = hash ("sha256" , $request->getParam('PASSWORD'));
    $query = "insert INTO users (username,name,surname,email,password,phone,cap,city,id_group,country,street) VALUES ('".$user."','".$name."','".$surname."','".$email."','".$passswordHash."','".$phone."','".$cap."','".$city."',1,'".$country."','".$street."')";
    try
    {
        $db = new db();
        //connect 
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        $r = oci_execute($stmt);
        //check errors
        if (!$r) 
        {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        else {
            //responce data
            echo json_encode(true);
        }
        
        
        

        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
    }
    catch(PDOException $e)
    {
        echo '{"error":{text: '.$e->getMessage().'}';
    }


});
