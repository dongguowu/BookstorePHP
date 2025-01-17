<?php

// <editor-fold defaultstate="collapsed" desc="Setup Session & Logger">
session_start();

require_once 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Configure Database Connection">
// FIXME: Replace before submitting (SCOTT)
//DB::debugMode();            //Replace before submitting (SCOTT)

if (false) {
    DB::$user = 'bootstore';
    DB::$dbName = 'bootstore';
    DB::$password = 'vuxunjqTbm5S7sAq';
    DB::$port = 3333;
    DB::$host = 'localhost';
    DB::$encoding = 'utf8';
    DB::$error_handler = 'db_error_handler';
} else {
    DB::$user = 'cp4907_bookstore';
    DB::$dbName = 'cp4907_scott-dongguo-bookstore.ipd15.com';
    DB::$password = 'vuxunjqTbm5S7sAq';
    DB::$port = 3306;
    DB::$host = 'localhost';
    DB::$encoding = 'utf8';
    DB::$error_handler = 'db_error_handler';
}

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Configure Error-Handler">
function db_error_handler($params) {
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error("SQL query: " . $params['query']);
    http_response_code(500);
    $app->render('fatal_error.html.twig');
    die; // don't want to keep going if a query broke
}

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Slim creation and setup">
$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig()
        ));

$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/cache'
);
$view->setTemplatesDirectory(dirname(__FILE__) . '/templates');
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Add User and Session to superglobals">
if (!isset($_SESSION['userId'])) {
    $_SESSION['userId'] = array();
}
if (!isset($_SESSION['sessionId'])) {
    $_SESSION['sessionId'] = session_id();
}
$twig = $app->view()->getEnvironment();
$twig->addGlobal('global_userId', $_SESSION['userId']);
$twig->addGlobal('global_sessionId', $_SESSION['sessionId']);
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Run Index Page (GET)">
$app->get('/', function() use ($app, $log) {
    $pagesize = 3;
    $currentPage = 1;
    $bookClassCode = 'xxx';
    $offsetItmes = ($pagesize * ($currentPage - 1));
    // Books
    DB::query("SELECT id FROM items");
    $TotalItems = DB::count();
    $totalpages = (int) ($TotalItems / $pagesize) + 1;

    $books = DB::query("SELECT * FROM items LIMIT $pagesize OFFSET $offsetItmes ");

    // Fetch first grade of DeweyDecimalClass
    $querStr = "SELECT code, name FROM classes WHERE code LIKE '%00' ORDER BY code";
    $classCodes = DB::query($querStr);

    $app->render('index.html.twig', array(
        'DeweyDecimalClass' => $classCodes,
        'totalpages' => $totalpages,
        'currentPage' => $currentPage,
        'currentBookClass' => $bookClassCode,
        'books' => $books,
    ));
});
// </editor-fold>
// // <editor-fold defaultstate="collapsed" desc="Run '/list/:currentPage/:currentBookClass">
$app->get('/list/:currentPage/:currentBookClass',
        function($currentPage = 1, $currentBookClass = 'xxx') use ($app, $log) {
    $pagesize = 3;

    // Totalpages
    if ($currentBookClass == 'xxx') {   // for all book classes
        DB::query("SELECT id FROM items");
    } else {                            // for special classes
        DB::query("SELECT id FROM items "
                . " WHERE DeweyDecimalClass LIKE %s"
                . " ", substr($currentBookClass, 0, 1) . '%%');
    }
    $TotalItems = DB::count();
    $totalpages = (int) (($TotalItems - 1) / $pagesize) + 1;
    if ($currentPage > $totalpages) {
        $currentPage = $totalpages;                         //Should this be $current = $totalpages - 1;
    }

    $offsetItmes = ($pagesize * ($currentPage - 1));
    // Books List
    if ($currentBookClass == 'xxx') {  // for all book classes
        $booksList = DB::query("SELECT * FROM items "
                        . " LIMIT $pagesize OFFSET $offsetItmes");
    } else {
        $booksList = DB::query("SELECT * FROM items "
                        . " WHERE DeweyDecimalClass LIKE %s"
                        . " LIMIT $pagesize OFFSET $offsetItmes", substr($currentBookClass, 0, 1) . '%%');
    }



    // DeweyDecimalClass
    $querStr = "SELECT DISTINCT c.code, c.name "
            . " FROM classes as c "
            . " INNER JOIN items as i ON c.code=i.DeweyDecimalClass";
    $classCodes = DB::query($querStr);

    // CurrentBookClass
    if (is_numeric($currentBookClass) && strlen($currentBookClass) > 0) {
        $currentBookClass = substr($currentBookClass, 0, 3);
    } else {
        $currentBookClass = 'xxx';
    }
    // Render
    $app->render('index.html.twig', array(
        'DeweyDecimalClass' => $classCodes,
        'totalpages' => $totalpages,
        'currentPage' => $currentPage,
        'currentBookClass' => $currentBookClass,
        'books' => $booksList,
    ));
});
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Index Page (with CRITERIA)">
/*
  $app->get('/scot/:criteria1/:criteria2/:criteria3', function(

  $criteria1 = 'all',
  $criteria2 = 'null',
  $criteria3 = 'null') use ($app, $log) {
  switch ($criteria1) {
  case("all"): {
  $books = DB::query("SELECT * FROM items");
  }
  case("new"): {
  //Does nothing as the items have no timestamp
  $books = DB::query("SELECT * FROM items");
  }
  case("below10"): {
  $books = DB::query("SELECT * FROM items WHERE price < 10.00");
  }
  case("greater99"): {
  $books = DB::query("SELECT * FROM items WHERE price > 99.99");
  }
  case("author"): {
  $books = DB::query("SELECT * FROM items WHERE author=%s", $criteria2);
  }


  //Need alot more...
  }
  $app->render('index.html.twig', array('books' => $books));
  });
 */
