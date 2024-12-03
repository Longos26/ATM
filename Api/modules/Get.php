<?php
class Get {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getTransactions($userId = null) {
        $sqlString = "SELECT * FROM transactions";
        if ($userId) {
            $sqlString .= " WHERE user _id=?";
        }
        $res = [];
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute($userId ? [$userId] : null);
            $res = $stmt->fetchAll();
        } catch (\Throwable $th) {
            $res = [
                "msg" => "Unable to fetch data", 
                "error" => $th->getMessage(),
                "code" => $th->getCode()
            ];
        }
        return $res;
    }
}
?>