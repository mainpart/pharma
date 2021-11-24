<?php
/**
 * Шаблон используется для отправки пользователю уведомления об окончании доступа
 *
 * @var $client WP_User
 * @var $doctor WP_User
 * @var $date_time_obj DateTime
 */
?>
Здравствуйте, <?php echo $client->display_name;?>.

Уведомляем, что доступ к странице консультаций <?php echo $client->display_name;?> - <?=$doctor->display_name;?> закрыт с <?=$date_time_obj->format( "Y-m-d" );?> , возможность перписки по электронной почте тпакже ограничена. Для возобновления функционала необходимо продлить подписку https://curshen.info/?p=966

------
Это автоматическое сообщение с сайта curshen.info