// </editor-fold> 
// <editor-fold defaultstate="collapsed" desc="Login Page (GET)">
$app->get('/login', function() use ($app, $log) {
//  No Check on userId needed, if user is already 
//  logged in they can change accounts by logging in.
    $app->render('login.html.twig');
});
// </editor-fold> 
// <editor-fold defaultstate="collapsed" desc="Login Page (POST)">
$app->post('/login', function() use ($app, $log) {
    $email = $app->request()->post('email');
    $password = $app->request()->post('password');
    $password = md5($password);
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);

    if ($user && ($user['password'] == $password)) {
        $_SESSION['userId'] = $user['id'];              // login by userId
        $_SESSION['sessionId'] = session_id();          // and current sessionId
        //$app->render('index.html.twig', array('userId' => $_SESSION['userId']));

        $app->redirect('/');
    } else {
        $app->render('login.html.twig', array('error' => true));
    }
});
// </editor-fold> 
// <editor-fold defaultstate="collapsed" desc="Logout Page">
$app->get('/logout', function() use ($app, $log) {
    if ($_SESSION['userId']) {
        $_SESSION['userId'] = array();              // destroy userId
        $_SESSION['sessionId'] = array();           // and sessionId
        $app->render('logout.html.twig');
    } else {
        $_SESSION['userId'] = array();
        $log->addAlert('Unregistered user tried to LOGOUT');
        $app->redirect('/');
    }
});
// </editor-fold> 
// <editor-fold desc="Cart Page">
$app->get('/cart', function() use ($app, $log) {
    if ($_SESSION['userId']) {
        $userID = $_SESSION['userId'];
        $items = DB::query(""
                        . "SELECT c.id, u.email, i.title, c.createdTS "
                        . "FROM cartitems AS c "
                        . "INNER JOIN items As i "
                        . "ON c.itemId=i.id "
                        . "INNER JOIN users As u "
                        . "ON c.userId=u.id "
                        . "WHERE c.userID=%s "
                        . "ORDER BY c.createdTS ASC", $userID
        );
    } else {
        $items = DB::query(""
                        . "SELECT c.id, u.email, i.title, c.createdTS "
                        . "FROM cartitems AS c "
                        . "INNER JOIN items As i "
                        . "ON c.itemId=i.id "
                        . "INNER JOIN users As u "
                        . "ON c.userId=u.id "
                        . "WHERE c.sessionId=%s "
                        . "ORDER BY c.createdTS ASC", session_id());
    }
    $app->render('cart.html.twig', array('cartitems' => $items));
});
// </editor-fold> 
// <editor-fold desc="Transaction History Page">
$app->get('/transactionhistory', function() use ($app, $log) {
    if ($_SESSION['userId']) {
        $items = DB::query(""
                        . "SELECT * "
                        . "FROM orderitems "
                        . "INNER JOIN orders "
                        . "ON orderitems.orderId=orders.id "
                        . "WHERE orders.userId=%s", $_SESSION['userId']);

//If we add a timestamp to the orders we can return the
//transacrion history in chroniclogical order with
//"ORDER BY orders.timestamp ASC"

        $app->render('transactionhistory.html.twig', array('items' => $items));
    } else {
        $log->addAlert('Unregistered user tried to Access TRANSACTION HISTORY');
        $app->render('index.html.twig');
    }
});
// </editor-fold>
// <editor-fold desc="Sell History Page">
$app->get('/sellhistory', function() use ($app, $log) {

    if ($_SESSION['userId']) {
        $items = DB::query("SELECT * FROM items WHERE sellerId=%s", $_SESSION['userId']);
        $app->render('sellhistory.html.twig', array('items' => $items));
    } else {
        $log->addAlert('Unregistered user tried to Access SALES HISTORY');
        $app->redirect('/');
    }
});
// </editor-fold> 
// <editor-fold desc="Sell Page (GET)">
$app->get('/sell', function() use ($app, $log) {
    if ($_SESSION['userId']) {
        $app->render('sell.html.twig');
    } else {
        $log->addAlert('Unregistered user tried to Access SELL');
        $app->render('index.html.twig');
    }
});
// </editor-fold> 
// <editor-fold desc="Sell Page (POST)">
$app->post('/sell', function() use ($app, $log) {
    $title = $app->request()->post('title');
    $description = $app->request()->post('description');
    $conditionofused = $app->request()->post('conditionofused');
    $author = $app->request()->post('author');
    $ISBN = $app->request()->post('ISBN');
    $price = $app->request()->post('price');
    $DeweyDecimalClass = $app->request()->post('DeweyDecimalClass');
    $type1 = $app->request()->post('type1');
    $type2 = $app->request()->post('type2');
    $type3 = $app->request()->post('type3');

    $imageDir = $app->request()->post('image');

    $errorList = array();

    if (!$errorList) {
        DB::insert('items', array(
            'title' => $title,
            'image' => $imageDir,
            'description' => $description,
            'conditionofused' => $conditionofused,
            'author' => $author,
            'ISBN' => $ISBN,
            'price' => $price,
            'DeweyDecimalClass' => $DeweyDecimalClass,
            'type1' => $type1,
            'isFrontPage' => 0,
            'type2' => $type2,
            'type3' => $type3,
            'sellerId' => $_SESSION['userId']));

//Or go to the Item.html.twig for the newly added item?
        $app->render('item_add_success.html.twig');
    } else {
        $app->render('item_add_success.html.twig', array('errors' => errorList));
    }
});
// </editor-fold> 
// <editor-fold desc="Registration Page (GET)">
$app->get('/register', function() use ($app, $log) {
//  No Check on userId needed, if user is already 
//  logged in they can register a new account.   
    $app->render('register.html.twig');
});
// </editor-fold> 
// <editor-fold desc="Registration Page (POST)">
$app->post('/register', function() use ($app, $log) {
    $email = $app->request()->post('email');
    $password1 = $app->request()->post('password1');
    $password2 = $app->request()->post('password2');
    $values = array(
        'email' => $email,
        'password1' => $password1,
        'password1' => $password2);
    $errorList = array();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        unset($values['email']);
        unset($values['password1']);
        unset($values['password2']);
        array_push($errorList, "The provided E-mail address is invalid");
    }

    if (DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email)) {
        unset($values['password1']);
        unset($values['password2']);
        array_push($errorList, "This E-mail address is already in user");
    }

    if (strlen($password1) < 6) {
        unset($values['password1']);
        unset($values['password2']);
        array_push($errorList, "Passwords must be six characters or longer");
    }

    if ($password1 != $password2) {
        unset($values['password1']);
        unset($values['password2']);
        array_push($errorList, "Your passwords do not match");
    }

    if (!$errorList) {
        $passwordMd5 = md5($password1);
        echo '$passwordMd5';
        DB::insert('users', array(
            'email' => $email,
            'password' => $passwordMd5
        ));

        $_SESSION['userId'] = DB::insertId();

        $app->render('registration_successful.html.twig');
    } else {
        $app->render('register.html.twig', array('errorList' => $errorList, 'values' => $values));
    }
});
// </editor-fold> 
// <editor-fold desc="Run /cart/add/:id Page (POST)">
$app->post('/cart/add/:itemId', function($itemId) use ($app, $log) {
// validate parameters
    $existing = DB::query("SELECT id FROM items WHERE id=%d", $itemId);
    if (!$existing) {
        echo $itemId . "not found";
        return;
    }

    $sessionId = session_id();

    if (!isset($_SESSION['userId'])) {
        DB::insert('cartitems', array(
            'userId' => '1',
            'itemId' => $itemId,
            'sessionId' => $sessionId
        ));
    } else {
        $userId = $_SESSION['userId'];
        DB::insert('cartitems', array(
            'userId' => $userId,
            'itemId' => $itemId,
            'sessionId' => $sessionId
        ));
    }


    $id = DB::insertId();
    $cartItem = DB::queryFirstRow("SELECT * FROM cartitems WHERE id=%i", $id);
    $app->render('cart_add_success.html.twig', array(
        'v' => $cartItem
    ));
});
// </editor-fold> 
// <editor-fold desc="Run /cart/remove/:id Page (POST)">
$app->post('/cart/remove/:id', function($id) use ($app, $log) {
// validate parameters
    $cartItem = DB::query("SELECT id FROM cartitems WHERE id=%d", $id);
    if (!$cartItem) {
        echo $id . " not found";
        return;
    }
    if ($_SESSION['userId']) {
        DB::delete('cartitems', "id=%i AND userId=%i", $id, $_SESSION['userId']);
    } else {
        DB::delete('cartitems', "id=%i AND sessionId=%i", $id, $_SESSION['sessionId']);
    }

    $cartItem = DB::query("SELECT id FROM cartitems WHERE id=%d", $id);
    if ($cartItem) {
        echo $id . " not deleted";
        return;
    } else {
        $app->render('cart_remove_success.html.twig', array(
            'id' => $id
        ));
    }
});
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Run /admin/item/add Page (GET POST)">
$app->get('/admin/item/:action(/:id)', function($action, $id = -1) use ($app, $log) {
    // only login user can continute 
    if (!$_SESSION['userId']) {
        $app->render('access_denied.html.twig');
        return;
    }

// validate parameters
    if (($action == 'add' && $id != -1) || ($action == 'edit' && $id == -1)) {
        $app->notFound();
        return;
    }
// stage 1 get form
    if ($action == 'edit') {
        $item = DB::queryFirstRow("SELECT * FROM items WHERE id=%i", $id);
        if (!$item) {
            $app->notFound();
            return;
        }
        $log->debug("preparing to edit item with id=" . $id);
        $app->render('item_addedit.html.twig', array(
            'action' => 'edit',
            'v' => $item)
        );
    } else {
        $app->render('item_addedit.html.twig', array('action' => 'add'));
    }
});
$app->post('/admin/item/:action(/:id)', function($action, $id = -1) use ($app, $log) {
    // only login user can continute 
    if (!$_SESSION['userId']) {
        $app->render('access_denied.html.twig');
        return;
    }
    if (($action == 'add' && $id != -1) || ($action == 'edit' && $id == -1)) {
        $app->notFound();
        return;
    }

// -----------------debugging --------------------
//    var_dump($_SESSION['user']);
//    var_dump($_FILES);
//    echo '<hr />';
//    var_dump($_POST);
//    echo '<hr />';
// -----------------debugging --------------------
// 
    $id = $app->request()->post('id');
    $title = $app->request()->post('title');
    $author = $app->request()->post('author');
    $isbn = $app->request()->post('isbn');
    $description = $app->request()->post('description');
    $condition = $app->request()->post('condition');
    $bookclass = $app->request()->post('bookclass');
    $price = $app->request()->post('price');
    $imageData = null;
    $mimeType = null;
    $valueList = array(
        'id' => $id,
        'title' => $title,
        'author' => $author,
        'isbn' => $isbn,
        'price' => $price,
        'condition' => $condition,
        'bookclass' => $bookclass,
        'description' => $description,
        'image' => $imageData,
        'mimeType' => $mimeType
    );

//
    $errorList = array();
    if (strlen($title) < 2 || strlen($title) > 200) {
        array_push($errorList, "Title($title) must be 2-200 characters long");
    }
    if (strlen($author) < 2 || strlen($author) > 100) {
        array_push($errorList, "Author($author) must be 2-100 characters long");
    }
    if (strlen($isbn) < 2 || strlen($isbn) > 30) {
        array_push($errorList, "ISBN($isbn) invalid");
    }
    if (strlen($description) < 20 || strlen($description) > 2000) {
        array_push($errorList, "Description must be 20-2000 characters long");
    }
    if ($condition < 40 || $condition > 100) {
        array_push($errorList, "Condition($condition) must be 40-100");
    }
    if (!is_numeric($price) || $price <= 0 || $price > 999.99) {
        array_push($errorList, "Price($price) invalid");
    }
    if (strlen($bookclass) != 3) {
        array_push($errorList, "Book class($bookclass) invalid");
    }


// 
    if ($_FILES['image']['size'] == 0) {
        array_push($errorList, "Image is empty");
    } else {
        $image = $_FILES['image'];
        $imageInfo = getimagesize($image['tmp_name']);
        if (!$imageInfo) {
            array_push($errorList, "File does not look like a valid image");
        } else {
// never allow '..' in the file name
            if (strstr($image['name'], '..')) {
                array_push($errorList, "File name invalid");
            }
// only allow select extensions
            $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, array('jpg', 'jpeg', 'gif', 'png'))) {
                array_push($errorList, "File extension invalid");
            }
// check mime-type submitted
//$mimeType = $image['type']; // TODO: use getimagesize result mime-type instead
            $mimeType = $imageInfo['mime'];
            if (!in_array($mimeType, array('image/gif', 'image/jpeg', 'image/jpg', 'image/png'))) {
                array_push($errorList, "File type invalid");
            }

// 
            $imageData = file_get_contents($image['tmp_name']);
            $valueList['image'] = $imageData;
            $valueList['mimeType'] = $mimeType;
        }
    }

