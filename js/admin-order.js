jQuery(function ($) {

    $('.pay_button').click(function () {
        var el = $(this).closest('.container');
        var data = el.data();
        el.html('');
        //var button = $(this).detach();
        var spinner = $('<div class="spinner"></div>');
        $(spinner).appendTo(el);
        spinner.css({visibility: 'visible', 'float': 'left'});
        var ajax_data = {
            client_id: data.clientId,
            order_id: data.orderId,
            type: $(this).data('action'),
            action: 'paid_notification_admin_ajax',
            nonce: my_ajax_object.nonce
        };
        $.ajax(
            {
                type: "post",
                url: my_ajax_object.ajaxurl,
                data: ajax_data,
                success: function (msg) {
                    el.html(msg);
                },
                error: function (msg) {
                    el.html(msg);
                }

            }
        );


    });
});