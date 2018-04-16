<?php
/**
 * revcom_bot
 *
 * @author - Александр Штокман
 */
header('Content-Type: text/html; charset=utf-8');
// подрубаем API
require_once("vendor/autoload.php");

define('ROOT',dirname(__FILE__));
require_once(ROOT.'/components/telegram_bot.php');
require_once(ROOT.'/components/db.php');



// дебаг
if(true){
	error_reporting(E_ALL & ~(E_NOTICE | E_USER_NOTICE | E_DEPRECATED));
	ini_set('display_errors', 1);
}

// создаем переменную бота
$token = param::token();
$chat_id_chanel = param::chat_id_chanel();
$bot = new \TelegramBot\Api\Client($token,null);

if($_GET["bname"] == "revcombot"){
	$bot->sendMessage("@burgercaputt", "Тест");
}

// если бот еще не зарегистрирован - регистируем
if(!file_exists("registered.trigger")){ 
	/**
	 * файл registered.trigger будет создаваться после регистрации бота. 
	 * если этого файла нет значит бот не зарегистрирован 
	 */
	 
	// URl текущей страницы
	$page_url = "https://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	$result = $bot->setWebhook($page_url);
	if($result){
		file_put_contents("registered.trigger",time()); // создаем файл дабы прекратить повторные регистрации
	} else die("ошибка регистрации");
}

// Команды бота
// пинг. Тестовая
$bot->command('ping', function ($message) use ($bot) {
	$bot->sendMessage($message->getChat()->getId(), 'pong!');
});

// обязательное. Запуск бота
$bot->command('start', function ($message) use ($bot) {
    $answer = "Задавайте вопрос. Например:<b>доступ к прохождению долины</b>\r\n
<b>Так же более полная инстуркция к использованию бота</b> /help";
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// помощ
$bot->command('help', function ($message) use ($bot) {
    $answer = "<b>Праивла использования:</b>\r\n

<b>1)</b>для того чтобы спросить бота достаточно набрать ваш вопрос,напрмер : <b>Что такое масштабирование</b>\r\n
<b>2)</b>не нашли ответ на свой вопрос,попробуйте уточнить или изменить запрос: \r\n
<b>3)</b>если после 5-6 попыток не можете найти ответ на свой вопрос,то сохраните вопрос в базе бота и на него в кратчайшие сроки ответят <b> bot)ваш вопрос </b>например: bot)Что такое масштабирование\r\n
<b>4)</b>бот не будет отвечать если общаться на <b>транслите</b>например: kak dela\r\n
<b>5)</b>Получить список вопросов которые оставили для ответа /answer \r\n
";
    $bot->sendMessage($message->getChat()->getId(), $answer);
});
// список не отвеченных вопросов
$bot->command('answer', function ($message) use ($bot) {
    $answer = answer_list::get_answer_list();
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// передаем картинку
$bot->command('getpic', function ($message) use ($bot) {
	$pic = "http://aftamat4ik.ru/wp-content/uploads/2017/03/photo_2016-12-13_23-21-07.jpg";

    $bot->sendPhoto($message->getChat()->getId(), $pic);
});

// передаем документ
$bot->command('getdoc', function ($message) use ($bot) {
	$document = new \CURLFile('shtirner.txt');

    $bot->sendDocument($message->getChat()->getId(), $document);
});

// Кнопки у сообщений
$bot->command("ibutton", function ($message) use ($bot) {
	$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
		[
			[
				['callback_data' => 'data_test', 'text' => 'Answer'],
				['callback_data' => 'data_test2', 'text' => 'ОтветЪ']
			]
		]
	);

	$bot->sendMessage($message->getChat()->getId(), "тест", false, null,null,$keyboard);
});

// Обработка кнопок у сообщений
$bot->on(function($update) use ($bot, $callback_loc, $find_command){
	$callback = $update->getCallbackQuery();
	$message = $callback->getMessage();
	$chatId = $message->getChat()->getId();
	$data = $callback->getData();
	
	if($data == "data_test"){
		$bot->answerCallbackQuery( $callback->getId(), "This is Ansver!",true);
	}
	if($data == "data_test2"){
		$bot->sendMessage($chatId, "Это ответ!");
		$bot->answerCallbackQuery($callback->getId()); // можно отослать пустое, чтобы просто убрать "часики" на кнопке
	}

}, function($update){
	$callback = $update->getCallbackQuery();
	if (is_null($callback) || !strlen($callback->getData()))
		return false;
	return true;
});

// обработка инлайнов
$bot->inlineQuery(function ($inlineQuery) use ($bot) {
	mb_internal_encoding("UTF-8");
	$qid = $inlineQuery->getId();
	$text = $inlineQuery->getQuery();
	
	// Это - базовое содержимое сообщения, оно выводится, когда тыкаем на выбранный нами инлайн
	$str = "Что другие?
Свора голодных нищих.
Им все равно...
В этом мире немытом
Душу человеческую
Ухорашивают рублем,
И если преступно здесь быть бандитом,
То не более преступно,
Чем быть королем...
Я слышал, как этот прохвост
Говорил тебе о Гамлете.
Что он в нем смыслит?
<b>Гамлет</b> восстал против лжи,
В которой варился королевский двор.
Но если б теперь он жил,
То был бы бандит и вор.";
	$base = new \TelegramBot\Api\Types\Inline\InputMessageContent\Text($str,"Html");
	
	// Это список инлайнов
	// инлайн для стихотворения
	$msg = new \TelegramBot\Api\Types\Inline\QueryResult\Article("1","С. Есенин","Отрывок из поэмы `Страна негодяев`");
	$msg->setInputMessageContent($base); // указываем, что в ответ к этому сообщению надо показать стихотворение
	
	// инлайн для картинки
	$full = "http://aftamat4ik.ru/wp-content/uploads/2017/05/14277366494961.jpg"; // собственно урл на картинку 
	$thumb = "http://aftamat4ik.ru/wp-content/uploads/2017/05/14277366494961-150x150.jpg"; // и миниятюра
	
	$photo = new \TelegramBot\Api\Types\Inline\QueryResult\Photo("2",$full,$thumb);
	
	// инлайн для музыки
	$url = "http://aftamat4ik.ru/wp-content/uploads/2017/05/mongol-shuudan_-_kozyr-nash-mandat.mp3";
	$mp3 = new \TelegramBot\Api\Types\Inline\QueryResult\Audio("3",$url,"Монгол Шуудан - Козырь наш Мандат!");
	
	// инлайн для видео
	$vurl = "http://aftamat4ik.ru/wp-content/uploads/2017/05/bb.mp4";
	$thumb = "http://aftamat4ik.ru/wp-content/uploads/2017/05/joker_5-150x150.jpg";
	$video = new \TelegramBot\Api\Types\Inline\QueryResult\Video("4",$vurl,$thumb, "video/mp4","коммунальные службы","тут тоже может быть описание");
	
	// отправка
	try{
		$result = $bot->answerInlineQuery( $qid, [$msg,$photo,$mp3,$video],100,false);
	}catch(Exception $e){
		file_put_contents("rdata",print_r($e,true));
	}
});

// Reply-Кнопки
$bot->command("buttons", function ($message) use ($bot) {
	$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[["text" => "Власть советам!"], ["text" => "Сиськи!"]]], true, true);

	$bot->sendMessage($message->getChat()->getId(), "тест", false, null,null, $keyboard);
});

