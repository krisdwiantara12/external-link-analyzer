<?php
/*
Plugin Name: External Link Analyzer
Description: Plugin untuk menganalisis link eksternal dalam postingan berdasarkan root domain, menampilkan analisis, dan mengedit postingan.
Version: 1.0
Author: Sanepo
*/

// Menambahkan menu di area admin untuk pengaturan
add_action('admin_menu', 'plugin_menu_analisator_link');

function plugin_menu_analisator_link()
{
    add_options_page('Pengaturan Analisator Link', 'Analisator Link', 'manage_options', 'analisator-link-settings', 'analisator_link_settings_page');
}

function analisator_link_settings_page()
{
    $root_domain = get_option('root_domain');
?>
    <div class="wrap">
        <h2>Pengaturan Analisator Link</h2>
        <form method="post" action="options.php">
            <?php settings_fields('analisator-link-settings-group'); ?>
            <?php do_settings_sections('analisator-link-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Root Domain:</th>
                    <td><input type="text" name="root_domain" value="<?php echo esc_attr($root_domain); ?>" /></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <style>
        .analysis-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .post-info,
        .link-analysis {
            width: 48%;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }

        .link-analysis table {
            width: 100%;
            border-collapse: collapse;
        }

        .link-analysis th,
        .link-analysis td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .link-analysis th {
            background-color: #e9e9e9;
        }
    </style>
<?php

    // Menampilkan analisis link eksternal dari semua postingan
    $args = array(
        'post_type'   => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1
    );
    $posts = get_posts($args);

    foreach ($posts as $post) {
        $post_content = $post->post_content;

        // Mengekstrak semua tautan dari konten postingan
        preg_match_all('/<a (.*?)href="(http(s)?:\/\/(.*?))">(.*?)<\/a>/i', $post_content, $matches, PREG_SET_ORDER);

        $external_link_found = false;

        // Loop melalui semua tautan
        foreach ($matches as $match) {
            $link = $match[2];
            $link_domain = parse_url($link, PHP_URL_HOST);

            // Memeriksa apakah tautan adalah tautan eksternal (tidak termasuk dalam root domain)
            if (strpos($link_domain, $root_domain) === false) {
                $external_link_found = true;
                break; // Keluar dari loop jika ditemukan tautan eksternal
            }
        }

        // Menampilkan analisis hanya jika tautan eksternal ditemukan dalam postingan
        if ($external_link_found) {
            echo '<div class="analysis-container">';
            echo '<div class="post-info">';
            echo '<h3>' . $post->post_title . '</h3>';
            echo '<a href="' . get_edit_post_link($post->ID) . '">Edit Postingan</a>';
            echo '</div>';
            echo '<div class="link-analysis">';
            echo '<table>';
            echo '<tr><th>Teks Anchor dari Link Eksternal</th></tr>';
            echo analisa_link_eksternal($post->post_content, $root_domain);
            echo '</table>';
            echo '</div>';
            echo '</div>';
        }
    }
}

function analisa_link_eksternal($content, $root_domain)
{
    $pattern = '/<a (.*?)href="(http(s)?://(.*?))">(.*?)<\/a>/i';
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    $output = '';
    foreach ($matches as $match) {
        $link = $match[2];
        $anchor_text = $match[5]; // Teks anchor dari link
        $link_domain = parse_url($link, PHP_URL_HOST);

        if (strpos($link_domain, $root_domain) === false) {
            $output .= '<tr><td>' . esc_html($anchor_text) . '</td></tr>';
        }
    }

    return $output;
}

add_action('admin_init', 'register_analisator_link_settings');

function register_analisator_link_settings()
{
    register_setting('analisator-link-settings-group', 'root_domain');
}

function analisator_link_add_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=analisator-link-settings">' . __('Settings') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'analisator_link_add_settings_link');
