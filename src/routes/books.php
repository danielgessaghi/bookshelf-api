<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header("Access-Control-Allow-Origin: *");

$book = new \Slim\App;


// Get all books paged in 10 books for page
$book->get('/api/books/list/{page}', function (Request $request, Response $response){
  $db = new db();
  //connect
  $db = $db->connect();
  $results_per_page = 10;
  //if (isset($_GET["page"])) { $page  = $_GET["page"]; } else { $page=1; };
  $page = request->getAttribute('page');
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
$book->get('/api/books/detail/{book_id}', function (Request $request, Response $response){
  $isbn = result->getAttribute('book_id');
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


// delete a book ADMIN
$book->post('/api/books/delate/{book_id}', function (Request $request, Response $response){
    $isbn = result->getAttribute('book_id');
    $strSQL = "DELETE FROM items WHERE isbn = '".$isbn"' ";
    try
      {
        $db = new db();
        //connect
        $db = $db->connect();
        $objParse = oci_parse($db, $strSQL);
        $objExecute = oci_execute($objParse);

    if($objExecute)
    {
      oci_commit($db); //*** Commit Transaction ***//
      echo "Record Deleted.";
    }
    else
    {
      oci_rollback($db); //*** RollBack Transaction ***//
      $e = oci_error($db);
      echo "Error Delete [".$e['message']."]";
    }
    oci_close($db);
    }
});


// add a book ADMIN
$book->post('/api/books/add{id_group}', function (Request $request, Response $response){

  //id_group dell'utente loggato
  $id_group_utente = result->getAttribute('id_group');

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
    else {
      echo "you do not have permissions";
    }

});
