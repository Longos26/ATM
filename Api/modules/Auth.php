<?php
class Auth {
    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    protected function checkPassword($pword, $hashPword) {
        $hash = crypt($pword, $hashPword);
        return $hash === $hashPword;
    }

    public function encryptPassword($pword) {
        $hashFormat = "$2y$10$";
        $saltLength = 22;
        $salt = $this->generateSalt($saltLength);
        return crypt($pword, $hashFormat . $salt);
    }

    private function generateSalt($len) {
        $urs = md5(uniqid(mt_rand(), true));
        $b64String = base64_encode($urs);
        $mb64String = str_replace('+', '.', $b64String);
        return substr($mb64String, 0, $len);
    }

    public function encryptData($dt) {
        try {
            $json = json_encode($dt);
            $iv = openssl_random_pseudo_bytes(16);
            $key = getenv('ENCRYPTION_KEY') ?: 'your-secure-key-here';
            $encData = openssl_encrypt($json, "AES-256-CBC", $key, 0, $iv);
            if ($encData === false) {
                throw new Exception("Encryption failed");
            }
            $payload = ["data" => $encData, "iv" => base64_encode($iv)];
            return base64_encode(json_encode($payload));
        } catch (Exception $e) {
            throw new Exception("Encryption error: " . $e->getMessage());
        }
    }

    public function decryptData($dt) {
        try {
            $payload = json_decode(base64_decode($dt), true);
            if (!$payload || !isset($payload["data"]) || !isset($payload["iv"])) {
                throw new Exception("Invalid encrypted payload");
            }
            $iv = base64_decode($payload["iv"]);
            $key = getenv('ENCRYPTION_KEY') ?: 'your-secure-key-here';
            $decrypted = openssl_decrypt($payload['data'], "AES-256-CBC", $key, 0, $iv);
            if ($decrypted === false) {
                throw new Exception("Decryption failed");
            }
            return json_decode($decrypted);
        } catch (Exception $e) {
            throw new Exception("Decryption error: " . $e->getMessage());
        }
    }
}