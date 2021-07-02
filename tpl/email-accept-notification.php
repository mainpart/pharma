Здравствуйте, <?php echo $user->display_name;?>.

Гомеопат <?php $doctor->display_name;?> уведомляет, что получил ваш запрос. В настоящее время вы можете воспользоваться следующими активными консультациями:

<?php
$query = new WP_Query([
    'meta_query'=>[
        'relation'=>'AND',
        [
            'key'=>'doctor_id',
            'value'=>$doctor->ID,

        ],[
            'key'=>'client_id',
            'value'=>$user->ID
        ],
        [
            'key'=>'is_active',
            'value'=>1
        ],
    ],
    'post_type'=>Pharma::CONSULTATION_POST_TYPE,
]);
while($query->have_posts()) : $query->the_post();
    echo get_permalink()."\r\n";
endwhile;
wp_reset_postdata();
?>

Спасибо . <?=get_option('site_name');?>
