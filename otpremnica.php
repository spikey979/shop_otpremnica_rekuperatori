<?php
/*
Plugin Name: KS Otpremnica
Description: Iz zadane narudžbe kreira otpremnicu za firmu
Version: 1.8
Author: Spikey
*/


// Include FPDF library
//require_once plugin_dir_path(__FILE__) . 'lib/fpdf/fpdf.php';
require_once plugin_dir_path(__FILE__) . 'lib/fpdf/tfpdf.php';

require_once plugin_dir_path(__FILE__) . 'pdf_functions.php';


if (!function_exists('custom_log')) {
	function custom_log() {
		$file = ABSPATH.'dump.txt';
		
		$arr = func_get_args();
		$log = "";
		foreach(func_get_args() as $arg){
			if(!empty($log)){$log.="\n";}
			$tmp = var_export($arg, true);
			$tmp = trim($tmp, "'");//remove single quote which was added by var_export()
			$log.= $tmp;
		}

		@file_put_contents($file, utf8_encode($log)."\n", FILE_APPEND);
		unset($tmp);
		unset($log);
	}
}


// Add admin menu
add_action('admin_menu', 'custom_invoice_menu');
function custom_invoice_menu() {
    // Main menu
    add_menu_page(
        'Otpremnica',
        'Otpremnica',
        'manage_options',
        'otpremnica',
        'otpremnica_page',
        'dashicons-media-text',
        30
    );

    // Submenu: Račun Metro
    add_submenu_page(
        'otpremnica',            // Parent slug
        'Račun Metro',           // Page title
        'Račun Metro',           // Menu title
        'manage_options',
        'racun_metro',           // Submenu slug
        'racun_metro_page'       // Callback function
    );

    // Submenu: Račun Pevex
    add_submenu_page(
        'otpremnica',
        'Račun Pevex',
        'Račun Pevex',
        'manage_options',
        'racun_pevex',
        'racun_pevex_page'
    );
}

// Metro sifra robe
function add_metro_sifra_robe_once() {
    if ( get_option( 'metro_sifra_robe_done' ) ) return;

    // Get all products and variations
    $args = array(
        'post_type'      => array( 'product', 'product_variation' ),
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    );

    $posts = get_posts( $args );

    foreach ( $posts as $post_id ) {
        $existing_value = get_post_meta( $post_id, 'metro_sifra_robe', true );

        if ( $existing_value === '' ) {
            update_post_meta( $post_id, 'metro_sifra_robe', '' );
        }
    }

    update_option( 'metro_sifra_robe_done', true );
}
add_action( 'init', 'add_metro_sifra_robe_once' );

// za varijabilne proizvode
function add_metro_sifra_robe_variation_field( $loop, $variation_data, $variation ) {
    // Get existing value
    $value = get_post_meta( $variation->ID, 'metro_sifra_robe', true );
    ?>
    <div class="form-row form-row-full">
        <label><?php _e( 'Metro Šifra Robe', 'woocommerce' ); ?></label>
        <input type="text" class="short" name="metro_sifra_robe[<?php echo $variation->ID; ?>]" value="<?php echo esc_attr( $value ); ?>" />
    </div>
    <?php
}
add_action( 'woocommerce_variation_options_pricing', 'add_metro_sifra_robe_variation_field', 10, 3 );

function save_metro_sifra_robe_variation_field( $variation_id, $i ) {
    if ( isset( $_POST['metro_sifra_robe'][$variation_id] ) ) {
        update_post_meta(
            $variation_id,
            'metro_sifra_robe',
            sanitize_text_field( $_POST['metro_sifra_robe'][$variation_id] )
        );
    }
}
add_action( 'woocommerce_save_product_variation', 'save_metro_sifra_robe_variation_field', 10, 2 );



// Pevex sifra robe
function add_pevex_sifra_robe_once() {
    if ( get_option( 'pevex_sifra_robe_done' ) ) return;

    // Get all products and variations
    $args = array(
        'post_type'      => array( 'product', 'product_variation' ),
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    );

    $posts = get_posts( $args );

    foreach ( $posts as $post_id ) {
        $existing_value = get_post_meta( $post_id, 'pevex_sifra_robe', true );

        if ( $existing_value === '' ) {
            update_post_meta( $post_id, 'pevex_sifra_robe', '' );
        }
    }

    update_option( 'pevex_sifra_robe_done', true );
}
add_action( 'init', 'add_pevex_sifra_robe_once' );

