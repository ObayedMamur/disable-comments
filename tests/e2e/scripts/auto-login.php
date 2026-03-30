<?php
/**
 * Auto-login MU Plugin for E2E Testing
 * 
 * Usage: Visit `/?autologin=editor` to instantly login as the 'editor' user.
 */

add_action('init', function () {
    if (isset($_GET['autologin']) && !empty($_GET['autologin'])) {
        $username = sanitize_user($_GET['autologin']);
        $user = get_user_by('login', $username);

        if ($user) {
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);
            do_action('wp_login', $user->user_login, $user);

            $redirect_to = admin_url();
            wp_safe_redirect($redirect_to);
            exit;
        } else {
            wp_die("User '{$username}' not found for auto-login.");
        }
    }
});

// Fix for Docker environments where wp-cron and loopbacks attempt to hit localhost:8080 and fail.
// We intercept all WordPress cURL requests to localhost:8080 and route them to 127.0.0.1:80 internally.
add_action('http_api_curl', function ($handle, $r, $url) {
    if (strpos($url, 'localhost:8080') !== false) {
        curl_setopt($handle, CURLOPT_URL, str_replace('localhost:8080', '127.0.0.1', $url));
        curl_setopt($handle, CURLOPT_PORT, 80);
    }
}, 10, 3);