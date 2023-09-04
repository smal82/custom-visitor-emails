jQuery(document).ready(function($) {
    // Aggiorna il widget tramite Ajax quando la pagina è pronta
    $.ajax({
        url: custom_dashboard_widget_ajax.ajaxurl,
        type: 'POST',
        data: { action: 'custom_dashboard_widget_refresh' },
        success: function(response) {
            $('#custom-dashboard-widget-container').html(response);
        }
    });

    // Aggiorna il widget ogni 15 secondi (o con la frequenza desiderata)
    setInterval(function() {
        $.ajax({
            url: custom_dashboard_widget_ajax.ajaxurl,
            type: 'POST',
            data: { action: 'custom_dashboard_widget_refresh' },
            success: function(response) {
                $('#custom-dashboard-widget-container').html(response);
            }
        });
    }, 15000); // 60 secondi
});