// za varijabilne proizvode
function add_pevex_sifra_robe_variation_field( $loop, $variation_data, $variation ) {
    // Get existing value
    $value = get_post_meta( $variation->ID, 'pevex_sifra_robe', true );
    ?>
    <div class="form-row form-row-full">
        <label><?php _e( 'Pevex Šifra Robe', 'woocommerce' ); ?></label>
        <input type="text" class="short" name="pevex_sifra_robe[<?php echo $variation->ID; ?>]" value="<?php echo esc_attr( $value ); ?>" />
    </div>
    <?php
}
add_action( 'woocommerce_variation_options_pricing', 'add_pevex_sifra_robe_variation_field', 10, 3 );

function save_pevex_sifra_robe_variation_field( $variation_id, $i ) {
    if ( isset( $_POST['pevex_sifra_robe'][$variation_id] ) ) {
        update_post_meta(
            $variation_id,
            'pevex_sifra_robe',
            sanitize_text_field( $_POST['pevex_sifra_robe'][$variation_id] )
        );
    }
}
add_action( 'woocommerce_save_product_variation', 'save_pevex_sifra_robe_variation_field', 10, 2 );


// Admin page content
function otpremnica_page() {
    ?>
    <div class="wrap">
        <h1>Generator otpremnice</h1>
        <form id="otpremnica-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="generiraj_otpremnicu">
            
            <div class="form-row field-short">
                <label for="order_id">Broj narudžbe:</label>
                <input type="text" id="order_id" name="order_id" placeholder="npr. 12345" required>
            </div>

            <div class="form-row field-short">
                <label for="broj_otpremnice">Broj otpremnice:</label>
                <input type="text" id="broj_otpremnice" name="broj_otpremnice" placeholder="npr. 24-2024" required>
            </div>

            <div class="form-row field-short">
                <label for="datum_otpremnice">Datum otpremnice:</label>
                <input type="date" id="datum_otpremnice" name="datum_otpremnice" required>
            </div>

            <div class="form-row">
                <label for="dodatno_polje_1">Dodatno polje 1:</label>
                <input type="text" id="dodatno_polje_1" name="dodatno_polje_1" placeholder="npr. Broj Pevex narudžbe: 123456789">
            </div>

            <div class="form-row">
                <label for="dodatno_polje_2">Dodatno polje 2:</label>
                <input type="text" id="dodatno_polje_2" name="dodatno_polje_2" placeholder="npr. Datum Pevex narudžbe: 05.08.2024.">
            </div>

            <div class="form-row">
                <label for="dodatni_prazni_retci">Dodatni prazni retci:</label>
                <input type="number" id="dodatni_prazni_retci" name="dodatni_prazni_retci" value="0" min="0" max="20">
            </div>

            <?php submit_button('Kreiraj otpremnicu', 'primary', 'submit-btn'); ?>
            <?php wp_nonce_field('generate_invoice_action', 'generate_invoice_nonce'); ?>
        </form>
    </div>

    <style>
        #otpremnica-form {
            max-width: 600px;
        }
        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
           
        }
        .form-row label {
            flex: 0 0 150px; /* Adjust this width as needed */
            margin-right: 10px;
        }
        .form-row input {
            flex: 1;
        }
        .submit {
            margin-top: 20px;
        }

        .field-short {
            width: 50px;
        }


    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#otpremnica-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            
            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(),
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob) {
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = "otpremnica.pdf";
                    link.target = "_blank";
                    link.click();
                },
                error: function() {
                    alert('Error generating PDF');
                }
            });
        });
    });
    </script>
    <?php
}


add_action('admin_post_generiraj_otpremnicu', 'handle_generiraj_otpremnicu');
function handle_generiraj_otpremnicu() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    if (!isset($_POST['generate_invoice_nonce']) || !wp_verify_nonce($_POST['generate_invoice_nonce'], 'generate_invoice_action')) {
        wp_die('Invalid nonce');
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if ($order_id <= 0) {
        wp_die('Invalid Order ID');
    }

    $broj_otpremnice = $_POST['broj_otpremnice'];
    
    $str_date = $_POST['datum_otpremnice'];
    $date = DateTime::createFromFormat('Y-m-d', $str_date);
    if ($date === false) {
        $formatted_date = "Invalid Date";
    } else {
        $formatted_date = $date->format('d.m.Y.');
    }

    $dodatno_polje_1 = $_POST['dodatno_polje_1'];
    $dodatno_polje_2 = $_POST['dodatno_polje_2'];
    $dodatni_prazni_retci = isset($_POST['dodatni_prazni_retci']) ? intval($_POST['dodatni_prazni_retci']) : 0;

    $data = [
        "order_id" => $order_id,
        "broj_otpremnice" => $broj_otpremnice,
        "datum_otpremnice" => $formatted_date,
        "dodatno_polje_1" => $dodatno_polje_1,
        "dodatno_polje_2" => $dodatno_polje_2,
        "dodatni_prazni_retci" => $dodatni_prazni_retci
    ];

    generiraj_otpremnicu($data);
}