// Отлов любых сообщений + обрабтка reply-кнопок
$bot->on(function($Update) use ($bot){
	
	$message = $Update->getMessage();
	$mtext = $message->getText();
	$cid = $message->getChat()->getId();
	
	if(mb_stripos($mtext,"Сиськи") !== false){
		$pic = "http://aftamat4ik.ru/wp-content/uploads/2017/05/14277366494961.jpg";

		$bot->sendPhoto($message->getChat()->getId(), $pic);
	}
	if(mb_stripos($mtext,"власть советам") !== false){
		$bot->sendMessage($message->getChat()->getId(), "Смерть богатым!");
	}
	else {
        $str = $mtext;
        $chat =[
            'id' => $message->getChat()->getId(),
            'type' => $message->getChat()->getType(),
            'title' => $message->getChat()->getTitle(),
            'username' => $message->getChat()->getUsername(),
            'first_name' => $message->getChat()->getFirstName(),
            'last_name' => $message->getChat()->getLastName(),
        ];

        $ignore = form::get_command($str);//если транслит то бот не отвечает
        if($ignore)
        {
        $commands = check::check_command($str,$chat['username'],$chat['id']);
        $chat_id_chanel = param::chat_id_chanel();
        if ($commands) {
            if(is_array($commands))
            {
            $bot->sendMessage($message->getChat()->getId(), $commands['0']);
            $bot->sendMessage($chat_id_chanel, $commands['1']);
            }//вопрос на канал

            else $bot->sendMessage($message->getChat()->getId(), $commands);
        }
        if (!$commands) {
            $answer = sent_variations::sent_var($ignore);
            if ($answer) {
                $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(
                    [
                        [
                            ["text" => $answer['0']]

                        ],
                        [

                            ["text" => $answer['1']]

                        ],
                        [

                            ["text" => $answer['2']]

                        ],
                        [

                            ["text" => "/help не нашли ответ"]

                        ]

                    ],
                    true, true);

                $bot->sendMessage($message->getChat()->getId(), "Вы имели ввиду", false, null, null, $keyboard);
            } else {
                $bot->sendMessage($message->getChat()->getId(), '<b>Попробуйте поменять или уточнить свой запрос</b> '.$chat['first_name'].' '.$chat['last_name']);
            }
        }
    }
    }
}, function($message) use ($name){
	return true; // когда тут true - команда проходит
});

// запускаем обработку
$bot->run();

echo "бот";