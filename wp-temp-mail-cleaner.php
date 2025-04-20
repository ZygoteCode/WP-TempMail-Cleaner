<?php
/*
Plugin Name: WP TempMail Cleaner
Plugin URI: https://github.com/GabryB03/WP-TempMail-Cleaner
Description: Delete registered users with temp-mail in your website.
Version: 1.0
Author: GabryB03
Author URI: https://github.com/GabryB03
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Security, prevent direct access

// Function to load the list of domains from a .txt file
function load_temp_mail_domains_from_file()
{
    $file_path = plugin_dir_path(__FILE__) . 'temp_mail_domains.txt';
    $domains = [];

    // Check if the file exists
    if (file_exists($file_path))
    {
        // Read each line of the file and add it to the $domains array
        $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line)
        {
            $domains[] = trim($line); // Remove extra spaces
        }

        // Store the domains in a WordPress option (in the database)
        update_option('temp_mail_domains_cache', $domains);
    }

    return $domains;
}

// Function to get the unwanted domains (first loads from cache, if not found, loads from the file)
function get_temp_mail_domains()
{
    // Check if the domains are already in cache
    $domains = get_option('temp_mail_domains_cache');
    
    // If not found in cache, load from file
    if (false === $domains)
    {
        $domains = load_temp_mail_domains_from_file();
    }

    return $domains;
}

// Function to remove users with specific domains
function remove_users_with_temp_mail_domains()
{
    // Load the unwanted domains from the cache
    $temp_mail_domains = get_temp_mail_domains();

    // Retrieve all users
    $users = get_users();

    // Check each user
    foreach ($users as $user)
    {
        $email = $user->user_email;

        // Check if the email contains one of the unwanted domains
        foreach ($temp_mail_domains as $domain)
        {
            if (strpos($email, '@' . $domain) !== false)
            {
                // Delete the user if the domain matches
                wp_delete_user($user->ID);
                break;
            }
        }
    }
}

// Add an option in the admin menu to perform the user removal
function temp_mail_cleaner_menu()
{
    add_submenu_page(
        'users.php',
        'WP TempMail Cleaner',
        'WP TempMail Cleaner',
        'manage_options',
        'wp-temp-mail-cleaner',
        'temp_mail_cleaner_page'
    );
}

add_action('admin_menu', 'temp_mail_cleaner_menu');

// Page for manual user removal
function temp_mail_cleaner_page()
{
    ?>

    <div class="wrap">

        <h1>WP TempMail Cleaner</h1>
        <p>Click the button below to remove users with temp-mail domains from your website.</p>

        <form method="post">
            <?php submit_button('Remove Users'); ?>
        </form>

        <?php

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            remove_users_with_temp_mail_domains();
            echo '<p>Users with temp-mail domains successfully removed.</p>';
        }
        ?>

    </div>

    <?php
}

// Load the domains at plugin activation
function temp_mail_cleaner_activate()
{
    get_temp_mail_domains(); // Load the domain list during activation
}

register_activation_hook(__FILE__, 'temp_mail_cleaner_activate');

// Add a GitHub link to the plugin page
function add_github_link_to_plugin($links)
{
    $github_link = '<a href="https://github.com/GabryB03/WP-TempMail-Cleaner" target="_blank">GitHub</a>';
    array_unshift($links, $github_link); // Adds the GitHub link before others
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_github_link_to_plugin');

// Add "Settings" button in the plugin list
function add_settings_button_to_plugin($links)
{
    $settings_link = '<a href="' . admin_url('users.php?page=wp-temp-mail-cleaner') . '">Settings</a>';
    array_unshift($links, $settings_link); // Adds the "Settings" link before others
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_settings_button_to_plugin');
