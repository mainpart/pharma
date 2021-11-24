<?php
/** @var $doctor_id int */
?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	<?php
	wp_nonce_field( 'order-form', 'pharma-order-nonce' );
	?>

	<label for="name">Имя и фамилия (обязательно) : </label>
	<input type="text" name="name"/> 
	<br/>
	<label for="message">Детали оплаты (дата, время, сумма, способ):</label>
	<textarea name="message" id="message"></textarea>
	<input type="hidden" name="action" value="paid_notification">
	<input type="hidden" name="doctor_id" value="<?=$doctor_id;?>">
	<input type="submit" value="Отправить">
</form>