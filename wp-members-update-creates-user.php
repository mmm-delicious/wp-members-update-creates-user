<?php
/**
 * Plugin Name: MMM Username Registration API
 * Description: Registers users via REST API for Elementor forms with WP-Members activation.
 * Version: 3.1
 * Author: MMM Delicious
 * Developer: Mark McDonnell
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 6.7
 */

defined( 'ABSPATH' ) || exit;

add_action('rest_api_init', function () {
    register_rest_route('mmm/v1', '/register', [
        'methods'  => 'POST',
        'callback' => 'mmm_rest_register_user',
        'permission_callback' => '__return_true'
    ]);
});

function mmm_rest_register_user(WP_REST_Request $request) {
    $json_data = $request->get_json_params();
    $form_data = $request->get_body_params();
    $data = !empty($json_data) ? $json_data : $form_data;

    // If Elementor form structure, flatten it
    if (!empty($data['fields']) && is_array($data['fields'])) {
        $flat = [];
        foreach ($data['fields'] as $key => $field) {
            $flat[$key] = $field['value'] ?? '';
        }
        $data = $flat;
    }

    $email     = sanitize_email($data['email'] ?? '');
    $username  = sanitize_user($data['username'] ?? '');
    $first     = sanitize_text_field($data['first_name'] ?? '');
    $last      = sanitize_text_field($data['last_name'] ?? '');

    if (!$email || !$first || !$last) {
        return new WP_REST_Response(['success' => true, 'message' => 'Silent success for missing fields'], 200);
    }

    if (email_exists($email)) {
        $user = get_user_by('email', $email);
        if ($user && strtolower(get_user_meta($user->ID, 'last_name', true)) === strtolower($last)) {
            // Update existing user's info if matched
            $meta_map = [
                'phone' => 'phone1',
                'mobile_phone' => 'phone1',
                'address' => 'billing_address_1',
                'city' => 'billing_city',
                'state' => 'billing_state',
                'zip' => 'billing_postcode'
            ];

            foreach ($meta_map as $field => $meta_key) {
                if (!empty($data[$field])) {
                    $new_value = sanitize_text_field($data[$field]);
                    $current_value = get_user_meta($user->ID, $meta_key, true);
                    if ($new_value !== $current_value) {
                        update_user_meta($user->ID, $meta_key, $new_value);
                    }
                }
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Existing user updated.'
            ], 200);
        } else {
            // Silent quit if user doesn't match last name
            return new WP_REST_Response(['success' => true, 'message' => 'Silent success for unmatched user'], 200);
        }
    }

    if (empty($username) || username_exists($username)) {
        $base = strtolower(substr($first, 0, 1) . $last);
        $username = sanitize_user($base);
        $suffix = 1;
        while (username_exists($username)) {
            $username = sanitize_user($base . $suffix);
            $suffix++;
        }
    }

    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'first_name' => $first,
        'last_name'  => $last,
        'role'       => 'subscriber',
    ]);

    if (is_wp_error($user_id)) {
        return new WP_REST_Response(['success' => true, 'message' => 'Silent success despite WP error'], 200);
    }

    update_user_meta($user_id, 'wpmem_reg_activate', true);
    update_user_meta($user_id, 'wpmem_block', 1);

    $meta_map = [
        'phone' => 'phone1',
        'mobile_phone' => 'phone1',
        'address' => 'billing_address_1',
        'city' => 'billing_city',
        'state' => 'billing_state',
        'zip' => 'billing_postcode',
        'ssn' => 'LastSNN',
        'job_title' => 'job_title',
        'job_classification' => 'job_classification',
        'classification' => 'job_classification',
        'accept' => 'newsletter',
    ];

    foreach ($meta_map as $field => $meta_key) {
        if (!empty($data[$field])) {
            update_user_meta($user_id, $meta_key, sanitize_text_field($data[$field]));
        }
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'User created.'], 200);
}
