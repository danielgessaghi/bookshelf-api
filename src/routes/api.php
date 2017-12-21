<?php
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;

header("Access-Control-Allow-Origin: *");
session_start();

$app = new \Slim\App;

////////////////////////////////////USERS//////////////////////////////////////
// Get all users
$app->get('/api/users/list', function (Request $request, Response $response) {
    $sql = "SELECT * FROM users";
    $db = new db();
    $db = $db->connect();
    $stmt = oci_parse($db, $sql);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }
    $ret = [];
    $idx = 0;
    while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
        $ret[$idx] = $row;
        $idx++;
    }
    $response->getBody()->write(json_encode($ret));
    $customers = oci_free_statement($stmt);
    oci_close($db);
    return $response;
});
// login user
$app->post('/api/login', function (Request $request, Response $response) {
    $password = $request->getParam('PASSWORD');
    $usermail = $request->getParam('EMAIL');
    $passswordHash = hash("sha256", $request->getParam('PASSWORD'));
    $query = "select * from users WHERE EMAIL = '" . $request->getParam('EMAIL') . "' and PASSWORD = '" . $passswordHash . "'";
    $db = new db();
    $db = $db->connect();
    $stmt = oci_parse($db, $query);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }
    $row = null;
    $row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);
    $user = array('USERNAME' => $row['USERNAME'], 'ID_GROUP' => $row['ID_GROUP']);
    $_SESSION["user"] = $user;

    if ($row != null) {
        $respJSON = array('USERNAME' => $row['USERNAME'], 'FIRSTNAME' => $row['NAME'], 'LASTNAME' => $row['SURNAME'], 'ID_GROUP' => $row['ID_GROUP'], 'EMAIL' => $row['EMAIL']); //MORE DATA ADD THERE
        $response->getBody()->write(json_encode($respJSON));
    } else {
        $response->getBody()->write(json_encode(false));
    }
    //close connection
    $customers = oci_free_statement($stmt);
    oci_close($db);
    return $response;
});

// register a new user
$app->post('/api/register', function (Request $request, Response $response) {
    $email = new email();
    $user = $request->getParam('USERNAME');
    $name = $request->getParam('FIRSTNAME');
    $surname = $request->getParam('LASTNAME');
    $usermail = $request->getParam('EMAIL');
    $phone = $request->getParam('PHONE');
    $cap = $request->getParam('CAP');
    $city = $request->getParam('CITY');
    $country = $request->getParam('COUNTRY');
    $street = $request->getParam('STREET');
    $password = $request->getParam('PASSWORD');
    if ($email->isValid($usermail)) {
        $passswordHash = hash("sha256", $password);
        $query = "insert INTO users (username,name,surname,email,password,phone,cap,city,id_group,country,street) VALUES ('" . $user . "','" . $name . "','" . $surname . "','" . $usermail . "','" . $passswordHash . "','" . $phone . "','" . $cap . "','" . $city . "',1,'" . $country . "','" . $street . "')";
        $db = new db();
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        if (!oci_execute($stmt)) {
            $response->getBody()->write(json_encode(false));
        } else {
            $response->getBody()->write(json_encode(true));
        }
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    } else {
        $response->getBody()->write("email not correct");
    }
});
////////////////////////////////////BOOK//////////////////////////////////////
// Get all books paged in 10 books for page
$app->get('/api/books/list/{page}', function (Request $request, Response $response) {
    $db = new db();
    $db = $db->connect();
    $results_per_page = 10;
    $page = $request->getAttribute('page');
    $start_from = ($page - 1) * $results_per_page;
    $sql = "SELECT * FROM items ORDER BY ISBN OFFSET " . "$start_from" . " ROWS FETCH NEXT 10 ROWS ONLY";
    $stmt = oci_parse($db, $sql);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }
    $ret = []; $i = 0;
    while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
        $ret[$i] = $row;
        $i++;
    }
    $response->getBody()->write(json_encode($ret));
    $customers = oci_free_statement($stmt);
    oci_close($db);
    return $response;
});
//filter of the category
$app->get('/api/books/filter/{category_id}', function (Request $request, Response $response) {
    $category_id = $request->getAttribute('category_id');
    $db = new db();
    $db = $db->connect();
    $sql = "SELECT * FROM items i join categories c on c.id_category = i.ID_CATEGORY where c.GENRE = '" . $category_id . "'";
    try
    {
        $stmt = oci_parse($db, $sql);
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $response->getBody()->write(json_encode($row));
        }
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    } catch (PDOException $e) {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
});
// get book info
$app->get('/api/books/detail/{book_id}', function (Request $request, Response $response) {
    $isbn = $request->getAttribute('book_id');
    $query = "SELECT * FROM items WHERE ISBN = '" . $isbn . "'";
    try
    {
        $db = new db();
        //connect
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
        while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $response->getBody()->write(json_encode($row));
        }
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    } catch (PDOException $e) {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
});

