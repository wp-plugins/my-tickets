(function ($) {
    $(function () {
        $(".cc-num-valid").hide();
        $(".cc-num-invalid").hide();
        $("input.cc-num").payment('formatCardNumber');
        $('input.cc-exp').payment('formatCardExpiry');
        $('input.cc-cvc').payment('formatCardCVC');

        $("input[type='number']").on('keydown', function (e) {
            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                    // Allow: Ctrl+A
                (e.keyCode == 65 && e.ctrlKey === true) ||
                    // Allow: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                // let it happen, don't do anything
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
        $('.mt-error-notice').hide();
        $('.tickets_field').on('blur', function () {
            var remaining = 0;
            var purchasing = 0;
            $('.tickets-remaining .value').each(function () {
                var current_value = parseInt($(this).text());
                remaining = remaining + current_value;
            });
            $('.tickets_field').each(function () {
                var current_value = Number($(this).val());
                purchasing = purchasing + current_value;
            });
            if (purchasing > remaining) {
                $('button[name="mt_add_to_cart"]').addClass('mt-invalid-purchase').attr('disabled', 'disabled');
            } else {
                $('button[name="mt_add_to_cart"]').removeClass('mt-invalid-purchase').removeAttr('disabled');
            }
        });
        $('.mt_cart button').on('click', function (e) {
            e.preventDefault();
            var action = $(this).attr('class');
            var target = $(this).attr('rel');
            var event_id = $(this).attr('data-id');
            var event_type = $(this).attr('data-type');
            var val = $(target + ' .mt_count').val();

            if (action == 'more') {
                var newval = parseInt(val) + 1;
            } else if (action == 'less') {
                var newval = parseInt(val) - 1;
            } else {
                var newval = 0;
                $(target).addClass('removed');
            }

            $(target + ' .mt_count').val(newval);
            $(target + ' span.count').text(newval);
            var total = 0;
            $('td .count').each(function () {
                var count = $(this).text();
                var price = $(this).parent('td').siblings().children('.price').text();
                total += count * price;
            });
            $('.mt_total_number').text('$' + parseFloat(total).toFixed(2).replace('/(\d)(?=(\d{3})+\.)/g', "$1,").toString());

            var data = {
                'action': mt_ajax_cart.action,
                'data': {mt_event_id: event_id, mt_event_tickets: newval, mt_event_type: event_type},
                'security': mt_ajax_cart.security
            };
            $.post(mt_ajax_cart.url, data, function (response) {
                if (response.success == 1) {
                    $('.mt-response').html("<p>" + response.response + "</p>").show(300);
                }
            }, "json");

        });

        $('.gateway-selector a').on('click', function (e) {
            e.preventDefault();
            $('.gateway-selector li').removeClass('active');
            $(this).parent('li').addClass('active');
            var gateway = $(this).attr('data-assign');
            $('input[name="mt_gateway"]').val(gateway);
        });

        $('.ticket-orders button').on('click', function (e) {
            $('.mt-processing').show();
            e.preventDefault();
            var post = $(this).closest('.ticket-orders').serialize();
            var data = {
                'action': mt_ajax.action,
                'data': post,
                'function': 'add_to_cart',
                'security': mt_ajax.security
            };
            $.post(mt_ajax.url, data, function (response) {
                $('#mt-response-' + response.event_id).html("<p>" + response.response + "</p>").show(300);
                if (response.success == 1) {
                    $('.mt_qc_tickets').text(response.count);
                    $('.mt_qc_total').text(parseFloat(response.total, 10).toFixed(2).replace('/(\d)(?=(\d{3})+\.)/g', "$1,").toString());
                }
            }, "json");
            $('.mt-processing').hide();
        });
        // on checkbox, update private data
        $('.mt_save_shipping').on('click', function (e) {
            e.preventDefault();
            $('.mt-processing').show();

            var street  = $('.mt_street').val();
            var street2 = $('.mt_street2').val();
            var city    = $('.mt_city').val();
            var state   = $('.mt_state').val();
            var code    = $('.mt_code').val();
            var country = $('.mt_country').val();

            var post = {
                "street": street,
                "street2": street2,
                "city": city,
                "state": state,
                "code": code,
                "country": country
            };

            var data = {
                'action': mt_ajax.action,
                'data': post,
                'function': 'save_address',
                'security': mt_ajax.security
            };
            $.post( mt_ajax.url, data, function (response) {
                var message = response.response;
                $( '.mt-response' ).html( "<p>" + message + "</p>" ).show( 300 );
            }, "json" );
            $('.mt-processing').hide();
        });
    });
}(jQuery));