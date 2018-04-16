<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 21.03.2018
 * Time: 14:22
 */
include_once (ROOT.'/config/telegram_config.php');
class param{
    static function take_param(){
        $paramsPath = 'config/telegram_config.php';
        $params = include($paramsPath);
        return $params;
    }
    static function token(){
        $params = self::take_param();
        return $params['token'];
    }
    static function chat_id_chanel(){
        $params = self::take_param();
        return $params['chat_id_chanel'];
    }
}

//получение подготовленной команды для ,транслит не поддерживается,для общения пользователей
class form{
    static function get_command($text){
        $target = mb_strtolower ($text);
        $mask = preg_replace("/[^а-яё ]/iu", '', $target);
        $str = preg_replace("/ {2,}/"," ",$mask);
        $pieces = explode(" ", $str);
        $m = count($pieces);
        for($i = 0;$i <= $m;$i++){
            if(iconv_strlen($pieces[$i],'UTF-8')>2) {
                $check = mb_substr($pieces[$i], 0, 4);
                if (!$check) break;
                $real .= '*' . $check . '* ';
            }
        }
        return $real;

    }
}
//проверка команды на тип обращения
class check{
    static function check_command($str,$user_id,$chat_id){
        $commands = explode(")", $str);
        $command = trim($commands['0']);
        if(is_numeric($command)){
            $sql = "SELECT `answer`,`answer_details` FROM `franch` WHERE `id` = :command ";
            $results = Db::getAnswer($sql,$command) -> fetch();
            if(!empty($results)) {
                $result = "<b>Общий совет:</b>\r\n ".$results['answer']."\r\n<b>Ваши действия:</b> \r\n ".$results['answer_details'];
            }
            else {$result = '<b>Попробуйте изменить ваш запрос</b>';}
        }
        elseif($command === 'bot'){

            $question = str_replace($commands['0'].")", "", $str);
            $sql = "INSERT INTO `franch_answer` SET `question` = :question, `user_id` = :user_id, `chat_id` = :chat_id";
            Db::takeAnswer($sql,$question,$user_id,$chat_id);
            $result[] = "Ваш вопрос <b>$question</b> сохранен в базе бота";
            $result[] = "@$user_id задал вопрос <b> $question</b> ";
        }
        else {$result = false;}
        return $result;
    }
}
//Выводит уточняющие варианты ответа
class sent_variations{
    static  function sent_var($ignore){
        $sql = "SELECT `id`,`question`FROM `franch` WHERE MATCH(`question`) AGAINST( :command IN BOOLEAN MODE) LIMIT 3";
        $result = Db::getAnswer($sql,$ignore);
        while ($row = $result -> fetch() ) {
            $question[] = $row;
        }
        if($question)
        {
            $answer['0'] =  $question['0']['id'].')'.$question['0']['question'];
            if(empty($question['1']['question']))$answer['1'] ='' ;
            else {$answer['1'] =  $question['1']['id'].')'.$question['1']['question'];}

            if(empty($question['2']['question']))$answer['2'] ='' ;
            else {$answer['2'] =  $question['2']['id'].')'.$question['2']['question'];}
        }
         else {$answer = false ;}

        return $answer;
    }
}
//Выводит список вопросов сохраненых ботом
class answer_list{
    static  function get_answer_list(){
        $sql = "SELECT `question`FROM `franch` WHERE `verify` IS NULL";
        $result = $result = Db::getPdo($sql);
        $i = 0;
        while ($row = $result -> fetch()) {
            $question[] = $row;
            $answer .="<b>".$question[$i]['question']."</b>\r\n";
            $i++;
        }
        return "Список вопросов которые оставили на ответ\r\n".$answer;

    }
}