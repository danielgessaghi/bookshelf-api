<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../src/config/db.php';
require '../src/config/email_validation.php';
require '../src/routes/users.php';
require '../src/routes/cart.php';
require '../src/routes/books.php';

$app->run();
