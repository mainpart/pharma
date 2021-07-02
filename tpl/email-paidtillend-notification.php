<?php
/**
 * Шаблон используется для отправки пользователю уведомления об окончании доступа
 *
 * @var $client WP_User
 * @var $doctor WP_User
 * @var $date_time_obj DateTime
 */
?>
- Это автоматическое сообщение, не отвечайте на него -

Здравствуйте, <?php echo $client->display_name;?>.

Уведомляем, что доступ к консультациям с <?=$doctor->display_name;?> закрыт <?=$date_time_obj->format( "Y-m-d" );?>. 
Если у вас остались вопросы, то связаться с гомеопатом вы сможете зайдя на сайт с логином и паролем по ссылке https://keep.homeoclass.ru/wp-login.php

Техническая поддержка . <?=get_option('site_name');?>
