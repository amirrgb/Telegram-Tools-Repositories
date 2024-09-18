<?php

require __DIR__.'/vendor/autoload.php';

date_default_timezone_set('Asia/Tehran');


use app\TelegramBot;
use DataBase\Database;
use Dotenv\Dotenv;

use function PHPSTORM_META\type;
require_once 'TelegramBot.php';
require_once 'Database.php';



$dotenv = Dotenv::createImmutable(__DIR__ . '/../idea');
$dotenv->load();



$token = $_ENV['BOT_TOKEN'];
$panel_username = $_ENV['PANEL_USERNAME'];
$panel_paswword = $_ENV['PANEL_PASSWORD'];
$dbUsername = $_ENV['DATABASE_USERNAME'];
$dbPassword = $_ENV['DATABASE_PASSWORD'];








$content = file_get_contents('php://input');
$update = json_decode($content, true);

$db = new Database($dbUsername,$dbPassword);
$bot = new TelegramBot($token , $panel_username , $panel_paswword, $dbUsername,$dbPassword);
$chat_id = $update['message']['chat']['id'];
$user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
$command = $user['command'];
$message_id = $user['message_id'];
$bot->deleteMessage($chat_id,$message_id);

$user = $bot->findUser($chat_id);


if($user == null)
{
    $db->insert('users', ['id', 'name', 'username'] , [$chat_id, $update['message']['chat']['first_name'], $update['message']['chat']['username']]);
    $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
}



