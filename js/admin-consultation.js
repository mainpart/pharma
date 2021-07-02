jQuery(function ($) {


    $('input.paidtill').datepicker({
            onSelect: function(a){
                var container = $(this).closest('div');
                var data = $(container).data();
                var unixtime = $(this).datepicker("getDate").getTime() / 1000;
                var timeZone = $(this).datepicker("getDate").getTimezoneOffset();
                if (timeZone > 0 ){
                    unixtime-=Math.abs(timeZone) * 60;
                }else {
                    unixtime+=Math.abs(timeZone) * 60;
                }


                var ajax_data = {
                    time: unixtime,
                    client_id: data.clientId,
                    doctor_id: data.doctorId,
                    action: 'consult_change_date_admin_ajax',

                };

                var containers = $('div.paidtill_'+data.doctorId+'_'+data.clientId);

                var spinners = [];
                $(containers).each(function(){
                    var spinner = $('<div class="spinner"></div>');
                    $(spinner).appendTo($(this)).css({visibility: 'visible', 'float': 'left'});
                    $(this).find('span.paidtill').hide();
                    spinners.push(spinner);
                });

                $.ajax(
                    {
                        type: "post",
                        url: my_ajax_object.ajaxurl,
                        data: ajax_data,
                        success: function (msg) {

                            $(spinners).each(function () {
                                $(this).remove()
                            });
                            console.log(containers);
                            $(containers).each(function () {
                                $(this).find('span.paidtill').each(function () {
                                    console.log($(this));
                                    $(this).show();
                                    $(this).find('span').html(msg);
                                    $(this).find('input').datepicker("setDate", msg);
                                });

                            })
                        },
                        error: function (msg) {
                            $(spinners).each(function(){$(this).remove()});
                            $(containers).each(function(){$(this).find('span.paidtill').show()});
                        },

                    }
                );



            },
            dateFormat: "yy-mm-dd",
            showOn: "button",
            buttonImage: "/wp-admin/images/date-button.gif",
            buttonImageOnly: true,
        });



    $('span.paidtill').click(function () {
        // var div = $(this).closest('div');
        // console.log(div.data());
        // $(div).find('input.paidtill').datepicker("show");
    });

    $('input.check[type=checkbox]').click(function () {
        var container = $(this).closest('.container');
        var cb = $(this);
        cb.hide();
        var spinner = $('<div class="spinner"></div>');
        $(spinner).appendTo(container);
        spinner.css({visibility: 'visible', 'float': 'left'});
        var ajax_data = {
            value: $(this).is(':checked'),
            post_id: $(this).data('postId'),
            action: 'consult_activation_admin_ajax',
            nonce: my_ajax_object.nonce
        };
        $.ajax(
            {
                type: "post",
                url: my_ajax_object.ajaxurl,
                data: ajax_data,
                success: function (msg) {
                    spinner.remove();
                    if (msg==1){
                        cb.show();

                    } else {
                        container.html(msg);
                    }
                },
                error: function (msg) {
                    spinner.remove();
                    container.html('Ошибка');
                }

            }
        );


    });

});