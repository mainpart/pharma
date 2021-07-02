Здравствуйте, <?php echo $client->display_name;?>.

Уведомляем, что доступ к консультациям с <?=$doctor->display_name;?> открыт до <?=$date_time_obj->format( "Y-m-d" );?>

Школа гомеопатов <?=get_option('site_name');?>
