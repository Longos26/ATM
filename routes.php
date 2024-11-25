<?php
require_once("./config/Connection.php");
require_once("./modules/Get.php");
require_once("./modules/Post.php");
require_once("./modules/Auth.php");

$db = new Connection();
$pdo = $db->connect();
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
    echo "No public API available";
    break;

  case 'POST':
    $d = json_decode($auth->decryptData(file_get_contents("php://input")));
    switch ($req[ 0]) {
      case 'gettransactions':
        echo json_encode($get->getTransactions($req[1]));
        break;

      case 'addtransaction':
        echo json_encode($post->insertTransaction($d));
        break;

      case 'updatetransaction':
        echo json_encode($post->updateTransaction($d));
        break;

      case 'deletetransaction':
        echo json_encode($post->deleteTransaction($d->id));
        break;

      // Sample
      case 'encryptpword':
        echo json_encode($auth->encryptPassword("Sample Password"));
        break;

      case 'encryptdata':
        echo json_encode($auth->encryptData(array("data" => "Hello World")));
        break;

      case 'decryptdata':
        echo json_encode($auth->decryptData("eyJkYXRhIjoicG55RW5Jak5RMXpSdzJKTXUzVVRjVjgyQUtqXC9aUVc5b1dKWHNEVkxZT0U9IiwiaXYiOiJPREU0WXpBMk1UbGhPREEzTW1OaE5qVmxNVGd3TURrMU5tUm1ZMlE0WWpFPSJ9"));
        break;
    }
    break;

  default:
    echo "NA";
}