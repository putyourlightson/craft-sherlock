$(document).ready(function()
{
    $('table.limited').each(function() {
        var limit = $(this).data('limit');
        if ($(this).find('tr').length > limit) {
            $(this).closest('.sherlock').find('.expand').show();
            $(this).find('tr:nth-child(n+' + (limit + 1) + ')').hide();
        }
    });

    $('a.view-more').click(function(event) {
        $(this).closest('.sherlock').find('table.limited tr').show();
        $(this).parent().find('.view-less').show();
        $(this).hide();

        event.preventDefault();
    });

    $('a.view-less').click(function(event) {
        var limit = $(this).closest('.sherlock').find('table.limited').data('limit');
        $(this).closest('.sherlock').find('table.limited tr:nth-child(n+' + (limit + 1) + ')').hide();
        $(this).parent().find('.view-more').show();
        $(this).hide();

        event.preventDefault();
    });

    $('.run-scan').click(function() {
        event.preventDefault();

        $('.sherlock').hide();
        $('.running').show();
        $('.running #graphic').addClass('spinner')

        $.get($(this).attr('data-url'), function() {
            location.reload();
        }).fail(function(xhr) {
            var error = xhr.responseText;
            $('.running #graphic').addClass('error').removeClass('spinner');
            $('.running #text').addClass('error').html(error);
            $('.running #back').show();
        });
    });
});