//
    if ($errorList) {
        $app->render('item_addedit.html.twig', array(
            'v' => $valueList, 'errorList' => $errorList));
    } else {
//        echo "post-list: <br>";
//        var_dump(array(
//            'id' => $id,
//            'title' => $title,
//            'author' => $author,
//            'ISBN' => $isbn,
//            'description' => $description,
//            'DeweyDecimalClass' => $bookclass,
//            'conditionofused' => $condition,
//            'price' => $price,            
//            'mimeType' => $mimeType,
//            'image' => $imageData,
//                )
//        );
//        return;
        DB::insert('items', array(
            'id' => $id,
            'title' => $title,
            'author' => $author,
            'ISBN' => $isbn,
            'description' => $description,
            'DeweyDecimalClass' => $bookclass,
            'conditionofused' => $condition,
            'price' => $price,
            'mimeType' => $mimeType,
            'image' => $imageData,
        ));
        $itemId = DB::insertId();
        $app->render('item_addedit_success.html.twig', array('itemId' => $itemId));
    }
});
//        ->conditions(array('action' => '(add|edit)', 'id' => '[0-9]+'));
// </editor-fold> 
// <editor-fold desc="Run /item/:id/image (GET)">
$app->get('/item/:id/image', function($id) use ($app, $log) {
    $item = DB::queryFirstRow("SELECT mimeType, image FROM items WHERE id=%i", $id);
    if (!$item) {
        $app->notFound();
        return;
    }
//    header('Content-Type: image/jpeg');
//    var_dump($item);    
//    return;
    $app->response()->header('Content-Type', $item['mimeType']);
    echo $item['image'];
});
// </editor-fold>
// <editor-fold desc="Run /item/:code/class (GET)">
$app->get('/item/:code/class', function($code) use ($app, $log) {
    switch (strlen($code)) {
        case 1:
            $codelikeStr = $code . '%0';
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$codelikeStr' ORDER BY code";
            $results = DB::query($querStr);
            break;
        case 2:
            $codelikeStr = $code . '%';
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$codelikeStr' ORDER BY code";
            $results = DB::query($querStr);
            break;
        default:
            $codelikeStr = '%00';
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$codelikeStr' ORDER BY code";
            $results = DB::query($querStr);
            break;
    }
    $isFirstOption = true;
    foreach ($results as $row) {
        if ($isFirstOption) {
            $isFirstOption = false;
            echo "<option value='" . $row['code'] . "'>";
            echo $row['name'] . "</option>\n";
        } else {
            echo "<option value='" . $row['code'] . "'>";
            echo $row['name'] . "</option>\n";
        }
    }
});
// </editor-fold>
// <editor-fold desc="Run /item/:code/classStr (GET)">
$app->get('/item/:code/classStr', function($code) use ($app, $log) {
    $results = array();
    switch (strlen($code)) {
        case 1:
            $codelikeStr = $code . '00';
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$codelikeStr'";
            array_push($results, DB::query($querStr));
            break;
        case 2:
            $codelikeStr = substr($code, 0, 1) . '00';
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$codelikeStr'";
            array_push($results, DB::query($querStr));
            $codelikeStr = substr($code, 0, 2) . '0';
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$codelikeStr'";
            array_push($results, DB::query($querStr));
            break;
        default:
            $codelikeStr = substr($code, 0, 1) . '00';
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$codelikeStr'";
            array_push($results, DB::query($querStr));
            $codelikeStr = substr($code, 0, 2) . '0';
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$codelikeStr'";
            array_push($results, DB::query($querStr));
            $querStr = "SELECT code, name FROM classes WHERE code LIKE '$code'";
            array_push($results, DB::query($querStr));
            break;
    }
//    var_dump($results);
//    return;
    echo '<p>' . $results[0][0]['name'] . '</p>';
    echo '<p>' . $results[1][0]['name'] . '</p>';
    echo '<p>' . $results[2][0]['name'] . '</p>';
});
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Run /isemailregistered">
$app->get('/isemailregistered/:email', function($email) use ($app, $log) {
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    echo ($user) ? "Email already in use." : "";
});
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Run /test (GET)">
$app->get('/test', function() use ($app, $log) {
    var_dump($_SESSION);
    echo '<hr>';
    print_r($_SESSION);
    echo '<hr>';
    if (!isset($_SESSION['userId'])) {
        echo 'isset works';
    } else {
        echo 'isset does\'t works';
    }
    echo '<hr>';
    if (!$_SESSION['userId']) {
        echo "session-userId is false";
    } else {
        echo 'session-userId does\'t works';
    }

    echo '<hr>';

    if ($_SESSION['userId'] == '') {
        echo "session-userId equals epmty string";
    } else {
        echo 'session-userId not equals epmty string';
    }
});
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="user-description">
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="user-description">
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="MeekroDB Actions">

