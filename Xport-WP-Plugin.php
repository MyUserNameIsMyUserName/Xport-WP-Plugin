<?php
/**
 * Plugin Name: Xport DB Backup
 * Plugin URI: https://xportcbd.com/
 * Description: Export db for backup with a single click from wordpress admin.
 * Version: 1.0
 * Author: Slavko Vuletic 
 * Author URI: https://xportcbd.com/
 */


class Xport_DB_Plugin {

    public function __construct() {
    	// Hook into the admin menu
    	add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
    }

    public function create_plugin_settings_page() {
    	// Add the menu item and page
    	$page_title = 'Xport Settings Page';
    	$menu_title = 'Xport';
    	$capability = 'manage_options';
    	$slug = 'xport-plugin';
    	$callback = array( $this, 'plugin_settings_page_content' );
    	$icon = 'dashicons-admin-plugins';
    	$position = 100;

    	add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
    }

    public function plugin_settings_page_content() {
        if( $_POST['updated'] === 'true' ){
            $this->handle_form();
        }elseif( $_POST['export'] === 'true' ){
            $this->handle_form_export();
        }elseif( $_POST['delete'] === 'true' ){
            $this->form_handle_delete();
        } ?>
    	<div class="wrap">
    		<h2>Xportdb Settings Page</h2>
    		<form method="POST">
                <input type="hidden" name="updated" value="true" />
                <?php wp_nonce_field( 'xport_update', 'xport_form' ); ?>
                <table class="form-table">
                	<tbody>
                        <tr>
                    		<th><label for="database_name">Database Name:</label></th>
                    		<td><input name="database_name" id="database_name" type="text" value="<?php echo get_option('xport_database_name'); ?>" class="regular-text" /></td>
                    	</tr>
                        <tr>
                    		<th><label for="database_username">Username:</label></th>
                    		<td><input name="database_username" id="database_username" type="text" value="<?php echo get_option('xport_database_username'); ?>" class="regular-text" /></td>
                    	</tr>
                        <tr>
                    		<th><label for="database_password">Password:</label></th>
                    		<td><input name="database_password" id="database_password" type="text" value="<?php echo get_option('xport_database_password'); ?>" class="regular-text" /></td>
                    	</tr>
                        <tr>
                    		<th><label for="database_host">Host:</label></th>
                    		<td><input name="database_host" id="database_host" type="text" value="<?php echo get_option('xport_database_host'); ?>" class="regular-text" /></td>
                    	</tr>
                	</tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Info">
                </p>
            </form>
            <form method="POST">
                <input type="hidden" name="export" value="true" />
                <?php wp_nonce_field( 'xport_update_export', 'xport_form_export' ); ?>
                
                <p class="submit">
                    <input type="submit" name="export_button" id="export" class="button button-primary" value="Export!">
                </p>
            </form>
            <?php
            $files=glob(ABSPATH . 'wp-content/uploads/ic-exports/*.sql');
            if (!empty($files)){
            ?>
            <h2>List of database exports:</h2>
            <?php
            }
            foreach ($files as $file) {
                ?>
                <form method="POST">
                    <input type="hidden" name="delete" value="true" />
                    <?php wp_nonce_field( 'xport_update_delete', 'xport_form_delete' ); ?>
                    <?php
                    echo "<a href='/wp-content/uploads/ic-exports/".basename($file)."' target='_blank'>".basename($file)."</a>";
                    ?>
                    <input type="hidden" name="file-address" value="<?= basename($file)?>" />
                    <input type="submit" name="delete_button" id="delete-<?= basename($file)?>" class="button button-primary" value="Delete!">
                </form>
                <?php
            }
            ?>
    	</div> <?php
    }

    public function handle_form() {
        if( ! isset( $_POST['xport_form'] ) || ! wp_verify_nonce( $_POST['xport_form'], 'xport_update' ) ){ ?>
           <div class="error">
               <p>Sorry, your nonce was not correct. Please try again.</p>
           </div> <?php
           exit;
        } else {
            
            $database_name = sanitize_text_field( $_POST['database_name'] );
            $database_username = sanitize_text_field( $_POST['database_username'] );
            $database_password = sanitize_text_field( $_POST['database_password'] );
            $database_host = sanitize_text_field( $_POST['database_host'] );

            if( ($database_name != "") || ($database_username != "") || ($database_password != "") || ($database_host != "") ){
                update_option( 'xport_database_name', $database_name );
                update_option( 'xport_database_username', $database_username );
                update_option( 'xport_database_password', $database_password );
                update_option( 'xport_database_host', $database_host );?>
                <div class="updated">
                    <p>Your fields were saved!</p>
                </div> <?php
            } else { ?>
                <div class="error">
                    <p>Check your inputs, something is missing.</p>
                </div> <?php
            }
        }
    }

    public function handle_form_export(){
        if( ! isset( $_POST['xport_form_export'] ) || ! wp_verify_nonce( $_POST['xport_form_export'], 'xport_update_export' ) ){ ?>
            <div class="error">
                <p>Sorry, your nonce was not correct.</p>
            </div> <?php
            exit;
        } else {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);

            $database = get_option('xport_database_name');
            $user = get_option('xport_database_username');
            $pass = get_option('xport_database_password');
            $host = get_option('xport_database_host');
            $dir = ABSPATH . 'wp-content/uploads/ic-exports/database'.time().'.sql';

            $helperString = "<h3>Backing up database to `<code>{$dir}</code>`</h3>";

            exec("mysqldump --user={$user} --password={$pass} --host={$host} {$database} --result-file={$dir} 2>&1", $output);
            
            
            ?>
            <div class="error">
                <p><?php var_dump($output); ?></p>
            </div> <?php
        }
    }

    public function form_handle_delete(){
        if( ! isset( $_POST['xport_form_delete'] ) || ! wp_verify_nonce( $_POST['xport_form_delete'], 'xport_update_delete' ) ){ ?>
            <div class="error">
                <p>Sorry, your nonce was not correct.</p>
            </div> <?php
        } else {?>
            <div class="error">
                <p>File Deleted: <?= $_POST['file-address'] ?></p>
            </div> <?php
            wp_delete_file( ABSPATH . 'wp-content/uploads/ic-exports/'.$_POST['file-address'] );
        }
    }
}
new Xport_DB_Plugin();