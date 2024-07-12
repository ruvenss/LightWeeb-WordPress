<?php
/*
/*
Plugin Name: LightWeb WordPress
Description: Sends an event to your LightWeb server when a post is created or updated.
Version: 1.0.0
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

function lightweb_send_post_event($post_ID, $post, $update)
{
    // Ensure the function runs only once per post creation or update
    if (wp_is_post_revision($post_ID) || wp_is_post_autosave($post_ID)) {
        return;
    }
    // Define the URL of the remote server
    $remote_url = 'https://stage.energieplus-lesite.be/api/v1/?a=wp_article_update';
    switch ($post->post_type) {
        case 'post':
            $header = 'header.html';
            $footer = 'footer.html';
            break;
        case 'page':
            $header = 'header.html';
            $footer = 'footer.html';
            break;
        default:
            # code...
            break;
    }
    // Prepare data to send
    $permalink = get_permalink($post->ID);
    $data = array(
        'a' => 'wp_article_update',
        'post_id' => $post_ID,
        'post_title' => json_encode($post->post_title),
        'post_description' => get_post_meta($post_ID, "description", true),
        'post_content' => base64_encode($post->post_content),
        'post_status' => $post->post_status,
        'post_author' => $post->post_author,
        'post_date' => $post->post_date,
        'post_modified' => $post->post_modified,
        'post_uri' => $post->post_uri,
        'post_type' => $post->post_type,
        'post_parent' => $post->post_parent,
        'post_permalink' => $permalink,
        'featured_image' => wp_get_attachment_url(get_post_thumbnail_id($post_ID)),
        'header' => $header,
        'footer' => $footer,
        'secret' => AUTH_KEY,
        'site_url' => site_url(),
        'update' => $update
    );
    // Convert data to JSON format
    $data_json = json_encode($data);
    // Initialize cURL session
    $ch = curl_init($remote_url);
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
        error_log('Sending to ' . $remote_url . ':' . "\n" . json_encode($data, JSON_PRETTY_PRINT));
        error_log('Response from remote server: ' . $response);
    }
    // Close cURL session
    curl_close($ch);
}