/*
  //INSERT new item
  DB::insert('items', array(
  'ISBN' => $ISBN,
  //Other stuff
  'imagePath' => $image['name']
  ));

  //
  $log->debug("Adding with new Id = " . DB::insertId());

  $itemId = DB::insertId();
  $app->render('item_add_success.html.twig', array('productId' => $productId));






  //INSERT new User
  DB::insert('items', array(
  'email' => $email,
  //Other Stuff
  'password' => $password
  ));

  //$log->debug("Adding with new Id = " . DB::insertId());

  $UserId = DB::insertId();
  $_SESSION['userId'] = $userID;
  $app->render('index.html.twig', $_SESSION['userId']);





  //Query for Registered User
  //
  //  NOT WORKING YET
  //
  $userEmail = $app->request()->post('userEmail');


  $userProfile = DB::queryFirstRow("SELECT * FROM users WHERE id=%i", $userId);

  if (!$userProfile)
  {
  $app->notFound();
  return;
  }
  $app->render('product_view.html.twig', array('p' => $product));
  });








  //Query All


  //Index's Sidebar is comprised of text links
  //<a href = index/~~~~/~~~~>~~~~~~~</a>



  //case('all') //TAKEN FROM URL TOKEN
  $items = DB::query("SELECT * FROM items");
  $app->render('index.html.twig', array('items' => $items));



  //Query Type1, type2, type3

  //case('type1')     //TAKEN FROM URL TOKEN
  //$type1            //TAKEN FROM URL TOKEN

  $items = DB::query("SELECT * FROM items WHERE type1=%s", $type1);
  $app->render('index.html.twig', array('items' => $items));




  //Query By Price

  //case('highprice')     //TAKEN FROM URL TOKEN
  //$price                //TAKEN FROM URL TOKEN

  $items = DB::query("SELECT * FROM items WHERE price>%d", $price);
  $app->render('index.html.twig', array('items' => $items));



  //Order by Author


  //Add the same functionality as the sidebar
  //links to the author line of the main



  //case('author')     //TAKEN FROM URL TOKEN
  //$author            //TAKEN FROM URL TOKEN

  $items = DB::query("SELECT * FROM items WHERE author=%s", $author);
  $app->render('index.html.twig', array('items' => $items));




  //Query Disctinct ISBN's

  //case('ISDN')     //TAKEN FROM URL TOKEN

  $items = DB::query("SELECT DISTINCT ISBN FROM items");
  $app->render('index.html.twig', array('items' => $items));





  //Query Users transaction history

  $userID = $_SESSION['userId'];

  $items = DB::query(""
  . "SELECT * "
  . "FROM orderitems "
  . "INNER JOIN orders "
  . "ON orderitems.orderId=orders.id "
  . "WHERE orders.userId=%s", $userID
  );


  //If we add a timestamp to the orders we can return the
  //transacrion history in chroniclogical order with

  //"ORDER BY orders.timestamp ASC"


  $app->render('transactionhistory.html.twig', array('items' => $items));


  //Query Users Sale History

  $userID = $_SESSION['userId'];

  $items = DB::query("SELECT * FROM items WHERE sellerId=%s", $userID);
  $app->render('sellhistory.html.twig', array('items' => $items));




  //Query Users Cart (SessionId)


  $sessionId = session_id();

  $items = DB::query(""
  . "SELECT * "
  . "FROM cartitems "
  . "INNER JOIN items "
  . "ON cartitems.itemId=items.id "
  . "WHERE cartitems.sessionId=%s "
  . "ORDER BY cartitems.createdTS ASC", $userID
  );

  $app->render('cart.html.twig', array('items' => $items));





  //Query Users Cart (UserId)

  $userID = $_SESSION['userId'];

  $items = DB::query(""
  . "SELECT * "
  . "FROM cartitems "
  . "INNER JOIN items "
  . "ON cartitems.itemId=items.id "
  . "WHERE cartitems.userId=%s "
  . "ORDER BY cartitems.createdTS ASC", $userID
  );

  $app->render('cart.html.twig', array('items' => $items));




  //Add Item to Cart

  $item = DB::query("SELECT id FROM items WHERE id=%d", $itemId);

  DB::insert('cartitems', array(
  'userid'=>$_SESSION['userId'],
  'itemId'=>$item['id'],
  'sessionId'=>$_SESSION['sessionId']));



  //Remove Item from Cart
  $itemId = $app->request()->post('itemId');
  DB::delete('cartitems', "id=%d", $itemId);




  //Remove item
  $itemId = $app->request()->post('itemId');
  DB::delete('items', "id=%d", $itemId);




  //Purchase Transaction(INSERT items in Cart to History, Remove items from Items, Delete items in Cart)

  // 0. Attempt Transaction
  try
  {
  DB::startTransaction();

  // 1: Create New Order
  DB::Insert('orders', array(
  'userId'=>$_SESSION['userId'],
  'address'=>$app->request()->post('address'),
  'postalCode'=>$app->request()->post('postalCode'),
  'phone'=>$app->request()->post('phone'),
  'paymentInfo'=>$app->request()->post('paymentInfo')));

  $orderId = DB::insertId();


  // 2. Get all Cart Items
  $cartItems = DB::query(""
  . "SELECT i.itemId, i.title, i.author, i.ISBN, "
  . "i.price, i.genre, i.type, i.sellerId, i.status"
  . "FROM cartitems AS ci, items AS o"
  . "INNER JOIN items"
  . "ON cartitems.itemId=items.id "
  . "WHERE cartitems.userId=%s ", $userID);



  // 3. Add CartItems to OrderItems
  foreach($cartItems as $cartItem)
  {
  DB::insert('orderitems', array(
  'orderId'=>$orderId,
  'itemId'=>$cartItem['itemId'],
  'title'=>$cartItem['title'],
  'author'=>$cartItem['author'],
  'ISBN'=>$cartItem['ISBN'],
  'price'=>$cartItem['price'],
  'genre'=>$cartItem['genre'],
  'type'=>$cartItem['type'],
  'sellerId'=>$cartItem['sellerId'],
  'status'=>$cartItem['status']));



  // 4. Delete items from Items table
  DB::delete('items', "id=%d", $cartItem['itemId']);



  // 5. Delete items from CartItems table
  DB::Delete('caritems', "itemId=%d", $cartItem['itemId']);
  }

  // 6. Commit Changes
  DB::commit();
  $app->render('order_success.html.twig', $cartItems);

  }
  // 7. Handle Transaction failure
  catch (MeekroDBException $e)
  {
  DB::rollback();
  sql_error_handler(array(
  'error' => $e->getMessage(),
  'query' => $e->getQuery()));
  }




 */













// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Research Notes">
//BOOTSTRAP - Basic Navbar (Top)
//https://www.w3schools.com/booTsTrap/tryit.asp?filename=trybs_navbar&stacked=h
//BOOTSTRAP - Sticky Navbar (Top)
//https://www.w3schools.com/booTsTrap/tryit.asp?filename=trybs_affix&stacked=h
//Responsive Navigation Bar
//https://www.w3schools.com/howto/howto_js_topnav_responsive.asp
//Top Navigation Bar (CSS)
//https://www.w3schools.com/howto/howto_js_topnav.asp
//Item Added to Card Modal Pop-up
//https://www.w3schools.com/howto/howto_css_modals.asp
//Fixed Sidebar
//https://www.w3schools.com/howto/howto_css_fixed_sidebar.asp
//Rounded Images
//https://www.w3schools.com/howto/howto_css_rounded_images.asp
//Columation
//https://www.w3schools.com/howto/howto_css_two_columns.asp
//BOOTSTRAP DEMO
//https://www.w3schools.com/howto/tryit.asp?filename=tryhow_website_bootstrap4
//Style Cards
//https://www.w3schools.com/howto/howto_css_cards.asp
//How To Create an Icon Bar
//https://www.w3schools.com/howto/howto_css_icon_bar.asp#
//Responsive Top Nav
//https://www.w3schools.com/howto/tryit.asp?filename=tryhow_js_topnav
// </editor-fold>





$app->run();

