<?php
class UserModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getUserByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND status = 1 LIMIT 1");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }
}