<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

require __DIR__ . '/vendor/autoload.php';

$router = new \Bramus\Router\Router();

$router->get('/', function() {
    echo "BLOG API1";
});

function sendResponse($body, $code) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body);
} 

// USER ROUTES
$router->post('/user/new', function() {
    $pdo = new PDO('mysql:host=localhost;dbname=blog-api', 'root', '');
    $jsonData = json_decode(file_get_contents("php://input"), true);
    $data = [
        "username" => $jsonData['username'],
        "email" => $jsonData['email'],
        "password" => $jsonData['password']
    ];
    if(!$data["username"] || !$data["email"] || !$data["password"]) {
        sendResponse("ყველა ველის შევსება აუცილებელია", 300);
    } else if(strlen($data["username"]) < 6) {
        sendResponse("მომხმარებლის სახელი უნდა იყოს 6 ასოზე მეტი", 300);
    } else if(strlen($data["password"]) < 6) {
        sendResponse("მომხმარებლის პაროლი უნდა იყოს 6 ასოზე მეტი", 300);
    } else
    {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->execute($data);
        sendResponse("OK", 200);
    }
});
$router->get("user/get/{userId}", function($userId) {
    $pdo = new PDO('mysql:host=localhost;dbname=blog-api', 'root', '');
    $sth = $pdo->prepare("SELECT * FROM users WHERE id=$userId");
    $sth->execute();
    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
    if(count($data) !== 0) {
        sendResponse($data[0], 200);
    } else {
        sendResponse("ესეთი აიდით იუზერი ვერ მოიძებნა", 300);
    }
});

$router->post("user/login", function() {
    $jsonData = json_decode(file_get_contents("php://input"), true);
    $email = $jsonData["email"];
    $password = $jsonData["password"];
    $pdo = new PDO('mysql:host=localhost;dbname=blog-api', 'root', '');
    $sth = $pdo->prepare("SELECT * FROM users WHERE email='$email' AND password = '$password'");
    $sth->execute();
    $count = $sth->rowCount();
    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
    if($count == 1) {
        sendResponse($data[0], 200);
    } else {
        sendResponse("იმეილი ან პაროლი არასწორია", 300);
    }
});
// USER ROUTES


// BLOG ROUTES
$router->get("/blog/get", function() {
    $pdo = new PDO('mysql:host=localhost;dbname=blog-api', 'root', '');
    $sth = $pdo->prepare("SELECT * FROM blogs ORDER BY id DESC");
    $sth->execute();
    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
    sendResponse($data, 200);
});
$router->get("/blog/get/{blogId}", function($blogId) {
    $pdo = new PDO('mysql:host=localhost;dbname=blog-api', 'root', '');
    $sth = $pdo->prepare("SELECT * FROM blogs WHERE id='$blogId'");
    $sth->execute();
    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
    count($data) == 1 ? sendResponse($data[0], 200) : sendResponse("ესეთი აიდით ბლოგი ვერ მოიძებნა", 300);
});
$router->get("/blog/search", function() {
    $searchVal = isset($_GET["search"]) ? $_GET["search"] : '';
    $pdo = new PDO('mysql:host=localhost;dbname=blog-api', 'root', '');
    $sql = "SELECT * FROM blogs WHERE title LIKE ?";
    $sqlData = array("%$searchVal%");
    $sth = $pdo->prepare($sql);
    $sth->execute($sqlData);
    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
    sendResponse($data, 200);
});

$router->post("/blog/create", function() {
    $title = $_POST["title"];
    $description = $_POST["description"];
    $image = "test";
    $user_id = $_POST["user_id"];
    $tag = $_POST["tag"];
    if(!$title || !$description || !$image || !$tag || !$user_id) {
        return sendResponse("აუცილებელია ყველა ველის შევსება", 300);
    }


    $filename = $_FILES["image"]["name"];

    $tempname = $_FILES["image"]["tmp_name"];  

    $image = "images/".md5(rand(5, 15)).".jpg";
    move_uploaded_file($tempname, $image);

    $pdo = new PDO('mysql:host=localhost;dbname=blog-api', 'root', '');
    $data = [
        "title" => $title,
        "description" => $description,
        "image" => $image,
        "user_id" => $user_id,
        "tag" => $tag,
    ];
    $stmt = $pdo->prepare("INSERT INTO blogs (title, description, image, user_id, tag) VALUES (:title, :description, :image, :user_id, :tag)");
    $stmt->execute($data);
    sendResponse('OK', 200);
});


// BLOG ROUTES



$router->set404(function() {
    header('HTTP/1.1 404 Not Found');
    echo "404";
});

$router->run();

?>