add_action('admin_post_generiraj_racun_metro', 'handle_generiraj_racun_metro');
function handle_generiraj_racun_metro() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    if (!isset($_POST['generate_racun_metro_nonce']) || !wp_verify_nonce($_POST['generate_racun_metro_nonce'], 'generate_racun_metro_action')) {
        wp_die('Invalid nonce');
    }

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_die('Invalid WooCommerce order');
    }

    $data = [
        'order_id' => $order_id,
        'broj_racuna' => sanitize_text_field($_POST['broj_racuna']),
        'broj_otpremnice' => sanitize_text_field($_POST['broj_otpremnice']),
        'datum_racuna' => $_POST['datum_racuna'],
        'datum_dospijeca' => $_POST['datum_dospijeca'],
        'datum_narudzbe' => $_POST['datum_narudzbe'],
        'broj_metro_narudzbe' => sanitize_text_field($_POST['broj_metro_narudzbe']),
        'broj_metro_dobavljaca' => sanitize_text_field($_POST['broj_metro_dobavljaca']),
        'order' => $order
    ];

    generiraj_racun_metro($data);
}

add_action('admin_post_generiraj_racun_pevex', 'handle_generiraj_racun_pevex');
function handle_generiraj_racun_pevex() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    if (!isset($_POST['generate_racun_pevex_nonce']) || !wp_verify_nonce($_POST['generate_racun_pevex_nonce'], 'generate_racun_pevex_action')) {
        wp_die('Invalid nonce');
    }

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_die('Invalid WooCommerce order');
    }

    $data = [
        'order_id' => $order_id,
        'broj_racuna' => sanitize_text_field($_POST['broj_racuna']),
        'broj_otpremnice' => sanitize_text_field($_POST['broj_otpremnice']),
        'datum_racuna' => $_POST['datum_racuna'],
        'datum_narudzbe' => $_POST['datum_narudzbe'],
        'broj_pevex_narudzbe' => sanitize_text_field($_POST['broj_pevex_narudzbe']),
        'valuta_placanja' => sanitize_text_field($_POST['valuta_placanja']),
        'order' => $order
    ];

    generiraj_racun_pevex($data);
}





// micanje raznih obavijesti i updejtova s interfejsa plugina
function remove_admin_notices() {
    remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
}

function remove_notices_on_custom_page() {
    $screen = get_current_screen();
    $allowed_ids = [
        'toplevel_page_otpremnica',
        'otpremnica_page_racun_metro',
        'otpremnica_page_racun_pevex'
    ];

    if ($screen && in_array($screen->id, $allowed_ids)) {
        add_action('admin_init', 'remove_admin_notices', 9999);
        add_action('admin_head', 'remove_admin_notices', 9999);
        add_filter('admin_notices', '__return_false');
        add_filter('all_admin_notices', '__return_false');
    }
}
add_action('current_screen', 'remove_notices_on_custom_page');

function remove_specific_notices() {
    $screen = get_current_screen();
    $allowed_ids = [
        'toplevel_page_otpremnica',
        'otpremnica_page_racun_metro',
        'otpremnica_page_racun_pevex'
    ];

    if ($screen && in_array($screen->id, $allowed_ids)) {
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');
    }
}
add_action('in_admin_header', 'remove_specific_notices', 999);

function hide_update_nag() {
    remove_action('admin_notices', 'update_nag', 3);
}
add_action('admin_init', 'hide_update_nag', 1);