// delete a book ADMIN
$app->post('/api/books/delate/{book_id}', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        if ($user['ID_GROUP'] == 2) {
            $isbn = $result->getAttribute('book_id');
            $strSQL = "DELETE FROM items WHERE isbn = '" . $isbn . "' ";
            try
            {
                $db = new db();
                //connect
                $db = $db->connect();
                $objParse = oci_parse($db, $strSQL);
                $objExecute = oci_execute($objParse);

                if ($objExecute) {
                    oci_commit($db);
                    echo "Record Deleted.";
                } else {
                    oci_rollback($db);
                    $e = oci_error($db);
                    echo "Error Delete [" . $e['message'] . "]";
                }
                oci_close($db);
            } catch (PDOException $e) {
                echo '{"error":{text: ' . $e->getMessage() . '}';
            }
        }
        http_response_code(403);
    }
    http_response_code(404);
});

// add a book ADMIN
$app->post('/api/books/add', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        if ($user['ID_GROUP'] == 2) {
            $isbn = $request->getParam('ISBN');
            $title = $request->getParam('TITLE');
            $author = $request->getParam('AUTHOR');
            $pubblication_date = $request->getParam('PUBBLICATION_DATE');
            $id_category = $request->getParam('ID_CATEGORY');
            $pages = $request->getParam('PAGES');
            $price = $request->getParam('PRICE');
            $query = "insert INTO items (isbn,title,author,pubblication_date,id_category,pages,price) VALUES ('" . $isbn . "','" . $title . "','" . $author . "','" . $pubblication_date . "','" . $id_category . "','" . $pages . "','" . $price . "')";
            try
            {
                $db = new db();
                //connect
                $db = $db->connect();
                $stmt = oci_parse($db, $query);
                //check errors
                if (!oci_execute($stmt)) {
                    $response->getBody()->write("one or more rows are not correct");
                } else {
                    //responce data
                    $response->getBody()->write("true");
                }
                //close connection
                $customers = oci_free_statement($stmt);
                oci_close($db);
                return $response;
            } catch (PDOException $e) {
                echo '{"error":{text: ' . $e->getMessage() . '}';
            }
        }
        http_response_code(403);
    }
    http_response_code(404);
});
////////////////////////////////////CART//////////////////////////////////////

$app->get('/api/cart/list', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $sql = "SELECT orders.ID_ORDER, items.ISBN, items.TITLE, items.AUTHOR, items.PUBBLICATION_DATE, items.PAGES, items.PRICE, orders.QUANTITY FROM orders join items on items.ISBN = orders.ID_ITEM WHERE orders.ID_USER = '" . $user['USERNAME'] . "' AND orders.DELIVERY_STATUS = '1'";
        try
        {
            $db = new db();
            //connect
            $db = $db->connect();
            $stmt = oci_parse($db, $sql);
            if (!oci_execute($stmt)) {
                $e = oci_error($stmt);
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
            $ret = [];
            $idx = 0;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $ord = $row['ID_ORDER'];
                $book = array('ISBN' => $row['ISBN'], 'TITLE' => $row['TITLE'], 'AUTHOR' => $row['AUTHOR'], 'PUBBLICATION_DATE' => $row['PUBBLICATION_DATE'], 'PAGES' => $row['PAGES'], 'PRICE' => $row['PRICE']);
                $quant = $row['QUANTITY'];
                $newRow = array('ID_ORDER' => $ord, 'BOOK' => $book, 'QUANTITY' => $quant);
                $ret[$idx] = $newRow;
                $idx++;
            }
            $response->getBody()->write(json_encode($ret));
            $customers = oci_free_statement($stmt);
            oci_close($db);
            return $response;
        } catch (PDOException $e) {
            echo '{"error":{text: ' . $e->getMessage() . '}';
        }
    }
});

