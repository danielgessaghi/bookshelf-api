<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header("Access-Control-Allow-Origin: *");

$app = new \Slim\App;

////////////////////////////////////USERS//////////////////////////////////////
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

// login user
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

// register a new user
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

            }else {
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
////////////////////////////////////BOOK//////////////////////////////////////

// Get all books paged in 10 books for page
$app->get('/api/books/list/{page}', function (Request $request, Response $response){
  $db = new db();
  //connect
  $db = $db->connect();
  $results_per_page = 10;
  $page = $request->getAttribute('page');
  $start_from = ($page-1) * $results_per_page;
  $sql = "SELECT * FROM items ORDER BY ISBN OFFSET "."$start_from"." ROWS FETCH NEXT 10 ROWS ONLY;";
  $result = $conn->query($sql);

  try
  {

      $stmt = oci_parse($db, $sql);
      if (!oci_execute($stmt))
      {
          $e = oci_error($stmt);
          trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
      }

      while ($row = $result->oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS)) {
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


// get book info
$app->get('/api/books/detail/{book_id}', function (Request $request, Response $response){
  $isbn = $result->getAttribute('book_id');
  $query = "SELECT * FROM items WHERE ISBN = "."$isbn";
  try
  {
      $db = new db();
      //connect
      $db = $db->connect();
      $stmt = oci_parse($db, $query);
      if (!oci_execute($stmt))
      {
          $e = oci_error($stmt);
          trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
      }
      while ($row = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS))
      {
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



// delete a book ADMIN
$app->post('/api/books/delate/{book_id}', function (Request $request, Response $response){
    $isbn = $result->getAttribute('book_id');
    $strSQL = "DELETE FROM items WHERE isbn = '".$isbn."' ";
    try
      {
        $db = new db();
        //connect
        $db = $db->connect();
        $objParse = oci_parse($db, $strSQL);
        $objExecute = oci_execute($objParse);

    if($objExecute)
    {
      oci_commit($db);
      echo "Record Deleted.";
    }
    else
    {
      oci_rollback($db);
      $e = oci_error($db);
      echo "Error Delete [".$e['message']."]";
    }
    oci_close($db);
    }
    catch(PDOException $e)
    {
        echo '{"error":{text: '.$e->getMessage().'}';
    }

});


// add a book ADMIN
$app->post('/api/books/add{id_group}', function (Request $request, Response $response){

  //id_group dell'utente loggato
  $id_group_utente = $result->getAttribute('id_group');
  $isbn = $request->getParam('ISBN');
  $title = $request->getParam('TITLE');
  $author = $request->getParam('AUTHOR');
  $pubblication_date = $request->getParam('PUBBLICATION_DATE');
  $id_category = $request->getParam('ID_CATEGORY');
  $pages = $request->getParam('PAGES');
  $price = $request->getParam('PRICE');

  //se utente è ADMIN id_group=0
  if ($id_group_utente == 0) {
  $query = "insert INTO items (isbn,title,author,pubblication_date,id_category,pages,price) VALUES ('".$isbn."','".$title."','".$author."','".$pubblication_date."','".$id_category."','".$pages."','".$price."')";

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
    else {
      echo "you do not have permissions";
    }
});
////////////////////////////////////CART//////////////////////////////////////

$app->get('/api/cart/list', function (Request $request, Response $response){
    $sql = "SELECT * FROM orders WHERE ID_USER = 'Jek'";
    var_dump($sql);
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
$app->post('/api/cart/add/{id}', function (Request $request, Response $response){

    $user = $request->getParam('USERNAME');
    $item = $request->getParam('id');
    $quantity = $request->getParam('QUANTITY');


    $query = "insert INTO orders(ID_USER, ID_ITEM, QUANTITY, DELIVERY_STATUS) VALUES ('".$user."', '".$item."', '".$quantity."', 'ordered')";
                try
                {
                    $db = new db();
                    //connect
                    $db = $db->connect();
                    $stmt = oci_parse($db, $query);
                    //check errors
                    if (!oci_execute($stmt))
                    {
                        $response->getBody()->write("not correct");
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
});

//post delete item
$app->post('/api/cart/delete/{id}', function (Request $request, Response $response){
    $user = $request->getParam('USERNAME');
    $item = $request->getParam('id');
    $quantity = $request->getParam('QUANTITY');
    $query = "delete FROM orders(ID_USER, ID_ITEM, QUANTITY, DELIVERY_STATUS) VALUES ('".$user."', '".$item."', '".$quantity."', 'ordered')";
                try
                {
                    $db = new db();
                    //connect
                    $db = $db->connect();
                    $stmt = oci_parse($db, $query);
                    //check errors
                    if (!oci_execute($stmt))
                    {
                        $response->getBody()->write("not correct");
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

});
////////////////////////////////////SEARCH//////////////////////////////////////

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
