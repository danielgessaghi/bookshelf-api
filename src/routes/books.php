<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header("Access-Control-Allow-Origin: *");

$app = new \Slim\App;


// Get all books paged in 10 books for page
$app->get('/api/books/list/{page}', function (Request $request, Response $response){
  $query = "SELECT * FROM items";
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
// get book info
$app->get('/api/books/detail/{book_id}', function (Request $request, Response $response){
  $query = "SELECT * FROM items WHERE ISBN = ";
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
$app->post('/api/books/delate/{book_id}', function (Request $request, Response $response){

});
// add a book ADMIN
$app->post('/api/books/add', function (Request $request, Response $response){

});
