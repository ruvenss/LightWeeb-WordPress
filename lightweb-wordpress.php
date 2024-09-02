<?php
/*
/*
Plugin Name: LightWeb WordPress
Description: Sends an event to your LightWeb server when a post is created or updated.
Version: 1.0.3
Author: NIZU <marvin.ai@nizu.io>
Author URI: https://nizu.io/en/
Text Domain: NIZU
Network: true
Requires at least: 3.9
Requires PHP: 8.1
License: GPLv3
License URI: https://raw.githubusercontent.com/ruvenss/LightWeb-WordPress/main/LICENSE
*/

// Hook into post creation and update
add_action('save_post', 'lightweb_send_post_event', 10, 3);
// Hook into category creation and update
add_action('created_category', 'lightweb_send_category_event', 10, 2);
add_action('edited_category', 'lightweb_send_category_event', 10, 2);
function lightweb_get_branch($wpdb, $taxonomies_tree)
{
    $taxonomies_tree2 = [];
    if (sizeof($taxonomies_tree) > 0) {
        for ($t = 0; $t < sizeof($taxonomies_tree); $t++) {
            $term_id = $taxonomies_tree[$t]['term_id'];
            $query = 'SELECT `term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count` FROM `wp_term_taxonomy` WHERE `parent`=' . $term_id . ' AND `taxonomy` = "category" AND `parent`>0 AND `description`<>""  ORDER BY `term_taxonomy_id` DESC';
            $taxonomies_tree2 = $wpdb->get_results($query, ARRAY_A);
            $taxonomies_tree2[$t]['branch'] = $taxonomies_tree2;
        }
    }
    return $taxonomies_tree2;
}
function lightweb_send_category_event($term_id, $tt_id)
{
    global $wpdb;

    // Get the category object
    $category = get_term($term_id);
    if (is_wp_error($category)) {
        error_log('Failed to get term: ' . $category->get_error_message());
        return;
    }

    // Get Lightweb options
    $lightweb_options = get_option('lightweb_option_name');
    if (!$lightweb_options || empty($lightweb_options['lightweb_stage_server_0'])) {
        error_log('Lightweb options are not set or invalid.');
        return;
    }

    // Define the URL of the remote server
    $remote_url = 'https://' . $lightweb_options['lightweb_stage_server_0'] . '/api/v1/?a=wp_category_update';

    // Fetch taxonomies
    $taxonomies_ground = get_taxonomies_hierarchy();
    // Encode permalink and description in base64
    array_walk_recursive($taxonomies_ground, function (&$value, $key) {
        if ($key == 'permalink' || $key == 'description') {
            $value = base64_encode($value);
        }
    });

    // Prepare data to send
    $permalink = get_category_link($term_id);
    $data = array(
        'a' => 'wp_category_update',
        'term_id' => $term_id,
        'name' => $category->name,
        'slug' => $category->slug,
        'description' => base64_encode($category->description),
        'taxonomy' => $category->taxonomy,
        'parent' => $category->parent,
        'count' => $category->count,
        'secret' => AUTH_KEY,
        'site_url' => site_url(),
        'post_permalink' => base64_encode($permalink),
        'taxonomies' => $taxonomies_ground
    );

    // Send data to the remote server
    send_data_to_remote_server($remote_url, $data);
}

function get_taxonomies_hierarchy($parent = 0)
{
    $args = array(
        'taxonomy' => 'category',
        'parent' => $parent,
        'hide_empty' => false
    );
    $categories = get_terms($args);
    $result = array();
    foreach ($categories as $category) {
        $item = array(
            'term_taxonomy_id' => $category->term_taxonomy_id,
            'term_id' => $category->term_id,
            'taxonomy' => $category->taxonomy,
            'post_title' => base64_encode($category->description),
            'post_parent' => $category->parent,
            'count' => $category->count,
            'post_type' => 'category',
            'post_permalink' => base64_encode(get_category_link($category->term_id)),
            'branch' => get_taxonomies_hierarchy($category->term_id)
        );
        $result[] = $item;
    }

    return $result;
}

