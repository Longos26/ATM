<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once("./config/Connection.php");
require_once("./modules/Get.php");
require_once("./modules/Post.php");
require_once("./modules/Auth.php");

$db = new Connection();
$pdo = $db->connect();

// Check if database connection was successful
if (!$pdo) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        "success" => false,
        "error" => "Database connection failed"
    ]);
    exit();
}

$get = new Get($pdo);
$post = new Post($pdo);
$auth = new Auth($pdo);

if (isset($_REQUEST['request'])) {
    $req = explode('/', rtrim($_REQUEST['request'], '/'));
} else {
    $req = array("errorcatcher");
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        echo json_encode(["error" => "No public API available"]);
        break;

    case 'POST':
        try {
            $rawData = file_get_contents("php://input");
            if (!$rawData) {
                throw new Exception("No data received");
            }
            
            $d = json_decode($auth->decryptData($rawData));
            if (!$d) {
                throw new Exception("Invalid data format: " . json_last_error_msg());
            }

            $result = null;
            switch ($req[0]) {
                case 'gettransactions':
                    $result = $get->getTransactions($req[1]);
                    break;

                case 'addtransaction':
                    $result = $post->insertTransaction($d);
                    if (!$result['success']) {
                        throw new Exception($result['error'] ?? "Failed to save transaction");
                    }
                    break;

                case 'updatetransaction':
                    echo json_encode($post->updateTransaction($d));
                    break;

                case 'deletetransaction':
                    echo json_encode($post->deleteTransaction($d->id));
                    break;

                case 'encryptpword':
                    echo json_encode($auth->encryptPassword("Sample Password"));
                    break;

                case 'encryptdata':
                    echo json_encode($auth->encryptData(array("data" => "Hello World")));
                    break;

                case 'decryptdata':
                    echo json_encode($auth->decryptData("eyJkYXRhIjoicG55RW5Jak5RMXpSdzJKTXUzVVRjVjgyQUt qXC9aUVc5b1dKWHNEVkxZT0U9IiwiaXYiOiJPREU0WXpBMk1UbGhPREEzTW1OaE5qVmxNVGd3TURrMU5tUm1ZMlE0WWpFPSJ9"));
                    break;
            }
            
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => $e->getMessage(),
                "code" => $e->getCode()
            ]);
        }
        break;

    default:
        echo json_encode(["error" => "Method not allowed"]);
        break;
}