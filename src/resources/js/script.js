$(document).ready(function()
{
    $('table.limited').each(function() {
        var limit = $(this).data('limit');
        if ($(this).find('tr').length > limit) {
            $(this).closest('.body').find('.expand').show();
            $(this).find('tr:nth-child(n+' + (limit + 1) + ')').hide();
        }
    });

    $('a.view-more').click(function(event) {
        $(this).closest('.body').find('table.limited tr').show();
        $(this).parent().find('.view-less').show();
        $(this).hide();

        event.preventDefault();
    });

    $('a.view-less').click(function(event) {
        var limit = $(this).closest('.body').find('table.limited').data('limit');
        $(this).closest('.body').find('table.limited tr:nth-child(n+' + (limit + 1) + ')').hide();
        $(this).parent().find('.view-more').show();
        $(this).hide();

        event.preventDefault();
    });

    $('.run-scan').click(function() {
        $('.sherlock').hide();
        $('.running').show();

        $.get($(this).data('url'), function() {
            location.reload();
        }).fail(function(xhr) {
            var error = xhr.responseText
            error = error.replace(/\n/g, '');
            error = error.replace(/<pre>(.*)/, '');
            $('.running #graphic').addClass('error');
            $('.running #status').html(error);
        });

        event.preventDefault();
    });

    if (typeof runScanAjax != 'undefined') {
        $.get(runScanAjax, function() {
            location.reload();
        });
    }
});
