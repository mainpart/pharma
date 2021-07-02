Здравствуйте, <?php echo $doctor->display_name;?>.

Пользователь <?php $user->display_name;?> уведомляет вас, что он оставил запрос на продление / добавление / возобновление консультаций:

<?php echo $message;?>

С уважением, команда сайта. <?=get_option('site_name');?>