if (isset($update['callback_query']))
{

    $callbackData = $update['callback_query']['data'];
    $callbackQueryId = $update['callback_query']['id'];
    $chat_id = $update['callback_query']['message']['chat']['id'];
    $bot->answerCallbackQuery($callbackQueryId, $callbackData);





    if (explode( " " ,$callbackData)[1] == 'config')
    {

        $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);
        $template_id = explode( " " ,$callbackData)[0];
        $panel_id = explode( " " ,$callbackData)[2];

        $message_id = $bot->sendMessage($chat_id, "نام کانفیگ خود را وارد کنید");
        $db->update('users', $chat_id, ['command' , 'message'], ['alter_name' , $template_id . " " . $panel_id]);

    }

    elseif(explode( " " ,$callbackData)[1] == 'approve')
    {

        $userId = explode(" ", $callbackData)[0];
        $selected_user = $db->select("SELECT * FROM `users` WHERE `id` = ?", [$userId]);
        $message_id = $selected_user['message_id'];
        $bot->deleteMessage($chat_id, $message_id);
        $db->update('users', explode( " " ,$callbackData)[0], ['is_verified' , 'message_id'], ['approved' , null]);


    }

    elseif(explode( " " ,$callbackData)[1] == 'ban')
    {

        $userId = explode(" ", $callbackData)[0];
        $selected_user = $db->select("SELECT * FROM `users` WHERE `id` = ?", [$userId]);
        $message_id = $selected_user['message_id'];
        $bot->deleteMessage($chat_id, $message_id);
        $db->update('users', explode( " " ,$callbackData)[0], ['is_verified' , 'message_id'], ['baned' , null]);


    }

    elseif(explode( " " ,$callbackData)[1] == 'panel')
    {
        $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,explode( " " ,$callbackData)[2]);
        $bot->deleteMessage($chat_id,$message_id);
        $panel_id = explode( " " ,$callbackData)[0];


        $panel_templates = $db->selectAll("SELECT * FROM panel_templates WHERE panel_id = ? ", [$panel_id]);
        foreach ($panel_templates as $panel_template)
        {
            $template = $db->join('*' , 'panel_templates' , 'templates' , 'panel_templates.template_id = templates.id' , 'panel_templates.template_id = ' . $panel_template['template_id']);
            $name = $template['text'];
            $buttons[] = [['text' => $name, 'callback_data' => strval($template['id'] . ' config ' . $panel_id)]];
        }
        $buttons[] = [['text' => 'بازگشت', 'callback_data' => 'return ']];
        $replyMarkup = json_encode([
            'inline_keyboard' =>
                $buttons

        ]);

        $message_id = $bot->sendMessage($chat_id, 'کانفیگ های این پنل:', $replyMarkup);
        $db->update('users', $chat_id, ['message_id'], [$message_id]);
    }

    elseif(explode( " " ,$callbackData)[1] == 'panel_search')
    {
        $user= $db->select('SELECT * FROM `users` WHERE `id` = ?', [$chat_id]);
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);

        $panel = $db->select("SELECT * FROM `panels` WHERE `id` = ?", [explode( " " ,$callbackData)[0]]);
        $panel_id = $panel['id'];


        $configs = $bot->searchConfigsBYChatId($chat_id , $panel_id);

        if (count($configs) > 0)
        {
            foreach ($configs as $config)
            {
                $buttons[] = [
                    ['text' => $config['name'], 'callback_data' => strval($config['id'] . ' ' . $panel_id . ' config_search')]
                ];
            }
            $buttons[] = [['text' => 'بازگشت', 'callback_data' => 'return ']];
            $keyboard = [
                'inline_keyboard' => $buttons
            ];

            $replyMarkup = json_encode($keyboard);

            $message_id = $bot->sendMessage($chat_id, 'کانفیگ های شما :', $replyMarkup);
        }
        else
        {
            $bot->sendMessage($chat_id, 'کانفیگی برای نمایش وجود ندارد');
        }
        $db->update('users', $chat_id , ['message_id'] , [$message_id] );

    }



    elseif(explode( " " ,$callbackData)[0] == 'خرید')
    {
//        $username . " " . $price . " " . $proxies . " " . $data_limit . " " . $expire
        $user= $db->select('SELECT * FROM `users` WHERE `id` = ?', [$chat_id]);
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);

        $username = explode(" ", $user['message'])[0];
        $price = explode(" ", $user['message'])[1];
        $data_limit = explode(" ", $user['message'])[2];
        $expire = explode(" ", $user['message'])[3];
        $panel_id = explode(" ", $user['message'])[4];
        $template_id = explode(" ", $user['message'])[5];
        $proxies = [];

        $bot->buy($chat_id ,$username, $price, $data_limit, $expire, $proxies , $panel_id , $template_id);

    }

    elseif(explode( " " ,$callbackData)[0] == 'delete')
    {

        $panel = $db->select("SELECT * FROM `panels` WHERE `id` = ?", [explode( " " ,$callbackData)[2]] );
        $db_user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
        $user = $bot->getuser(explode( " " ,$callbackData)[1] , $panel['url']);
        $config = $db->select("SELECT * FROM `configs` WHERE `name` = ? AND `panel_id`=?" , [explode( " " ,$callbackData)[1] , $panel['id']]);
        $price = $config['price'];
        $indebtedness = $db_user['indebtedness'];
        $indebtedness -= $price;
        if($indebtedness < 0)
        {
            $indebtedness = 0;
        }
        $db->update('users' , $chat_id, ['indebtedness'] , [$indebtedness]);
        $message_id = $db_user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);



        if($user['detail'] !== 'User not found')
        {
            $bot->removeuser($panel['url'] , $user['username']);
            $db->deleteConfig('configs' , $user['username'] , $panel['id']);
            $bot->sendMessage($chat_id, 'کانفیگ شما حذف شد');
        }

        else
        {
            $db->deleteConfig('configs' , explode( " " ,$callbackData)[1] , $panel['id']);
            $bot->sendMessage($chat_id, ' کانفیگ شما حذف شد');
        }

    }

    elseif(explode( " " ,$callbackData)[0] == 'sub_link')
    {
        $db = new Database($dbUsername,$dbPassword);
        $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);


        $panel = $db->select("SELECT * FROM `panels` WHERE `id` = ?", [explode( " " ,$callbackData)[2]] );
        $config = $bot->getuser(explode( " " ,$callbackData)[1], $panel['url'])['subscription_url'];
        $url = $panel['url'] . $config;
        $QRCode = $bot->makeQRcode($url);
        $bot->sendImage($chat_id, $QRCode, $config, explode( " " ,$callbackData)[1]);
    }

    elseif(explode( " " ,$callbackData)[0] == 'update')
    {
        $config = $db->select("SELECT * FROM `configs` WHERE `name` = ? AND `panel_id` = ? " , [explode( " " ,$callbackData)[1],explode( " " ,$callbackData)[2]] );
        $panel = $db->select("SELECT * FROM `panels` WHERE `id` = ?" , [explode( " " ,$callbackData)[2]] );
        $marzban_config = $bot->getuser(explode( " " ,$callbackData)[1], $panel['url'])['subscription_url'];
        $user = $db->select("SELECT * FROM `users` WHERE `id` = ?" , [$chat_id]);
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);
        if($config['expires_at'] !== null)
        {
            $bot->Extend($panel , explode( " " ,$callbackData)[1]);
        }
        $bot->ResetUserDataUsage(explode( " " ,$callbackData)[0] , $panel["url"]);
        $price = $config['price'];
        $indebtedness = $user['indebtedness'];
        $indebtedness += $price;
        if ($price == 0){
            $bot->sendMessage($chat_id , 'کانفیگ های تست قابلیت تمدید ندارند');
        }
        else{
            $db->update('users' , $chat_id, ['indebtedness'] , [$indebtedness]);
            $bot->sendMessage($chat_id , 'کانفیگ شما اپدیت شد');
            $url = $panel['url'] . $marzban_config;
            $QRCode = $bot->makeQRcode($url);
            $bot->sendImage($chat_id, $QRCode, $url, explode( " " ,$callbackData)[1]);
        }

    }

    elseif(explode( " " ,$callbackData)[0] == 'return')
    {
        $db = new Database($dbUsername,$dbPassword);
        $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);

        $message_id = $bot->sendMessage($chat_id ,'خوش آمدید دستور مورد نظر خود را انتخاب کنید');
        $db->update('users', $chat_id, ['message_id', 'command' , 'message'], [$message_id , '' , '']);
    }

    elseif(explode( " " ,$callbackData)[2] == 'config_search')
    {
        $user= $db->select('SELECT * FROM `users` WHERE `id` = ?', [$chat_id]);
        $panel_id = explode(" " , $callbackData)[1];
        $panel = $db->select("SELECT * FROM `panels` WHERE `id` = ?", [$panel_id] );
        $config = $db->select('SELECT * FROM `configs` WHERE `id` = ?' , [explode(" " , $callbackData)[0]]);
        $marzban_config = $bot->getuser($config['name'], $panel['url'])['subscription_url'];

        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);

        if ($config !== null) {
            $buttons = [
                [
                    ['text' => 'تمدید', 'callback_data' => 'update ' . $config['name'] . ' ' . $config['panel_id']],
                    ['text' => 'بازگشت', 'callback_data' => 'return '],
                ]
            ];


            $can_delete = strtotime($config['created_at']) > strtotime('-1 day');

            if ($can_delete)
            {

                $buttons[] = [['text' => 'حذف', 'callback_data' => 'delete ' . $config['name'] . ' ' . $config['panel_id']]];

            }

            $keyboard = [
                'inline_keyboard' => $buttons
            ];

            $replyMarkup = json_encode($keyboard);
            if ($marzban_config == null)
            {
                $message_id = $bot->sendMessage($chat_id, 'لطفا دوباره تلاش کنید ');
                $db->update('users', $chat_id, ['message_id', 'command'], [$message_id , '']);
            }
            else
            {
                $url = $panel['url'] . $marzban_config;
                $message_id = $bot->sendImage($chat_id, $url, $url, $config['name'] , $replyMarkup);
                $db->update('users', $chat_id, ['message_id', 'command'], [$message_id , '']);
            }

        }
    }

    elseif(explode( " " ,$callbackData)[0] == 'answer')
    {
        $db->update('users' , $chat_id , ['command' , 'message'] , ['answer' , explode( " " ,$callbackData)[1] . ' ' . explode( " " ,$callbackData)[2] ]);
        $bot->sendMessage($chat_id , 'پاسخ خود را به این مشتری بنویسید');
    }

    elseif(explode( " " ,$callbackData)[0] == 'payment')
    {
        $db = new Database($dbUsername,$dbPassword);
        $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);
        $target_user_chat_id = explode( " " ,$callbackData)[1];
        $indebtedness = 0;
        $db->update('users', $target_user_chat_id, ['indebtedness'], [$indebtedness]);
        $customer = $db->select("SELECT * FROM users WHERE id = ? ", [$target_user_chat_id]);
        $text = "{$target_user_chat_id} now have {$customer['indebtedness']} indebtedness";
        $bot->sendMessage($chat_id , $text,NULL);
        $bot->sendMessage($target_user_chat_id , 'درخواست تصفیه حساب شما توسط ادمین تایید شد');
    }

    elseif(explode( " " ,$callbackData)[0] == 'disapprove')
    {
        $db = new Database($dbUsername,$dbPassword);
        $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);
        $target_user_chat_id = explode( " " ,$callbackData)[1];

        $bot->sendMessage($target_user_chat_id , 'درخواست تسویه حساب شما توسط ادمین تایید نشد لطفا دوباره اقدام کنید');


    }

    elseif(explode( " " ,$callbackData)[0] == 'pay')
    {
        $db = new Database($dbUsername,$dbPassword);
        $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);


        $buttons[] = [
            ['text' => 'بازگشت', 'callback_data' => 'return'],
        ];

        $replyMarkup = json_encode([
            'inline_keyboard' => $buttons
        ]);



        $text = 'پیام خود مبنی بر تسویه حساب را  در غالب رسید ( عکس یا متن ) یا پیام اطلاع رسانی از پرداخت ارسال کنید';
        $bot->sendMessage($chat_id , $text );
        $db->update('users' , $chat_id , ['command'], ['payment']);

    }

    else
    {

        error_log('Received unexpected callback data: ' . $callbackData);
    }

}


