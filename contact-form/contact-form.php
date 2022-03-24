<?php
/*
Plugin Name: Contact Form
Description: For creating and saving contact info
Version: 1.0
Author: Lena Hovhannisyan
*/

if (!defined("ABSPATH")) {
    exit();
}

class ContactForm
{
    public function __construct()
    {
        // Create Custom post type
        //        add_action('init', array($this, 'create_custom_post_type'));

        // Add assets(js, css)
        add_action("wp_enqueue_scripts", [$this, "load_assets"]);

        // Add shortcode
        add_shortcode("contact-form", [$this, "load_shortcode"]);

        // Register REST API
        add_action("rest_api_init", [$this, "register_rest_api"]);
    }

    public function create_custom_post_type()
    {
        $args = [
            "public" => true,
            "has_archive" => true,
            "supports" => ["title"],
            "exclude_from_search" => true,
            "publicly_queryable" => false,
            "capability" => "manage_options",
            "labels" => [
                "name" => "Contact Form",
                "singular_name" => "Contact Form Entry",
            ],
            "menu_icon" => "dashicons-forms",
        ];

        register_post_type("contact_form", $args);
    }

    public function load_assets()
    {
        wp_enqueue_style(
            "style",
            plugin_dir_url(__FILE__) . "assets/css/style.css",
            [],
            1,
            "all"
        );

        wp_enqueue_script(
            "script",
            plugin_dir_url(__FILE__) . "assets/js/script.js",
            ["jquery"],
            false,
            true
        );
        wp_localize_script("script", "restObj", [
            "restURL" => rest_url(),
            "restNonce" => wp_create_nonce("wp_rest"),
        ]);
    }

    public function load_shortcode()
    {
        return <<<HTML
       <div class="inner">
           <form id="contact-form" name="form" enctype="multipart/form-data">
               <h3>Contact Form</h3>
               <div class="form-group">
                   <div class="form-wrapper">
                       <label for="">First Name</label>
                       <input name="first_name" type="text" class="form-control">
                   </div>
                   <div class="form-wrapper">
                       <label for="">Last Name</label>
                       <input name="last-name" type="text" class="form-control">
                   </div>
               </div>
               <div class="form-wrapper">
                   <label for="">Email</label>
                   <input name="email" type="text" class="form-control">
               </div>
               <div class="form-wrapper">
                   <label for="">Date of Birthday</label>
                   <input name="dob" type="date" class="form-control">
               </div>
               <div class="form-wrapper">
                   <label for="">Upload a file</label>
                   <input name="file" type="file" class="form-control">
               </div>
               <div class="checkbox">
                   <label>
                       <input name="checkbox" type="checkbox"> I accept the Terms of Use & Privacy Policy.
                       <span class="checkmark"></span>
                   </label>
               </div>
               <button type="submit">Submit</button>
           </form>
       </div>
HTML;
    }

    public function register_rest_api()
    {
        register_rest_route("contact-form/v1", "/send-email/", [
            "methods" => "POST",
            "callback" => [$this, "handle_contact_form"],
        ]);
    }

    public function handle_contact_form($data)
    {
        $headers = $data->get_headers();
        $params = $data->get_params();
        $nonce = $headers["x_wp_nonce"][0];

        if (!wp_verify_nonce($nonce, "wp_rest")) {
            return new WP_REST_Response("Message not sent", 422);
        }

        wp_mail($params->email,"Thank you","Thank you for registering.");

        process_contact_form();
        submitsForm("custom_contact_form", $params);
    }
}

new ContactForm();

function process_contact_form()
{
    global $wpdb;
    $params = $_POST;
    //create table if not exists*

    $table_name = $wpdb->prefix . "custom_contact_form";
    $query = $wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $wpdb->esc_like($table_name)
    );
    if (!$wpdb->get_var($query) == $table_name) {
        $sql = "CREATE TABLE {$table_name} (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            date VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        if ($wpdb->query($sql)) {
            submitsForm($table_name, $params);
        }
    } else {
        submitsForm($table_name, $params);
    }

    die();
}

function submitsForm($table_name, $params)
{
    global $wpdb;

    $curTime = date("Y-m-d H:i:s");

    $query = "INSERT INTO {$table_name}(first_name, last_name, email,date ) VALUES('{$params["first_name"]}', '{$params["last_name"]}', '{$params["email"]}','{$curTime}')";

    if ($wpdb->query($query)) {
        print_r($query);
    } else {
        print_r("Error");
    }
}

function my_admin_menu()
{
    add_menu_page(
        __("Contact Form", "my-textdomain"),
        __("Contact Form", "my-textdomain"),
        "manage_options",
        "contact_form",
        "contact_form_page_contents",
        "dashicons-forms",
        10
    );
}

add_action("admin_menu", "my_admin_menu");

function contact_form_page_contents()
{
    ?>

<h1><?php esc_html_e("Welcome!!!", "my-plugin-textdomain"); ?></h1>
    <style>
        .admin-table {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 90%;
        }

        .admin-table td, .admin-table th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .admin-table tr:nth-child(even){background-color: #f2f2f2;}

        .admin-table tr:hover {background-color: #ddd;}

        .admin-table th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #04AA6D;
            color: white;
        }
    </style>
    <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>File</th>
                </tr>
                <?php
                $db = mysqli_connect(
                    null,
                    DB_USER,
                    DB_PASSWORD,
                    DB_NAME,
                    3306,
                    "/cloudsql/<INSTANCE_CONNECTION_NAME>"
                );
                $sql = "SELECT * FROM `wp_custom_contact_form`";
                $result = mysqli_query($db, $sql);
                if ($result) {
                    while ($r = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td>
                            <?php echo $r["id"]; ?>
                        </td>
                        <td>
                            <?php echo $r["first_name"]; ?>
                        </td>
                         <td>
                            <?php echo $r["last_name"]; ?>
                        </td>
                         <td>
                            <?php echo $r["email"]; ?>
                        </td>
                        <td>
                            <?php echo $r["date"]; ?>
                        </td>
                        <td>
                            <?php echo $r["file"]; ?>
                        </td>
                    </tr>
                <?php }
                }?>
            </table>

<?php
}

function register_my_plugin_scripts()
{
    wp_register_style("my-plugin", plugins_url("ddd/css/plugin.css"));

    wp_register_script("my-plugin", plugins_url("ddd/js/plugin.js"));
}

add_action("admin_enqueue_scripts", "register_my_plugin_scripts");

function load_my_plugin_scripts($hook)
{
    if ($hook != "toplevel_page_sample-page") {
        return;
    }
}

add_action("admin_enqueue_scripts", "load_my_plugin_scripts");