function send_data_to_remote_server($url, $data)
{
    // Convert data to JSON format
    $data_json = json_encode($data);
    // Initialize cURL session
    $ch = curl_init($url);
    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_json)
        )
    );
    // Execute cURL session and get response
    $response = curl_exec($ch);
    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log('cURL error: ' . curl_error($ch));
    } else {
        // Log the response from the remote server
        error_log('Response from remote server: ' . $response);
    }
    // Close cURL session
    curl_close($ch);
}
function lightweb_send_post_event($post_ID, $post, $update)
{
    // Ensure the function runs only once per post creation or update
    if (wp_is_post_revision($post_ID) || wp_is_post_autosave($post_ID)) {
        return;
    }
    $lightweb_options = get_option('lightweb_option_name'); // Array of All Options
    // Define the URL of the remote server
    $remote_url = 'https://' . $lightweb_options['lightweb_stage_server_0'] . '/api/';
    if ($lightweb_options['auto_publish_when_a_post_is_created_or_updated_1'] == "true") {
        $auto_publish = true;
    }
    switch ($post->post_type) {
        case 'post':
        case 'category':
            $header = $lightweb_options['post_header_2'];
            $footer = $lightweb_options['post_footer_3'];
            break;
        case 'page':
            $header = $lightweb_options['page_header_4'];
            $footer = $lightweb_options['page_footer_5'];
            break;
        default:
            # code...
            break;
    }
    // Prepare data to send
    $permalink = get_permalink($post->ID);
    $categories = get_the_category($post->ID);
    $content = trim($post->post_content);
    $content = str_replace(["´", "'", "’"], "&apos;", $content);
    $content = str_replace("À", "&Agrave;", $content);
    $content = str_replace("à", "&agrave;", $content);
    $content = str_replace("Â", "&Acirc;", $content);
    $content = str_replace("â", "&acirc;", $content);
    $content = str_replace("Æ", "&AElig;", $content);
    $content = str_replace("æ", "&aelig;", $content);
    $content = str_replace("Ç", "&Ccedil;", $content);
    $content = str_replace("ç", "&ccedil;", $content);
    $content = str_replace("È", "&Egrave;", $content);
    $content = str_replace("è", "&egrave;", $content);
    $content = str_replace("É", "&Eacute;", $content);
    $content = str_replace("é", "&eacute;", $content);
    $content = str_replace("Ê", "&Ecirc;", $content);
    $content = str_replace("ê", "&ecirc;", $content);
    $content = str_replace("Ë", "&Euml;", $content);
    $content = str_replace("ë", "&euml;", $content);
    $content = str_replace("Î", "&Icirc;", $content);
    $content = str_replace("î", "&icirc;", $content);
    $content = str_replace("Ï", "&Iuml;", $content);
    $content = str_replace("ï", "&iuml;", $content);
    $content = str_replace("Ô", "&Ocirc;", $content);
    $content = str_replace("ô", "&ocirc;", $content);
    $content = str_replace("Œ", "&OElig;", $content);
    $content = str_replace("œ", "&oelig;", $content);
    $content = str_replace("Ù", "&Ugrave;", $content);
    $content = str_replace("ù", "&ugrave;", $content);
    $content = str_replace("Û", "&Ucirc;", $content);
    $content = str_replace("û", "&ucirc;", $content);
    $content = str_replace("Ü", "&Uuml;", $content);
    $content = str_replace("ü", "&uuml;", $content);
    $content = str_replace("Ý", "&Yacute;", $content);
    $content = str_replace("ý", "&yacute;", $content);
    $content = str_replace("Þ", "&THORN;", $content);
    $content = str_replace("þ", "&thorn;", $content);
    $content = str_replace("ß", "&szlig;", $content);
    $content = str_replace("ÿ", "&yuml;", $content);
    $content = str_replace("«", "&laquo;", $content);
    $content = str_replace("»", "&raquo;", $content);

    $title = htmlspecialchars($post->post_title, 0, "UTF-8");
    $data = array(
        'a' => 'wp_article_update',
        'post_id' => $post_ID,
        'post_title' => base64_encode($title),
        'post_description' => get_post_meta($post_ID, "description", true),
        'post_content' => base64_encode($content),
        'post_status' => $post->post_status,
        'post_author_id' => $post->post_author,
        'post_author' => get_the_author_meta("display_name", $post->post_author),
        'post_date' => $post->post_date,
        'post_modified' => $post->post_modified,
        'post_uri' => $post->post_uri,
        'post_type' => $post->post_type,
        'post_parent' => $post->post_parent,
        'post_permalink' => base64_encode($permalink),
        'featured_image' => wp_get_attachment_url(get_post_thumbnail_id($post_ID)),
        'header' => $header,
        'footer' => $footer,
        'secret' => AUTH_KEY,
        'site_url' => site_url(),
        'update' => $update,
        'categories' => $categories
    );
    // Convert data to JSON format
    $data_json = json_encode($data);
    // Initialize cURL session
    $ch = curl_init($remote_url . "v1/?a=wp_article_update");
    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_json)
        )
    );
    // Execute cURL session and get response
    $response = curl_exec($ch);
    curl_close($ch);
    // Check for cURL errors
    // Log the response from the remote server
    error_log("Request to lightweb server: \n" . $data_json . "\n\t-\t-\t-\t-\n");
    error_log("Response from lightweb server: " . $response);

    if (curl_errno($ch)) {
        error_log('cURL error: ' . curl_error($ch));
    } else {
        if (json_decode($response, true)) {
            $data = json_decode($response, true);
            if (isset($data['data']['Authorization'])) {
                $lightweb_Authorization = $data['Authorization'];
                if ($auto_publish) {
                    lightweb_publish($remote_url, $lightweb_Authorization);
                }
            }
        }

    }
    // Close cURL session
}
function lightweb_publish($remote_url, $lightweb_Authorization)
{
    $curl = curl_init();
    curl_setopt_array(
        $curl,
        array(
            CURLOPT_URL => $remote_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => '{"a":"publish"}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $lightweb_Authorization
            ),
        )
    );
    $response = curl_exec($curl);
    curl_close($curl);
}
/**
 * Generated by the WordPress Option Page generator
 * at http://jeremyhixon.com/wp-tools/option-page/
 */

