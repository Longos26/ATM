<?php
class Post {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function insertTransaction($param) {
        $dt = $param->payload[0];
        $sqlString = "INSERT INTO transactions (user_id, transac_type, amount, balance_after, timestamp) VALUES (?,?,?,?,?)";
        $res = [];
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $success = $stmt->execute([
                $dt->user_id,
                $dt->transac_type,
                $dt->amount,
                $dt->balance_after,
                $dt->timestamp
            ]);
            if ($success) {
                $res = [
                    "success" => true,
                    "message" => "Transaction saved successfully",
                    "id" => $this->pdo->lastInsertId()
                ];
            } else {
                $res = [
                    "success" => false,
                    "error" => "Failed to save transaction"
                ];
            }
        } catch (\Throwable $th) {
            $res = [
                "success" => false,
                "msg" => "Unable to insert data", 
                "error" => $th->getMessage(),
                "code" => $th->getCode()
            ];
        }
        return $res;
    }

    public function updateTransaction($param) {
        $sqlString = "UPDATE transactions SET transac_type=?, amount=?, balance_after=?, timestamp=? WHERE id=?";
        $res = [];
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([
                $param->transac_type,
                $param->amount,
                $param->balance_after,
                $param->timestamp,
                $param->id
            ]);
            $res = ["success" => true, "message" => "Transaction updated successfully"];
        } catch (\Throwable $th) {
            $res = [
                "success" => false,
                "msg" => "Unable to update data", 
                "error" => $th->getMessage(),
                "code" => $th->getCode()
            ];
        }
        return $res;
    }

    public function deleteTransaction($param) {
        $sqlString = "DELETE FROM transactions WHERE id=?";
        $res = [];
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$param]);
            $res = ["success" => true, "message" => "Transaction deleted successfully"];
        } catch (\Throwable $th) {
            $res = [
                "success" => false,
                "msg" => "Unable to delete data", 
                "error" => $th->getMessage(),
                "code" => $th->getCode()
            ];
        }
        return $res;
    }
}
?>