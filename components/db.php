<?php
class Db
{
    public static function getConnection()
    {
        $paramsPath = ROOT . '/config/db_params.php';
        $params = include($paramsPath);
        $dsn = 'mysql:dbname='.$params['dbname'].';host='.$params['host'];
        $db = new PDO($dsn,$params['user'],$params['password'],$params['options']);

        return $db;
    }
    public static function getPdo($sql){
        $db = self::getConnection();
        $result = $db -> prepare($sql);
        $result -> execute();
        return $result;
    }
    public static function getAnswer($sql,$command){
        $db = self::getConnection();
        $result = $db -> prepare($sql);
        $result -> execute(array(':command' => $command));
        //$result -> execute(array($id));
        return $result;
    }

    public static function takeAnswer($sql,$question,$user_id,$chat_id){
        $db = self::getConnection();
        $result = $db -> prepare($sql);
        $result -> execute(array(':question' => $question,':user_id' => $user_id,'chat_id' => $chat_id));
        //$result -> execute(array($id));
        return $result;
    }
}