class LightWeb
{
    private $lightweb_options;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'lightweb_add_plugin_page'));
        add_action('admin_init', array($this, 'lightweb_page_init'));
    }

    public function lightweb_add_plugin_page()
    {
        add_management_page(
            'LightWeb', // page_title
            'LightWeb', // menu_title
            'manage_options', // capability
            'lightweb', // menu_slug
            array($this, 'lightweb_create_admin_page') // function
        );
    }

    public function lightweb_create_admin_page()
    {
        $this->lightweb_options = get_option('lightweb_option_name'); ?>

        <div class="wrap">
            <h2>LightWeb</h2>
            <p>LightWeb settings</p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('lightweb_option_group');
                do_settings_sections('lightweb-admin');
                submit_button();
                ?>
            </form>
        </div>
    <?php }

    public function lightweb_page_init()
    {
        register_setting(
            'lightweb_option_group', // option_group
            'lightweb_option_name', // option_name
            array($this, 'lightweb_sanitize') // sanitize_callback
        );

        add_settings_section(
            'lightweb_setting_section', // id
            'Settings', // title
            array($this, 'lightweb_section_info'), // callback
            'lightweb-admin' // page
        );

        add_settings_field(
            'lightweb_stage_server_0', // id
            'LightWeb Stage Host', // title
            array($this, 'lightweb_stage_server_0_callback'), // callback
            'lightweb-admin', // page
            'lightweb_setting_section' // section
        );

        add_settings_field(
            'auto_publish_when_a_post_is_created_or_updated_1', // id
            'Auto Publish when a post is created or updated', // title
            array($this, 'auto_publish_when_a_post_is_created_or_updated_1_callback'), // callback
            'lightweb-admin', // page
            'lightweb_setting_section' // section
        );

        add_settings_field(
            'post_header_2', // id
            'Post header', // title
            array($this, 'post_header_2_callback'), // callback
            'lightweb-admin', // page
            'lightweb_setting_section' // section
        );

        add_settings_field(
            'post_footer_3', // id
            'Post footer', // title
            array($this, 'post_footer_3_callback'), // callback
            'lightweb-admin', // page
            'lightweb_setting_section' // section
        );

        add_settings_field(
            'page_header_4', // id
            'Page Header', // title
            array($this, 'page_header_4_callback'), // callback
            'lightweb-admin', // page
            'lightweb_setting_section' // section
        );

        add_settings_field(
            'page_footer_5', // id
            'Page footer', // title
            array($this, 'page_footer_5_callback'), // callback
            'lightweb-admin', // page
            'lightweb_setting_section' // section
        );
    }

    public function lightweb_sanitize($input)
    {
        $sanitary_values = array();
        if (isset($input['lightweb_stage_server_0'])) {
            $sanitary_values['lightweb_stage_server_0'] = sanitize_text_field($input['lightweb_stage_server_0']);
        }

        if (isset($input['auto_publish_when_a_post_is_created_or_updated_1'])) {
            $sanitary_values['auto_publish_when_a_post_is_created_or_updated_1'] = $input['auto_publish_when_a_post_is_created_or_updated_1'];
        }

        if (isset($input['post_header_2'])) {
            $sanitary_values['post_header_2'] = sanitize_text_field($input['post_header_2']);
        }

        if (isset($input['post_footer_3'])) {
            $sanitary_values['post_footer_3'] = sanitize_text_field($input['post_footer_3']);
        }

        if (isset($input['page_header_4'])) {
            $sanitary_values['page_header_4'] = sanitize_text_field($input['page_header_4']);
        }

        if (isset($input['page_footer_5'])) {
            $sanitary_values['page_footer_5'] = sanitize_text_field($input['page_footer_5']);
        }

        return $sanitary_values;
    }

    public function lightweb_section_info()
    {

    }

    public function lightweb_stage_server_0_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="lightweb_option_name[lightweb_stage_server_0]" id="lightweb_stage_server_0" value="%s">',
            isset($this->lightweb_options['lightweb_stage_server_0']) ? esc_attr($this->lightweb_options['lightweb_stage_server_0']) : ''
        );
    }

    public function auto_publish_when_a_post_is_created_or_updated_1_callback()
    {
        ?> <select name="lightweb_option_name[auto_publish_when_a_post_is_created_or_updated_1]"
            id="auto_publish_when_a_post_is_created_or_updated_1">
            <?php $selected = (isset($this->lightweb_options['auto_publish_when_a_post_is_created_or_updated_1']) && $this->lightweb_options['auto_publish_when_a_post_is_created_or_updated_1'] === 'true') ? 'selected' : ''; ?>
            <option value="true" <?php echo $selected; ?>>Yes</option>
            <?php $selected = (isset($this->lightweb_options['auto_publish_when_a_post_is_created_or_updated_1']) && $this->lightweb_options['auto_publish_when_a_post_is_created_or_updated_1'] === 'false') ? 'selected' : ''; ?>
            <option value="false" <?php echo $selected; ?>>No</option>
        </select>
        <?php
    }

    public function post_header_2_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="lightweb_option_name[post_header_2]" id="post_header_2" value="%s">',
            isset($this->lightweb_options['post_header_2']) ? esc_attr($this->lightweb_options['post_header_2']) : ''
        );
    }

    public function post_footer_3_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="lightweb_option_name[post_footer_3]" id="post_footer_3" value="%s">',
            isset($this->lightweb_options['post_footer_3']) ? esc_attr($this->lightweb_options['post_footer_3']) : ''
        );
    }

    public function page_header_4_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="lightweb_option_name[page_header_4]" id="page_header_4" value="%s">',
            isset($this->lightweb_options['page_header_4']) ? esc_attr($this->lightweb_options['page_header_4']) : ''
        );
    }

    public function page_footer_5_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="lightweb_option_name[page_footer_5]" id="page_footer_5" value="%s">',
            isset($this->lightweb_options['page_footer_5']) ? esc_attr($this->lightweb_options['page_footer_5']) : ''
        );
    }

}
if (is_admin())
    $lightweb = new LightWeb();

function custom_taxonomy_walker($taxonomy, $parent = 0)
{
    $terms = get_terms($taxonomy, array('parent' => $parent, 'hide_empty' => false));
    //If there are terms, start displaying
    if (count($terms) > 0) {
        //Displaying as a list

        return $terms;
    }
    return [];
}
