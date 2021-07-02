<?php
/** @var $doctor_id int */
?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	<?php
	wp_nonce_field( 'order-form', 'pharma-order-nonce' );
	?>

	<p><label for="name">Ваше имя</label> 
		 <input type="text" name="name"/></p>
	<label for="message">Ваше сообщение</label>
	<textarea name="message" id="message"></textarea>
	<input type="hidden" name="action" value="paid_notification">
	<input type="hidden" name="doctor_id" value="<?=$doctor_id;?>">
	<input type="submit" value="Отправить уведомление">
</form>