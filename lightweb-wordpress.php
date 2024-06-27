<?php
/*
Plugin Name: LightWeb WordPress
Description: Sends an event to your LightWeb server when a post is created or updated.
Version: 1.0
Author: Ruvenss G. Wilches <ruvenss@gmail.com>
*/

// Hook into post creation and update
add_action('save_post', 'send_post_event', 10, 3);

function send_post_event($post_ID, $post, $update) {
    // Ensure the function runs only once per post creation or update
    if (wp_is_post_revision($post_ID) || wp_is_post_autosave($post_ID)) {
        return;
    }

    // Define the URL of the remote server
    $remote_url = 'https://your-remote-server.com/event-endpoint';

    // Prepare data to send
    $data = array(
        'post_id' => $post_ID,
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_status' => $post->post_status,
        'post_author' => $post->post_author,
        'post_date' => $post->post_date,
        'post_modified' => $post->post_modified,
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
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_json)
    ));

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
