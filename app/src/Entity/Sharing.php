<?php

namespace Athorrent\Entity;

class Sharing {
    private $token;

    private $userId;

    private $path;

    private $creationTimestamp;

    public function __construct($token = null, $userId = null, $path = null, $creationTimestamp = null) {
        $this->token = $token;
        $this->userId = $userId;
        $this->path = $path;
        $this->creationTimestamp = $creationTimestamp;
    }

    public function getToken() {
        return $this->token;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getPath() {
        return $this->path;
    }

    public function save() {
        global $app;

        if ($this->token === null) {
            $this->token = self::generateToken($this->userId, $this->path);

            $sth = $app['pdo']->prepare('INSERT INTO sharing(token, userId, path) VALUES(:token, :userId, :path)');
            $sth->bindValue('token', $this->token);
            $sth->bindValue('userId', $this->userId, \PDO::PARAM_INT);
            $sth->bindValue('path', $this->path);
            $sth->execute();
        }
    }

    public static function loadByToken($token) {
        global $app;

        $sth = $app['pdo']->prepare('SELECT userId, path, UNIX_TIMESTAMP(creationTimestamp) AS creationTimestamp FROM sharing WHERE token = :token');
        $sth->bindValue('token', $token);
        $sth->execute();

        $row = $sth->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new self($token, $row['userId'], $row['path'], $row['creationTimestamp']);
    }

    public static function deleteByToken($token, $userId) {
        global $app;

        $sth = $app['pdo']->prepare('DELETE FROM sharing WHERE token = :token AND userId = :userId');
        $sth->bindValue('token', $token);
        $sth->bindValue('userId', $userId);
        $sth->execute();

        return $sth->rowCount() === 1;
    }

    public static function loadByUserId($userId, $offset, $limit, &$total) {
        global $app;

        $sth = $app['pdo']->prepare('SELECT SQL_CALC_FOUND_ROWS token, path, UNIX_TIMESTAMP(creationTimestamp) AS creationTimestamp FROM sharing WHERE userId = :userId ORDER BY creationTimestamp LIMIT :offset, :limit; SELECT FOUND_ROWS()');
        $sth->bindValue('userId', $userId, \PDO::PARAM_INT);
        $sth->bindValue('offset', $offset, \PDO::PARAM_INT);
        $sth->bindValue('limit', $limit, \PDO::PARAM_INT);
        $sth->execute();

        $sharings = array();

        foreach ($sth as $row) {
            $sharings[] = new self($row['token'], $userId, $row['path'], $row['creationTimestamp']);
        }

        $sth->nextRowset();
        $total = $sth->fetch(\PDO::FETCH_COLUMN, 0);

        return $sharings;
    }

    public static function deleteByUserId($userId) {
        global $app;

        $sth = $app['pdo']->prepare('DELETE FROM sharing WHERE userId = :userId');
        $sth->bindValue('userId', $userId, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->rowCount() > 0;
    }

    public static function loadByPathRecursively($path, $userId) {
        global $app;

        $sth = $app['pdo']->prepare('SELECT token, path, UNIX_TIMESTAMP(creationTimestamp) AS creationTimestamp FROM sharing WHERE (path = :path OR path LIKE :like) AND userId = :userId ORDER BY CHAR_LENGTH(path)');
        $sth->bindValue('path', $path);
        $sth->bindValue('like', $path . '/%');
        $sth->bindValue('userId', $userId, \PDO::PARAM_INT);

        $sth->execute();

        $sharings = array();

        foreach ($sth as $row) {
            $sharings[] = new self($row['token'], $userId, $row['path'], $row['creationTimestamp']);
        }

        return $sharings;
    }

    public static function deleteByPathRecursively($path, $userId) {
        global $app;

        $sth = $app['pdo']->prepare('DELETE FROM sharing WHERE (path = :path OR path LIKE :like) AND userId = :userId');
        $sth->bindValue('path', $path);
        $sth->bindValue('like', $path . '/%');
        $sth->bindValue('userId', $userId, \PDO::PARAM_INT);

        $sth->execute();

        return $sth->rowCount() > 0;
    }

    public static function generateToken($userId, $path) {
        return md5($userId . '/' . $path);
    }
}

?>
