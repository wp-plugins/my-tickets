jQuery(document).ready(function ($) {
    $('#add_field').on('click', function () {
        $('#event_span').removeAttr('disabled');
        var num = $('.clonedInput').length; // how many "duplicatable" input fields we currently have
        var newNum = new Number(num + 1);      // the numeric ID of the new input field being added
        // create the new element via clone(), and manipulate it's ID using newNum value
        var newElem = $('#event' + num).clone().attr('id', 'event' + newNum);
        // manipulate the name/id values of the input inside the new element
        // insert the new element after the last "duplicatable" input field
        $('#event' + num).after(newElem);
        // enable the "remove" button
        $('#del_field').removeAttr('disabled');
        // business rule: you can only add 20 duplications
        if (newNum == 20)
            $('#add_field').attr('disabled', 'disabled');
    });

    $('#del_field').on('click', function () {
        var num = $('.clonedInput').length; // how many "duplicatable" input fields we currently have
        $('#event' + num).remove();     // remove the last element
        // enable the "add" button
        $('#add_field').removeAttr('disabled');
        // if only one element remains, disable the "remove" button
        if (num - 1 == 1)
            $('#del_field').attr('disabled', 'disabled');
        $('#event_span').attr('disabled', 'disabled');
    });
    $('#del_field').attr('disabled', 'disabled');
    $('#event_span').attr('disabled', 'disabled');

    $('#add_price').on('click', function () {
        var num = $('.clonedPrice').length; // how many "duplicatable" input fields we currently have
        var newNum = new Number(num + 1);      // the numeric ID of the new input field being added
        // create the new element via clone(), and manipulate it's ID using newNum value
        var newElem = $('#price' + num).clone().attr('id', 'price' + newNum);
        // manipulate the name/id values of the input inside the new element
        // insert the new element after the last "duplicatable" input field
        $('#price' + num).after(newElem);
        // enable the "remove" button
        $('#del_price').removeAttr('disabled');
        // business rule: you can only add 20 variations
        if (newNum == 20)
            $('#add_price').attr('disabled', 'disabled');
    });

    $('#del_price').on('click', function () {
        var num = $('.clonedPrice').length; // how many "duplicatable" input fields we currently have
        $('#price' + num).remove();     // remove the last element
        // enable the "add" button
        $('#add_price').removeAttr('disabled');
        // if only one element remains, disable the "remove" button
        if (num - 1 == 1)
            $('#del_price').attr('disabled', 'disabled');
        $('#event_span').attr('disabled', 'disabled');
    });
    $('#del_price').attr('disabled', 'disabled');

    $("button.up,button.down").click(function(e){
        e.preventDefault();
        $('.mt-pricing table tr').removeClass('fade');
        var row = $(this).parents("tr:first");
        if ($(this).is(".up")) {
            row.insertBefore(row.prev()).addClass('fade');
        } else {
            row.insertAfter(row.next()).addClass('fade');
        }
    });
});