if($user["is_verified"] == "approved")
{
    if (isset($update['message']) && $update['message']['text'] == '/start' )
    {
        $bot->sendMessage($chat_id,' سلام خوش آمدید');
        $db->update('users', $chat_id, ['message' , 'command'], [null , null]);
    }

    elseif (isset($update['message']) && $update['message']['text'] == '🌍 کانفیگ های پیشنهادی' )
    {
        $is_set = false;
        $text = $update['message']['text'];
        $chat_id = $update['message']['chat']['id'];
        $messageId = $update['message']['message_id'];


//        if($user['alter_name'] == 1)
//        {
//            $bot->deleteMessage($chat_id,$message_id);
//            $message_id = $bot->sendMessage($chat_id,'نام کانفیگ را وارد کنید');
//            $db->update('users', $chat_id, ['command' , 'message_id'], ['alter_name' , $message_id]);
//        }
            $buttons = [];
            $panels = $db->selectAll("SELECT * FROM panel_users WHERE user_id = ? ", [$chat_id]);

            if(count($panels) == 1)
            {
                $message_id = $user['message_id'];
                $bot->deleteMessage($chat_id,$message_id);


                $panel_templates = $db->selectAll("SELECT * FROM panel_templates WHERE panel_id = ? ", [$panels[0]['panel_id']]);
                foreach ($panel_templates as $panel_template)
                {
                    $template = $db->join('*' , 'panel_templates' , 'templates' , 'panel_templates.template_id = templates.id' , 'panel_templates.template_id = ' . $panel_template['template_id']);
                    $name = $template['text'];
                    $buttons[] = [['text' => $name, 'callback_data' => strval($template['id'] . ' config ' . $panels[0]['panel_id'])]];
                }
                $buttons[] = [['text' => 'بازگشت', 'callback_data' => 'return ']];


                $replyMarkup = json_encode([
                    'inline_keyboard' =>
                        $buttons

                ]);

                $message_id = $bot->sendMessage($chat_id, 'کانفیگ های این پنل:', $replyMarkup);
                $db->update('users', $chat_id, ['message_id'], [$message_id]);
            }

            elseif($panels == NULL)
            {
                $panels = $bot->findDefaultPanels();
                    $buttons = [];
                    foreach ($panels as $panel)
                    {
                        $buttons[] = [['text' => $panel['button_name'], 'callback_data' => strval($panel['id'] . ' panel ' . $message_id)]];
                    }
                    $buttons[] = [['text' => 'بازگشت', 'callback_data' => 'return ']];
                    $replyMarkup = json_encode([
                        'inline_keyboard' =>
                            $buttons

                    ]);

                    $message_id = $bot->sendMessage($chat_id, 'پنل های پیشنهادی شما:', $replyMarkup);
                    $db->update('users', $chat_id, ['message_id'], [$message_id]);

            }

            else
            {
                foreach ($panels as $panel)
                {
                    $selected_panels = $db->join('*' , 'panel_users' , 'panels' , 'panel_users.panel_id = panels.id' , 'panel_users.panel_id = ' . $panel['panel_id'] );
                    $buttons[] = [['text' => $selected_panels['button_name'], 'callback_data' => strval($panel['panel_id'] . ' panel ' . $message_id)]];
                }
                $buttons[] = [['text' => 'بازگشت', 'callback_data' => 'return ']];

                // Assuming here that you want to send a welcome message along with a button
                $replyMarkup = json_encode([
                    'inline_keyboard' =>
                        $buttons

                ]);

                $message_id = $bot->sendMessage($chat_id, 'پنل های پیشنهادی شما:', $replyMarkup);
                $db->update('users', $chat_id, ['message_id'], [$message_id]);
            }




    }



    elseif (isset($update['message']) && $update['message']['text'] == 'میزان بدهی شما')
    {
        $db->update('users' , $chat_id , ['command'] , [null]);
        $user = $db->select("SELECT * FROM users WHERE id = ? ", [$chat_id] );




        $buttons[] = [
            ['text' => 'تسویه حساب', 'callback_data' => 'pay'],
        ];

        $replyMarkup = json_encode([
            'inline_keyboard' => $buttons
        ]);


        $indebtedness = $user['indebtedness'];

        if($user['indebtedness'] !== 0 && $user['indebtedness'] !== null)
        {
            $message_id = $bot->sendMessage($chat_id,'میزان بدهی شما : '. $indebtedness . ' هزار تومان ' , $replyMarkup);
        }

        else
        {
            $message_id = $bot->sendMessage($chat_id,'شما در حال حاضر بدهی ندارید');
        }

        $db->update('users' , $chat_id , ['message_id'] , [$message_id]);



    }
    //this fs for support panel
    elseif (isset($update['message']) && $update['message']['text'] == 'تماس با پشتیبانی')
    {
        $bot->sendMessage($chat_id,' لطفا پیغام خود را بفرستید' ,$reply);
        $db->update('users' , $chat_id , ['command'] , ['support']);
    }
    elseif (isset($update['message']) && $update['message']['text'] == '🧔 کانفیگ های شما')
    {
        $db->update('users' , $chat_id , ['command'] , [null]);
        $panels = $db->selectAll("SELECT * FROM `panel_users` WHERE `user_id` = ?", [$chat_id]);
        $panels_number = count($panels);

        if ($panels_number > 1)
        {
            $buttons = [];
            foreach ($panels as $panel)
            {
                $selected_panels = $db->join('*' , 'panel_users' , 'panels' , 'panel_users.panel_id = panels.id' , 'panel_users.panel_id = ' . $panel['panel_id'] );

                $buttons[] = [
                    ['text' => $selected_panels['name'], 'callback_data' => strval($selected_panels['id'] . ' panel_search')]
                ];
            }

            $keyboard = [
                'inline_keyboard' => $buttons
            ];

            $replyMarkup = json_encode($keyboard);

            $message_id = $bot->sendMessage($chat_id, 'پنل مورد نظر خود را انتخاب کنید:', $replyMarkup);
            $db->update('users', $chat_id, ['message_id'], [$message_id]);
        }

        else
        {

            $panel = $db->select("SELECT * FROM `panel_users` WHERE `user_id` = ?", [$chat_id]);
            $panel_id = $panel['panel_id'];
            if (!$panel)
            {
                $panels = $bot->findDefaultPanels();
                $panel = $panels[0];
                $panel_id = $panel["id"];
            }

            $configs = $bot->searchConfigsBYChatId($chat_id , $panel_id);

            if (count($configs) > 0)
            {
                foreach ($configs as $config)
                {
                    $buttons[] = [
                        ['text' => $config['name'], 'callback_data' => strval($config['id'] . ' ' . $panel_id . ' config_search')]
                    ];
                }
                $keyboard = [
                    'inline_keyboard' => $buttons
                ];

                $replyMarkup = json_encode($keyboard);

                $message_id = $bot->sendMessage($chat_id, 'کانفیگ های شما :', $replyMarkup);
            }
            else
            {
                $bot->sendMessage($chat_id, 'کانفیگی برای نمایش وجود ندارد');
            }
            $db->update('users', $chat_id , ['message_id'] , [$message_id] );
        }




    }
    //this is for back keyborad button


    elseif (isset($update['message']) && $update['message']['text'] == 'تسویه حساب')
    {
        if($user['indebtedness'] !== 0 && $user['indebtedness'] !== null)
        {
            $bot->sendMessage($chat_id , 'درخواست شما ارسال شد
            رسید خود را برای ادمین ارسال کنید .منتظر تایید بمانید');
            $text =  $text = "یک درخواست تصفیه برای شما ارسال شد
            ----------------------------------------------------------------
            {$user['name']}
            -----------------------------------------------------------------
            میزان بدهی : {$user['indebtedness']}
            ";

            $buttons = [];

            $buttons[] = [
                ['text' => 'تایید و تسویه', 'callback_data' => 'payment ' . $chat_id],
                ['text' => 'رد', 'callback_data' => 'disapprove ' . $chat_id]
            ];

            $replyMarkup = json_encode([
                'inline_keyboard' => $buttons
            ]);

            $message_id = $bot->sendMessage(135629482 , $text , $replyMarkup);
            $db->update('users', 135629482, ['message_id'], [$message_id]);
        }
        else
        {
            $bot->sendMessage($chat_id , 'شما بدهی ندارید');
        }

    }


    elseif (isset($update['message']) && $update['message']['text'] == 'پنل مدیریت')
    {

        $db->update('users' , $chat_id , ['command'] , [null]);
        $keyboard = [
            ['درخواست ها'],
            ['پیام های مشتریان'],
            ['🌍 بازگشت به منوی اصلی'],
        ];
        $response = ['keyboard' => $keyboard, 'resize_keyboard' => true];
        $reply = json_encode($response);

        $bot->sendMessage($chat_id, 'پنل مدیریت',$reply);
    }

    elseif (isset($update['message']) && $update['message']['text'] == 'درخواست ها')
    {
        $db->update('users' , $chat_id , ['command'] , [null]);
        $requests = $db->selectAll("SELECT * FROM `users` WHERE `is_verified` = ?", ['unchecked']);
        foreach ($requests as $request)
        {
            $buttons = [];

            $buttons[] = [
                ['text' => 'تایید', 'callback_data' => $request['id'] . ' approve'],
                ['text' => 'رد', 'callback_data' => $request['id'] . ' ban']
            ];

            $replyMarkup = json_encode([
                'inline_keyboard' => $buttons
            ]);
            $message = $request['username'] .
                $message_id=$bot->sendMessage($chat_id, $request['username'], $replyMarkup);
            $db->update("users" , $request['id'] , ['message_id'] , [$message_id] );
        }
        if(count($requests) == 0)
        {
            $bot->sendMessage($chat_id, 'درخواستی وجود ندارد' , $replyMarkup);
        }

    }

    elseif (isset($update['message']) && $update['message']['text'] == '🌍 بازگشت به منوی اصلی')
    {
        $bot->sendMessage($chat_id , 'به منوی قبلی بازگشتید');
        $db->update('users' , $chat_id , ['command'] , [null]);
    }

    elseif (isset($update['message']) && $update['message']['text'] == 'پیام های مشتریان')
    {
        $messages = $db->selectAll('SELECT * FROM `support`');

        if(count($messages) == 0)
        {
            $bot->sendMessage($chat_id , 'پیامی برای نمایش وجود ندارد' );
        }
        else
        {

            foreach($messages as $message)
            {

                $buttons[] = [['text' => 'پاسخ', 'callback_data' => 'answer ' . $message['chat_id'] . ' ' . $message['id']]];

                $replyMarkup = json_encode([
                    'inline_keyboard' =>
                        $buttons

                ]);
                $sent_message = $message['message'] ;
                $text = "پیام از طرف : ". $message['username'] ." ". $message['name'] . "\n" . "متن پیام : {$sent_message}";

                $bot->sendMessage($chat_id , $text , $replyMarkup );
                $buttons = [];
            }
        }

    }

    elseif (isset($update['message']) && $command == 'alter_name')
    {
        $message_id = $user['message_id'];
        $bot->deleteMessage($chat_id,$message_id);
        $message_id = $bot->sendMessage($chat_id,'نام کانفیگ شما ست شد ');
        $template_id = explode(" " , $user['message'])[0];
        $panel_id = explode(" " , $user['message'])[1];
        $bot->sendMessage($chat_id , $template_id. " " . $panel_id);
        $selected = $db->select("SELECT * FROM templates WHERE id = ? ", [$user['message']]);
        $panel_template = $db->select("SELECT * FROM panel_templates WHERE panel_id = ? AND template_id = ?", [$panel_id, $template_id]);

        $username = $update['message']['text'];
        $price = $panel_template['price'];


        if($selected['expire'] !== null)
        {
            $currentDate = new DateTime();


            $currentDate->add(new DateInterval("P{$selected['expire']}D"));


            $newTimestamp = strtotime($currentDate->format('Y-m-d'));
            $expire = $newTimestamp;
            $expire_show = $selected['expire'];
        }
        else
        {
            $expire = null;
            $expire_show = '∞';
        }

        if($selected['limitation'] !== null)
        {
            $data_limit = intval($selected['limitation']);
            $data_show = $selected['limitation'];
        }
        else
        {
            $data_limit = null;
            $data_show = '∞';

        }



        $message = " 
                        💻 کانفیگ {$expire_show} روزه  
    
                🦅 حجم : {$data_show} گیگ  
    
                ❄️ قیمت : {$price}  هزار تومن 
    
                ✔️ در صورت مطمئن بودن روی خرید کلیک کنید";


        $reply = json_encode([
            'inline_keyboard' =>
                [
                    [
                        ['text' => "خرید", 'callback_data' => "خرید "]
                    ]
                ]

        ]);

        $message_id = $bot->sendMessage($chat_id , $message , $reply);
        $db->update('users', $chat_id, ['message' , 'command' , 'message_id' ] ,[ $username . " " . $price . " " . $data_limit . " " . $expire. " " . $panel_id . " " . $template_id , "" , $message_id ]);

    }

    elseif (isset($update['message']) && $command == 'support')
    {
        $user = $db->select('SELECT * FROM `users` WHERE `id` = ?', [$chat_id]);
        $text = "یه پیغام از طرف : {$user['username']}
        ----------------------------------------------------------------
        {$update['message']['text']}
        " . $chat_id;
        $message_id = $bot->sendMessage(135629482 , $text , NULL);
        $check = $db->insert('support' , ['chat_id' , 'username' , 'message'] , [$chat_id , $user['username'] , $update['message']['text'] ]);
        if ($check == true){
            $bot->sendMessage($chat_id , 'پیغام شما با موفقیت ارسال شد به زودی پاسخ خود را از طریق همین ربات دریافت خواهید کرد');
        }
        else{
            $bot->sendMessage($chat_id , 'مشکلی پیش آمده لطفا دوباره امتحان کنید');
        }
        $db->update('users' , $chat_id , ['command'] , [null]);
        $message_id = $bot->sendMessage(135629482 , $text , NULL);

    }

    elseif (isset($update['message']) && $command == 'answer')
    {

        $target_user_id = $user['message'];
        $message = $db->select('SELECT * FROM `support` WHERE `id` = ?' , [explode( " " ,$target_user_id)[1]]);
        $text = "ادمین در جواب این پیام شما پاسخی ارسال کرد 
        ----------------------------------------------------------------
        {$message['message']}
        ----------------------------------------------------------------
        {$update['message']['text']}
        ";


        $bot->sendMessage(explode( " " ,$target_user_id)[0] , $text );
        $db->deleteMessage('support' ,explode( " " ,$target_user_id)[1] );
    }

    elseif (isset($update['message']) && $command == 'payment')
    {
        $user = $db->select('SELECT * FROM `users` WHERE `id` = ?', [$chat_id]);
        $bot->sendmessage($chat_id , 'درخواست شما ارسال شد لطفا منتظر تاییدیه ادمین بمانید');
        $buttons[] = [
            ['text' => 'تایید و تسویه', 'callback_data' => 'payment ' . $chat_id],
            ['text' => 'رد', 'callback_data' => 'disapprove ' . $chat_id]
        ];

        $replyMarkup = json_encode([
            'inline_keyboard' => $buttons
        ]);
        $text= "یک درخواست تصفیه برای شما ارسال شد
        ----------------------------------------------------------------
        {$user['name']}
        ----------------------------------------------------------------
        میزان بدهی : {$user['indebtedness']}
        
        پیام زیر مربوط به این درخواست است :
        ";

        $messageId = $update['message']['message_id'];
        $bot->sendmessage(135629482 , $text , $replyMarkup);
        $bot->forwardMessage($chat_id , $messageId , $replyMarkup);
        $db->update('users' , $chat_id , ['command'] , [null]);
    }

}

elseif($user['is_verified'] == 'unchecked')
{
    $buttons[] = [['text' => 'تماس با پشتیبانی', 'url' => 'https://t.me/RajaTeam_support' ]];

    $replyMarkup = json_encode([
        'inline_keyboard' =>
            $buttons
    ]);
    $bot->sendMessage($chat_id , 'لطفا منتظر تایید ادمین بمانید' , $replyMarkup);
}









?>
