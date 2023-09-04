<?php
/*
Plugin Name: Custom Visitor Emails
Plugin URI:  https://wordpress.org/plugins/custom-visitor-emails
Description: Registra le visite e mostra i dettagli delle visite nella pagina di impostazioni.
Version: 1.0
Author: smal
Author URI: https://smal.netsons.org/
License:     GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

global $wpdb;
$table_name = $wpdb->prefix . 'custom_visitor_visits';

// Creazione della tabella delle visite
function custom_create_visits_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_visitor_visits';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            visit_date TEXT NOT NULL,
            visit_time TIME NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'custom_create_visits_table');

// Funzione per registrare la visita
function custom_record_visit() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_visitor_visits';


    $current_user = wp_get_current_user();

    // Verifica se l'utente corrente non è l'amministratore
    if (!in_array('administrator', $current_user->roles)) {
        $visit_date = date_i18n('j F Y');
    $visit_time = date_i18n('H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $wpdb->insert(
        $table_name,
        array(
            'visit_date' => $visit_date,
            'visit_time' => $visit_time,
            'ip_address' => $ip_address,
                'user_agent' => $user_agent
        )
    );

    // Invio dell'email all'admin
    $admin_email = get_option('admin_email');
    $subject = 'Nuova visita sul sito';
    $message = "Il tuo sito è stato appena visualizzato da un visitatore: $visit_date - $visit_time\nIndirizzo IP dell'utente: $ip_address\nUser-Agent: $user_agent";

    wp_mail($admin_email, $subject, $message);
}
}
add_action('wp', 'custom_record_visit');

// Pagina di impostazioni
function custom_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_visitor_visits';

    // Gestione dell'eliminazione della tabella
    if (isset($_POST['delete_visits'])) {
        $wpdb->query("TRUNCATE TABLE $table_name");
        echo '<div class="notice notice-success"><p>Tabella delle visite svuotata con successo.</p></div>';
    }

    $visits = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A);
    ?>
    <div class="wrap">
        <h2>Impostazioni Custom Visitor Emails</h2>
        <h3>Dettagli delle visite</h3>
        <form method="post" action="">
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Indirizzo IP</th>
                        <th>User-Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit) : ?>
                        <tr>
                            <td><?php echo $visit['visit_date']; ?></td>
                            <td><?php echo $visit['visit_time']; ?></td>
                            <td><a href="https://whatismyip.live/ip/<?php echo $visit['ip_address']; ?>" target="_blank"><?php echo $visit['ip_address']; ?></a></td>
                            <td><?php echo $visit['user_agent']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><input type="submit" class="button button-secondary" name="delete_visits" value="Svuota Tabella" onclick="return confirm('Sei sicuro di voler svuotare la tabella? Questa azione non può essere annullata.');"></p>
        </form>
    </div>
    <?php
}

// Aggiungi il link alle impostazioni prima di "Disattiva"
function custom_settings_link($links) {
    $settings_link = '<a href="admin.php?page=custom-plugin-table">Impostazioni</a>';
    array_splice($links, count($links) - 1, 0, $settings_link);
    return $links;
}


$plugin_basename = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin_basename", 'custom_settings_link');

// Aggiungi il menu specifico per il plugin con un'icona personalizzata
add_action('admin_menu', 'custom_plugin_menu');

function custom_plugin_menu() {
    $page_title = 'Custom Visitor Emails'; // Nome del plugin
    $menu_title = 'Custom Visitor Emails'; // Nome del plugin
    $capability = 'manage_options';
    $menu_slug = 'custom-plugin-table';
    $function = 'custom_settings_page';
    $icon_url = plugins_url('cveicon5.png', __FILE__);  // Sostituisci con l'URL del tuo file icona (ad esempio, 'http://example.com/my-plugin-icon.png')

    add_menu_page(
        $page_title,
        $menu_title,
        $capability,
        $menu_slug,
        $function,
        $icon_url // Aggiungi l'URL dell'icona personalizzata qui
    );

    add_submenu_page(
        $menu_slug, // Menu padre
        'Visualizza le visite',
        'Visualizza le visite',
        $capability,
        'custom-plugin-table',
        'custom_settings_page'
    );

    add_submenu_page(
        $menu_slug, // Menu padre
        'Gestione robots.txt',
        'Gestione robots.txt',
        $capability,
        'custom-plugin-robots',
        'custom_plugin_robots_page'
    );

    add_submenu_page(
        $menu_slug, // Menu padre
        'Gestione .htaccess',
        'Gestione .htaccess',
        $capability,
        'custom-plugin-htaccess',
        'custom_plugin_htaccess_page'
    );
}


// Funzione per visualizzare la pagina di gestione del file robots.txt
function custom_plugin_robots_page() {
    $robots_txt_path = ABSPATH . 'robots.txt';

    if (isset($_POST['save_robots'])) {
      //  $robots_content = sanitize_text_field($_POST['robots_content']);
 $robots_content = $_POST['robots_content'];
 $robots_content = htmlspecialchars($robots_content, ENT_QUOTES, 'UTF-8');
    //    file_put_contents($robots_txt_path, $robots_content);
$file = fopen($robots_txt_path, "w");
fwrite($file, $robots_content);
fclose($file);
        echo '<div class="notice notice-success"><p>Il file robots.txt è stato salvato con successo.</p></div>';
    }

    $robots_content = file_get_contents($robots_txt_path);
    ?>
    <div class="wrap">
        <h2>Gestione robots.txt</h2>
        <form method="post" action="">
            <textarea name="robots_content" rows="23" cols="150"><?php echo $robots_content; ?></textarea>
            <p><input type="submit" class="button button-primary" name="save_robots" value="Salva robots.txt"></p>
        </form>
    </div>
    <?php
}

// Funzione per visualizzare la pagina di gestione del file .htaceess
function custom_plugin_htaccess_page() {
    $htaccess_path = ABSPATH . '.htaccess';

    if (isset($_POST['save_htaccess'])) {
      //  $htaccess_content = sanitize_text_field($_POST['htaccess_content']);
 $htaccess_content = $_POST['htaccess_content'];

// Rimuovi eventuali barre rovesciate aggiunte precedentemente
    $htaccess_content_strip = stripslashes($htaccess_content);

// Ottieni il testo dalla textarea e effettua l'escape
//    $htaccess_content_add = addslashes($htaccess_content);

// $htaccess_content = htmlspecialchars($htaccess_content, ENT_QUOTES, 'UTF-8');
    //    file_put_contents($htaccess_txt_path, $htaccess_content);
$file2 = fopen($htaccess_path, "w");
fwrite($file2, $htaccess_content_strip);
fclose($file2);
        echo '<div class="notice notice-success"><p>Il file .htaccess è stato salvato con successo.</p></div>';
    }

    $htaccess_content = file_get_contents($htaccess_path);
// Rimuovi eventuali barre rovesciate aggiunte precedentemente
    $htaccess_content_strip = stripslashes($htaccess_content);

    ?>
    <div class="wrap">
        <h2>Gestione .htaccess</h2>
        <form method="post" action="">
            <textarea name="htaccess_content" rows="23" cols="150"><?php echo $htaccess_content_strip; ?></textarea>
            <p><input type="submit" class="button button-primary" name="save_htaccess" value="Salva .htaccess"></p>
        </form>
    </div>
    <?php
}


// Registra il widget per la bacheca dell'amministratore
function custom_register_dashboard_widget() {
    wp_add_dashboard_widget(
        'custom_dashboard_widget',
        'Custom Visitor Emails',
        'custom_dashboard_widget_content'
    );
    
    // Includi il file JavaScript per l'Ajax
    wp_enqueue_script('custom-dashboard-widget-ajax', plugin_dir_url(__FILE__) . 'custom-dashboard-widget-ajax.js', array('jquery'), '1.0', true);

// Passa l'URL per la funzione Ajax al file JavaScript
    wp_localize_script('custom-dashboard-widget-ajax', 'custom_dashboard_widget_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
	
//Includi il css
wp_enqueue_style('custom-dashboard-widget-btn', plugin_dir_url(__FILE__) . 'custon-dashboard-widget.css');

}
add_action('wp_dashboard_setup', 'custom_register_dashboard_widget');

// Funzione per visualizzare il contenuto del widget
function custom_dashboard_widget_content() {
    echo '<h1 style="text-align: center;">Ultime 10 visite</h1><br/>';
    echo '<table class="widefat striped responsive">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Data</th>';
    echo '<th>Ora</th>';
    echo '<th>Indirizzo IP</th>';
    echo '<th>User-Agent</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody id="custom-dashboard-widget-container">';
    echo '</tbody>';
 echo '</table>';
echo '<div style="text-align: center;">';
echo '<h2>Seleziona una pagina:</h2><br/>';
echo ' <div class="custom_widget_btn">';
echo '<a href="admin.php?page=custom-plugin-table">Elenco completo</a>';
echo '<a href="admin.php?page=custom-plugin-robots">Robots.txt</a>';
echo '<a href="admin.php?page=custom-plugin-htaccess">.htaccess</a>';
echo '</div>';
echo '</div>';
}


// Registra l'handler Ajax per il widget
add_action('wp_ajax_custom_dashboard_widget_refresh', 'custom_dashboard_widget_refresh');

// Funzione per aggiornare dinamicamente le visite tramite Ajax
function custom_dashboard_widget_refresh() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_visitor_visits';

    // Recupera le ultime 5 visite dalla tabella
    $visits = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 10");

    if (!empty($visits)) {
        foreach ($visits as $visit) {

            echo '<tr>';
            echo '<td>' . esc_html($visit->visit_date) . '</td>';
            echo '<td>' . esc_html($visit->visit_time) . '</td>';
            echo '<td><a href="https://whatismyip.live/ip/' . esc_html($visit->ip_address) . '" target="_blank">' . esc_html($visit->ip_address) . '</a></td>';
            echo '<td>' . esc_html($visit->user_agent) . '</td>';
            echo '</tr>';

   
        }
    } else {
        echo '<tbody><tr><td colspan="4">Nessuna visita registrata.</td></tr></tbody>';
    }

    wp_die(); // Termina il processo Ajax
}


?>
