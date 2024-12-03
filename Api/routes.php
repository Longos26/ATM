<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");
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

if (!$pdo) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit();
}

$get = new Get($pdo);
$post = new Post($pdo);
$auth = new Auth($pdo);

$req = isset($_REQUEST['request']) ? explode('/', rtrim($_REQUEST['request'], '/')) : ["errorcatcher"];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            switch ($req[0]) {
                case 'gettransactions':
                    if (!isset($req[1])) {
                        throw new Exception("User  ID is required");
                    }
                    $result = $get->getTransactions($req[1]);
                    echo json_encode($result);
                    break;
                default:
                    throw new Exception("Invalid request");
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
        break;

    case 'POST':
        try {
            $rawData = file_get_contents("php://input");
            if (!$rawData) {
                throw new Exception("No data received");
            }
            
            $d = null;
            if ($req[0] === 'encryptdata') {
                $data = json_decode($rawData, true);
                echo json_encode($auth->encryptData($data));
                exit;
            } else {
                $d = $auth->decryptData($rawData);
                if (!$d) {
                    throw new Exception("Invalid encrypted data");
                }
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
                    $result = $post->updateTransaction($d);
                    break;

                case 'deletetransaction':
                    $result = $post->deleteTransaction($d->id);
                    break;

                default:
                    throw new Exception("Invalid request");
            }
            
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
?>