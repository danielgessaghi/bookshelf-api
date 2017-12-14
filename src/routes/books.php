<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header("Access-Control-Allow-Origin: *");

$app = new \Slim\App;


// Get all books paged in 10 books for page
$app->get('/api/books/list/{page}', function (Request $request, Response $response){

});
// get book info
$app->get('/api/books/detail/{book_id}', function (Request $request, Response $response){

});
// delete a book ADMIN
$app->post('/api/books/delate/{book_id}', function (Request $request, Response $response){

});
// add a book ADMIN
$app->post('/api/books/add', function (Request $request, Response $response){
  
});