function racun_metro_page() {
    ?>
    <div class="wrap">
        <h1>Račun Metro</h1>
        <form id="racun-metro-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="generiraj_racun_metro">

            <div class="form-row field-short">
                <label for="order_id">Broj narudžbe (WooCommerce):</label>
                <input type="text" id="order_id" name="order_id" required>
            </div>

            <div class="form-row field-short">
                <label for="broj_racuna">Broj računa:</label>
                <input type="text" id="broj_racuna" name="broj_racuna" required>
            </div>

            <div class="form-row field-short">
                <label for="broj_otpremnice">Broj otpremnice:</label>
                <input type="text" id="broj_otpremnice" name="broj_otpremnice" required>
            </div>

            <div class="form-row field-short">
                <label for="datum_racuna">Datum računa:</label>
                <input type="date" id="datum_racuna" name="datum_racuna" required>
            </div>

            <div class="form-row field-short">
                <label for="datum_dospijeca">Datum dospijeća:</label>
                <input type="date" id="datum_dospijeca" name="datum_dospijeca" required>
            </div>

            <div class="form-row field-short">
                <label for="datum_narudzbe">Datum narudžbe:</label>
                <input type="date" id="datum_narudzbe" name="datum_narudzbe" required>
            </div>

            <div class="form-row field-short">
                <label for="broj_metro_narudzbe">Broj Metro narudžbe:</label>
                <input type="text" id="broj_metro_narudzbe" name="broj_metro_narudzbe">
            </div>

            <div class="form-row field-short">
                <label for="broj_metro_dobavljaca">Broj Metro dobavljača:</label>
                <input type="text" id="broj_metro_dobavljaca" name="broj_metro_dobavljaca" value="26184">
            </div>

            <?php submit_button('Kreiraj račun Metro', 'primary', 'submit-btn'); ?>
            <?php wp_nonce_field('generate_racun_metro_action', 'generate_racun_metro_nonce'); ?>
        </form>
    </div>

    <style>
        #otpremnica-form {
            max-width: 600px;
        }
        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
           
        }
        .form-row label {
            flex: 0 0 150px; /* Adjust this width as needed */
            margin-right: 10px;
        }
        .form-row input {
            flex: 1;
        }
        .submit {
            margin-top: 20px;
        }

        .field-short {
            width: 50px;
        }


    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#racun-metro-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');

            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(),
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob) {
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = "racun_metro.pdf";
                    link.target = "_blank";
                    link.click();
                },
                error: function() {
                    alert('Greška prilikom generiranja PDF-a.');
                }
            });
        });
    });
    </script>
    <?php
}


function racun_pevex_page() {
    ?>
    <div class="wrap">
        <h1>Račun Pevex</h1>
        <form id="racun-pevex-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="generiraj_racun_pevex">

            <div class="form-row field-short">
                <label for="order_id">Broj narudžbe (WooCommerce):</label>
                <input type="text" id="order_id" name="order_id" required>
            </div>

            <div class="form-row field-short">
                <label for="broj_racuna">Broj računa:</label>
                <input type="text" id="broj_racuna" name="broj_racuna" required>
            </div>

            <div class="form-row field-short">
                <label for="broj_otpremnice">Broj otpremnice:</label>
                <input type="text" id="broj_otpremnice" name="broj_otpremnice" required>
            </div>

            <div class="form-row field-short">
                <label for="datum_racuna">Datum računa:</label>
                <input type="date" id="datum_racuna" name="datum_racuna" required>
            </div>

            <div class="form-row field-short">
                <label for="datum_narudzbe">Datum narudžbe:</label>
                <input type="date" id="datum_narudzbe" name="datum_narudzbe" required>
            </div>

            <div class="form-row field-short">
                <label for="broj_pevex_narudzbe">Broj Pevex narudžbe:</label>
                <input type="text" id="broj_pevex_narudzbe" name="broj_pevex_narudzbe">
            </div>

            <div class="form-row field-short">
                <label for="valuta_placanja">Valuta plaćanja:</label>
                <input type="text" id="valuta_placanja" name="valuta_placanja" value="60 kalendarskih dana">
            </div>
           


            <?php submit_button('Kreiraj račun Pevex', 'primary', 'submit-btn'); ?>
            <?php wp_nonce_field('generate_racun_pevex_action', 'generate_racun_pevex_nonce'); ?>
        </form>
    </div>

    <style>
        #otpremnica-form {
            max-width: 600px;
        }
        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
           
        }
        .form-row label {
            flex: 0 0 150px; /* Adjust this width as needed */
            margin-right: 10px;
        }
        .form-row input {
            flex: 1;
        }
        .submit {
            margin-top: 20px;
        }

        .field-short {
            width: 50px;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#racun-pevex-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');

            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(),
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob) {
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = "racun_pevex.pdf";
                    link.target = "_blank";
                    link.click();
                },
                error: function() {
                    alert('Greška prilikom generiranja PDF-a.');
                }
            });
        });
    });
    </script>
    <?php
}