// post new item
$app->post('/api/cart/add/{id}', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $item = $request->getAttribute('id');
        $query = "insert INTO orders(ID_USER, ID_ITEM,QUANTITY, DELIVERY_STATUS) VALUES ('" . $user['USERNAME'] . "', '" . $item . "','1', '1')";
        try
        {
            $db = new db();
            //connect
            $db = $db->connect();
            $stmt = oci_parse($db, $query);
            //check errors
            if (!oci_execute($stmt)) {
                $response->getBody()->write("not correct");
            } else {
                //responce data
                $response->getBody()->write("true");
            }
            //close connection
            $customers = oci_free_statement($stmt);
            oci_close($db);
            return $response;
        } catch (PDOException $e) {
            echo '{"error":{text: ' . $e->getMessage() . '}';
        }
    }
});
//conferm order
$app->post('/api/cart/ordered', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        //var_dump($request->getParam('QUANTITY'));
        $query = "UPDATE orders SET  delivery_status = '2', quantity = '" . $request->getParam('QUANTITY') . "' WHERE id_user = '" . $user['USERNAME'] . "' and DELIVERY_STATUS = 1";

        try
        {
            //connect
            $db = new db();
            $db = $db->connect();
            $stmt = oci_parse($db, $query);
            //check errors
            if (!oci_execute($stmt)) {
                $response->getBody()->write("not correct");
            } else {
                //responce data
                $response->getBody()->write("true");
            }
            //close connection
            $customers = oci_free_statement($stmt);
            oci_close($db);
            return $response;
        } catch (PDOException $e) {
            echo '{"error":{text: ' . $e->getMessage() . '}';
        }
    }
});

//post delete item
$app->post('/api/cart/delete/{id}', function (Request $request, Response $response) {
    $order = $request->getAttribute('id');
    $query = "update orders o set o.DELIVERY_STATUS = '5' where o.ID_ORDER = '" . $order . "'";
    try
    {
        $db = new db();
        //connect
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        //check errors
        if (!oci_execute($stmt)) {
            $response->getBody()->write("not correct");
        } else {
            //responce data
            $response->getBody()->write("true");
        }
        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    } catch (PDOException $e) {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }

});
////////////////////////////////////SEARCH//////////////////////////////////////

$app->post('/api/search', function (Request $request, Response $response) {

    $q = $request->getParam('QUESTION');
    $query = " SELECT * FROM items i join CATEGORIES c on c.ID_CATEGORY = i.ID_CATEGORY WHERE REGEXP_LIKE (i.AUTHOR,'" . $q . "','i') or REGEXP_LIKE (i.ISBN,'" . $q . "','i') or REGEXP_LIKE (i.TITLE,'" . $q . "','i') or REGEXP_LIKE (c.GENRE ,'" . $q . "','i')";
    try
    {
        $db = new db();
        //connect
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        //check errors
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } else {
            //responce data
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $response->getBody()->write(json_encode($row));
            }
        }
        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    } catch (PDOException $e) {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
});
////////////////////////////////////CATEGORY//////////////////////////////////////
$app->get('/api/category/list', function (Request $request, Response $response){
    $query = "select * from categories";
    try
    {
        $db = new db();
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } 
        else 
        {
            $ret = [];
            $idx = 0;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) 
            {
                $ret[$idx] = $row;
                $idx++;
            }
            $response->getBody()->write(json_encode($ret));
        }
        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    }
    catch (PDOException $e) 
    {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
});

$app->post('/api/category/sorted/{id}', function (Request $request, Response $response){
    $category =  $request->getAttribute('id');
    $query = " select * from items i where i.ID_CATEGORY = '".$category."'";
    try
    {
        $db = new db();
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } 
        else 
        {
            $ret = [];
            $idx = 0;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) 
            {
                $ret[$idx] = $row;
                $idx++;
            }
            $response->getBody()->write(json_encode($ret));
        }
        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    }
    catch (PDOException $e) 
    {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
});