<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header("Access-Control-Allow-Origin: *");

$book = new \Slim\App;


// Get all books paged in 10 books for page
$book->get('/api/books/list/{page}', function (Request $request, Response $response){

});
// get book info
$book->get('/api/books/detail/{book_id}', function (Request $request, Response $response){

});
// delete a book ADMIN
$book->post('/api/books/delate/{book_id}', function (Request $request, Response $response){

});

// add a book ADMIN
$book->post('/api/books/add', function (Request $request, Response $response){
  
});