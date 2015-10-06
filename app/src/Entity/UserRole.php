<?php

namespace Athorrent\Entity;

class UserRole {
    private $userId;

    private $role;

    public static $list = array('ROLE_USER', 'ROLE_ADMIN');

    public function __construct($userId, $role) {
        $this->userId = $userId;
        $this->role = $role;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getRole() {
        return $this->role;
    }

    public function save() {
        global $app;

        $sth = $app['pdo']->prepare('INSERT INTO user_role(userId, role) VALUES(:userId, :role)');

        $sth->bindValue('userId', $this->userId, \PDO::PARAM_INT);
        $sth->bindValue('role', $this->role);

        $sth->execute();

        return $sth->rowCount() === 1;
    }

    public static function loadByUserId($userId) {
        global $app;

        $sth = $app['pdo']->prepare('SELECT role FROM user_role WHERE userId = :userId');
        $sth->bindValue('userId', $userId);
        $sth->execute();

        $userRoles = array();

        foreach ($sth as $row) {
            $userRoles[] = new self($userId, $row['role']);
        }

        return $userRoles;
    }

    public static function deleteByUserId($userId) {
        global $app;

        $sth = $app['pdo']->prepare('DELETE FROM user_role WHERE userId = :userId');
        $sth->bindValue('userId', $userId, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->rowCount() > 0;
    }
}

?>
