<?php

namespace Athorrent\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface {
    private $userId;

    private $username;

    private $password;

    private $salt;

    private $creationTimestamp;

    private $connectionTimestamp;

    private $roles;

    public function __construct($userId, $username, $password = null, $salt = null, $creationTimestamp = null, $connectionTimestamp = null) {
        $this->userId = $userId;
        $this->username = $username;
        $this->password = $password;

        if (is_string($salt)) {
            $this->salt = $salt;
        } else {
            $this->salt = md5(time());
        }

        $this->creationTimestamp = $creationTimestamp;
        $this->connectionTimestamp = $connectionTimestamp;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setRawPassword($password) {
        global $app;
        $this->password = $app['security.encoder.digest']->encodePassword($password, $this->salt);
    }

    public function getSalt() {
        return $this->salt;
    }

    public function getCreationTimestamp() {
        return $this->creationTimestamp;
    }

    public function getConnectionTimestamp() {
        return $this->connectionTimestamp;
    }

    public function updateConnectionTimestamp() {
        global $app;

        $sth = $app['pdo']->prepare('UPDATE user SET connectionTimestamp = FROM_UNIXTIME(:connectionTimestamp) WHERE userId = :userId');

        $sth->bindValue('connectionTimestamp', time(), \PDO::PARAM_INT);
        $sth->bindValue('userId', $this->userId, \PDO::PARAM_INT);

        $sth->execute();
    }

    public function getRoles() {
        if ($this->roles === null) {
            $this->roles = array_map(function ($userRole) {
                return $userRole->getRole();
            }, UserRole::loadByUserId($this->userId));
        }

        return $this->roles;
    }

    public function eraseCredentials() {
    }

    public function save() {
        global $app;

        if ($this->userId === null) {
            $sth = $app['pdo']->prepare('INSERT INTO user(username, password, salt) VALUES(:username, :password, :salt)');

            $sth->bindValue('username', $this->username);
            $sth->bindValue('password', $this->password);
            $sth->bindValue('salt', $this->salt);

            $sth->execute();
            $this->userId = $app['pdo']->lastInsertId();
        } else {
            $sth = $app['pdo']->prepare('UPDATE user SET username = :username, password = :password WHERE userId = :userId');

            $sth->bindValue('username', $this->username);
            $sth->bindValue('password', $this->password);
            $sth->bindValue('salt', $this->salt);
            $sth->bindValue('userId', $this->userId, \PDO::PARAM_INT);

            $sth->execute();
        }

        return $sth->rowCount() === 1;
    }

    public static function deleteByUserId($userId) {
        global $app;

        $sth = $app['pdo']->prepare('DELETE FROM user WHERE userId = :userId');
        $sth->bindValue('userId', $userId, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->rowCount() === 1;
    }

    public static function exists($username) {
        global $app;

        $sth = $app['pdo']->prepare('SELECT userId FROM user WHERE username = :username');
        $sth->bindValue('username', $username);
        $sth->execute();

        return $sth->fetch(\PDO::FETCH_ASSOC) !== false;
    }

    public static function loadByUsername($username) {
        global $app;

        $sth = $app['pdo']->prepare('SELECT userId, password, salt, creationTimestamp, connectionTimestamp FROM user WHERE username = :username');
        $sth->bindValue('username', $username);
        $sth->execute();

        $row = $sth->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new self($row['userId'], $username, $row['password'], $row['salt'], $row['creationTimestamp'], $row['connectionTimestamp']);
    }

    public static function loadAll($offset, $limit, &$total) {
        global $app;

        $sth = $app['pdo']->prepare('SELECT SQL_CALC_FOUND_ROWS userId, username, password, salt, creationTimestamp, connectionTimestamp FROM user ORDER BY userId LIMIT :offset, :limit; SELECT FOUND_ROWS()');
        $sth->bindValue('offset', $offset, \PDO::PARAM_INT);
        $sth->bindValue('limit', $limit, \PDO::PARAM_INT);
        $sth->execute();

        $users = array();

        foreach ($sth as $row) {
            $users[] = new self($row['userId'], $row['username'], $row['password'], $row['salt'], $row['creationTimestamp'], $row['connectionTimestamp']);
        }

        $sth->nextRowset();
        $total = $sth->fetch(\PDO::FETCH_COLUMN, 0);

        return $users;
    }
}

?>
