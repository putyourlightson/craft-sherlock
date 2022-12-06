$(document).ready(function()
{
    $('table.limited').each(function() {
        const limit = $(this).data('limit');
        if ($(this).find('tr').length > limit) {
            $(this).closest('.sherlock').find('.expand').show();
            $(this).find('tr:nth-child(n+' + (limit + 1) + ')').hide();
        }
    });

    $('a.view-more').click(function(event) {
        event.preventDefault();

        $(this).closest('.sherlock').find('table.limited tr').show();
        $(this).parent().find('.view-less').show();
        $(this).hide();
    });

    $('a.view-less').click(function(event) {
        event.preventDefault();

        const limit = $(this).closest('.sherlock').find('table.limited').data('limit');
        $(this).closest('.sherlock').find('table.limited tr:nth-child(n+' + (limit + 1) + ')').hide();
        $(this).parent().find('.view-more').show();
        $(this).hide();
    });

    $('.run-scan').click(function(event) {
        event.preventDefault();

        $('.sherlock').hide();
        $('.running').show();
        $('.running #graphic').addClass('spinner')

        $.get($(this).attr('data-url'), function() {
            location.reload();
        })
        .fail(function(xhr) {
            const error = xhr.responseText.replace(/<\/?pre>/gi, '');
            $('.running #graphic').addClass('error').removeClass('spinner');
            $('.running #text').addClass('error').html(error);
            $('.running #back').show();
        });
    });
});
