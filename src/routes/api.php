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
        $sql = "select o.ID_ORDER,o.TOT_PRICE,o.ORDER_DATE, i.ISBN, i.TITLE, i.PRICE, r.QUANTITY, R.Id_Order_Items from orders o join ORDER_ITEMS r on r.ID_ORDER = o.ID_ORDER join items i on i.ISBN = r.ID_ITEM WHERE o.ID_USER = '" . $user['USERNAME'] . "' AND o.DELIVERY_STATUS = '1' and r.CANCELLED = 0";
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
                $ord_items = $row['ID_ORDER_ITEMS'];
                $tot = $row['TOT_PRICE'];
                $da = $row['ORDER_DATE'];
                $book = array('ISBN' => $row['ISBN'], 'TITLE' => $row['TITLE'], 'PRICE' => $row['PRICE']);
                //var_dump($book);
                $quant = $row['QUANTITY'];
                //var_dump($quant);
                $newRow = array('ID_ORDER' => $ord,'ID_ORDER_ITEMS'=>$ord_items , 'ORDER_DATE' => $da, 'BOOK' => $book, 'QUANTITY' => $quant, 'TOT_PRICE' => $tot);
                //var_dump($newRow);
                $ret[$idx] = $newRow;
                $idx++;
            }
            //echo json_encode($ret);
            $response->getBody()->write(json_encode($ret));
            $customers = oci_free_statement($stmt);
            oci_close($db);
            return $response;
        } catch (PDOException $e) {
            echo '{"error":{text: ' . $e->getMessage() . '}';
        }
    } else {
        echo "no user";
    }
});

// post new item
$app->post('/api/cart/add/{id}', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $item = $request->getAttribute('id');
        //$price = $request->getAttribute('price');
        
        $query = "insert into orders (id_user,delivery_status,order_date) values ('".$user['USERNAME']."','1', to_char(current_date,'DD-MON-YY HH:MI:SS') )";
        $query1 = "insert into order_items (id_order,quantity,id_item) values ((select id_order from orders where order_date = to_char(current_date,'DD-MON-YY HH:MI:SS') and id_user = '".$user['USERNAME']."'),'1','".$item."')";

        var_dump($query1);

        try
        {
            $db = new db();
            //connect
            $db = $db->connect();
            $stmt = oci_parse($db, $query);
            //check errors
            if (!oci_execute($stmt)) {
                //$response->getBody()->write("not correct");
            } else {
                    //responce data
                    //$response->getBody()->write("true");
                $stmt1 = oci_parse($db, $query1);
                if (!oci_execute($stmt1)) {
                    $response->getBody()->write("not correct");
                } else {
                    $response->getBody()->write("true");
                }
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
        //data for the order
        $data = $request->getParsedBody();
        $quantity = $data["QUANTITY"];
        $tot = $data['TOT_PRICE'];
        $book_item = $data['BOOK'];
        $isbn_book = $book_item['ISBN'];
        $order_id = $data['ID_ORDER'];

        $query = "UPDATE ORDERS SET delivery_status = '2', TOT_PRICE = '".$tot."', ORDER_DATE = CURRENT_DATE WHERE id_user = '".$user['USERNAME']."' and DELIVERY_STATUS = 1";
        $query1 = "UPDATE ORDER_ITEMS set QUANTITY = '".$quantity."' where ID_ORDER = ".$order_id." and ID_ITEM='".$isbn_book."'";
        /*echo "\n ------------query1-------------- \n";
        var_dump($query1);
        echo "\n ------------book_item-------------- \n";
        var_dump($book_item);
        echo "\n ------------data-------------- \n";
        var_dump($data);
        echo "\n -------------------------- \n";*/
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
                $stmt1 = oci_parse($db, $query1);
                if (!oci_execute($stmt1)) {
                    $response->getBody()->write("not correct");
                } else {
                    $response->getBody()->write("true");
                }
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

//post delete order
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
//delete item

$app->post('/api/cart/delete/item/{id}', function (Request $request, Response $response) {

    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];

        $order = $request->getAttribute('id');
        $query =  "update ORDER_ITEMS o set o.CANCELLED = '1' where o.ID_ORDER_ITEMS = '" . $order . "'";

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
        } 
        catch (PDOException $e) 
        {
            echo '{"error":{text: ' . $e->getMessage() . '}';
        }
    }
    else {
        echo "no user";
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
$app->get('/api/category/list', function (Request $request, Response $response) {
    $query = "select * from categories";
    try
    {
        $db = new db();
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } else {
            $ret = [];
            $idx = 0;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $ret[$idx] = $row;
                $idx++;
            }
            $response->getBody()->write(json_encode($ret));
        }
        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    } catch (PDOException $e) {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
});

$app->post('/api/category/sorted/{id}', function (Request $request, Response $response) {
    $category = $request->getAttribute('id');
    $query = " select * from items i where i.ID_CATEGORY = '" . $category . "'";
    try
    {
        $db = new db();
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } else {
            $ret = [];
            $idx = 0;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $ret[$idx] = $row;
                $idx++;
            }
            $response->getBody()->write(json_encode($ret));
        }
        //close connection
        $customers = oci_free_statement($stmt);
        oci_close($db);
        return $response;
    } catch (PDOException $e) {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
});
//////////////////////////////////////TOP 3///////////////////////////////////////////////

//top 3 books
$app->get('/api/top-{num}/book', function (Request $request, Response $response) {
    $max = $request->getAttribute('num');
    $query = 'SELECT * FROM ( select o.ID_ITEM,sum(o.QUANTITY) sum from ORDER_ITEMS o where o.CANCELLED = 0 group by o.ID_ITEM ORDER BY sum DESC  )FETCH NEXT '.$max.' ROWS ONLY';
    try
    {
        $db = new db();
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        if (!oci_execute($stmt)) 
        {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } 
        else 
        {
            $ret = [];
            $idx = 0;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
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
    catch(PDOException $e) 
    {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
}); 
//top 3 categories
$app->get('/api/top-{num}/category', function (Request $request, Response $response) {
    $max = $request->getAttribute('num');
    $query = 'select * from (select c.GENRE, count(i.ID_CATEGORY) sum from ORDER_ITEMS r join items i on i.ISBN = r.ID_ITEM join CATEGORIES c on c.ID_CATEGORY = i.ID_CATEGORY  group by c.GENRE ORDER BY sum DESC ) FETCH NEXT '.$max.' ROWS ONLY';
    try
    {
        $db = new db();
        $db = $db->connect();
        $stmt = oci_parse($db, $query);
        if (!oci_execute($stmt)) 
        {
            $e = oci_error($stmt);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } 
        else 
        {
            $ret = [];
            $idx = 0;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
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
    catch(PDOException $e) 
    {
        echo '{"error":{text: ' . $e->getMessage() . '}';
    }
});

////////////////////////////////////RETURNS//////////////////////////////////////

$app->get('/api/returns/list', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        
        $sql = "select o.ID_ORDER,o.ORDER_DATE, i.ISBN, i.TITLE,i.PRICE, r.QUANTITY, R.Id_Order_Items from orders o join ORDER_ITEMS r on r.ID_ORDER = o.ID_ORDER join items i on i.ISBN = r.ID_ITEM WHERE o.ID_USER = '".$user['USERNAME']."' and r.CANCELLED = 0 minus select o.ID_ORDER,o.ORDER_DATE, i.ISBN, i.TITLE,i.PRICE, r.QUANTITY, R.Id_Order_Items from orders o join ORDER_ITEMS r on r.ID_ORDER = o.ID_ORDER join items i on i.ISBN = r.ID_ITEM join Return e on E.Id_Order_Items = R.Id_Order_Items WHERE o.ID_USER = '".$user['USERNAME']."' and r.CANCELLED = 0";
        //$sql = "select i.ISBN, i.TITLE, i.PRICE, r.QUANTITY, R.Id_Order_Items, e.id_returning_status from orders o join ORDER_ITEMS r on r.ID_ORDER = o.ID_ORDER join items i on i.ISBN = r.ID_ITEM join return e on R.Id_Order_Items = e.Id_Order_Items WHERE o.ID_USER = '" . $user['USERNAME'] . "' and r.CANCELLED = 0";
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
                $ord_items = $row['ID_ORDER_ITEMS'];
                $da = $row['ORDER_DATE'];
                //$book = array('ISBN' => $row['ISBN'], 'TITLE' => $row['TITLE'], 'PRICE' => $row['PRICE']);
                //var_dump($book);
                $quant = $row['QUANTITY'];
                //var_dump($quant);
                $newRow = array('ID_ORDER' => $ord, 'ID_ORDER_ITEMS' => $ord_items, 'ORDER_DATE' => $da, 'ISBN' => $row['ISBN'], 'TITLE' => $row['TITLE'], 'PRICE' => $row['PRICE'], 'QUANTITY' => $quant);
                //var_dump($newRow);
                $ret[$idx] = $newRow;
                $idx++;
            }
            //echo json_encode($ret);
            $response->getBody()->write(json_encode($ret));
            $customers = oci_free_statement($stmt);
            oci_close($db);
            return $response;
        } catch (PDOException $e) {
            echo '{"error":{text: ' . $e->getMessage() . '}';
        }
    } else {
        echo "no user";
    }
});

$app->get('/api/returns_admin/list', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        
        $sql = "select o.ID_ORDER,o.ORDER_DATE, i.ISBN, i.TITLE,i.PRICE, r.QUANTITY, R.Id_Order_Items from orders o join ORDER_ITEMS r on r.ID_ORDER = o.ID_ORDER join items i on i.ISBN = r.ID_ITEM join Return e on E.Id_Order_Items = R.Id_Order_Items WHERE r.CANCELLED = 0";
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
                $ord_items = $row['ID_ORDER_ITEMS'];
                $da = $row['ORDER_DATE'];
                //$book = array('ISBN' => $row['ISBN'], 'TITLE' => $row['TITLE'], 'PRICE' => $row['PRICE']);
                //var_dump($book);
                $quant = $row['QUANTITY'];
                //var_dump($quant);
                $newRow = array('ID_ORDER' => $ord, 'ID_ORDER_ITEMS' => $ord_items, 'ORDER_DATE' => $da, 'ISBN' => $row['ISBN'], 'TITLE' => $row['TITLE'], 'PRICE' => $row['PRICE'], 'QUANTITY' => $quant);
                //var_dump($newRow);
                $ret[$idx] = $newRow;
                $idx++;
            }
            //echo json_encode($ret);
            $response->getBody()->write(json_encode($ret));
            $customers = oci_free_statement($stmt);
            oci_close($db);
            return $response;
        } catch (PDOException $e) {
            echo '{"error":{text: ' . $e->getMessage() . '}';
        }
    } else {
        echo "no user";
    }
});


//delete return item
$app->post('/api/returns/delete/{id}', function (Request $request, Response $response) {

    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];

        $order = $request->getAttribute('id');
        $quantity1 = $request->getParam('QUANTITY');
        $query = "insert into return(id_order_items,quantity,id_returning_status) values ('".$order."','".$quantity1."',1)";
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
    } else {
        echo "no user";
    }
});

$app->post('/api/returns_admin/confirmed/{id}', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $order = $request->getAttribute('id');
        $query = "UPDATE Return SET Id_Returning_Status = 2 where Id_Order_Items = ".$order."";
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
    } else {
        echo "no user";
    }
});


$app->post('/api/returns_admin/completed/{id}', function (Request $request, Response $response) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $order = $request->getAttribute('id');
        $query = "UPDATE Return SET Id_Returning_Status = 3 where Id_Order_Items = ".$order."";
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
    } else {
        echo "no user";
    }
});

