<?php
/*
	Plugin Name: Parse Push for Wordpress
	Plugin URI: http://quiroa.me/plugins/parse-push-wordpress/
	Description: 
	Author: Steven Quiroa
	Version: 1.0.0
	Author URI: http://quiroa.me
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

require 'vendor/autoload.php';

use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseACL;
use Parse\ParsePush;
use Parse\ParseUser;
use Parse\ParseInstallation;
use Parse\ParseException;
use Parse\ParseAnalytics;
use Parse\ParseFile;
use Parse\ParseCloud;
use Parse\ParseClient;

add_action( 'admin_menu', 'register_parse_push' );

function trim_value(&$value){ 
    $value = trim($value); 
}

add_action( 'admin_enqueue_scripts', 'my_enqueue' );
function my_enqueue($hook) {
	// die($hook);
    if( 'toplevel_page_parse-push' != $hook ) return;
        
	wp_enqueue_script( 'ajax-script', plugins_url( '/assets/parse-push.js', __FILE__ ), array(), '1.0.0', true);

	$max_length = (int) esc_attr( get_option('pp-max-length') );
	// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
	wp_localize_script( 'ajax-script', 'ajax_object',
            array( 
            	'ajax_url' => admin_url( 'admin-ajax.php' ), 
            	'max_length' => ($max_length > 0) ? ($max_length) : 100,
            	'ajax_nonce' => wp_create_nonce( "pp-super-security-string" )
            )  
	);
}

function register_parse_push(){
	add_menu_page( 'Parse Push', 'Parse push', 'manage_options', 'parse-push', 'parse_push_sender','dashicons-smartphone', 98 );
	add_submenu_page( 'parse-push', 'Send Push', 'Send Parse Push', 'manage_options', 'parse-push', 'parse_push_sender' );
	add_submenu_page( 'parse-push', 'Parse Push Settings', 'Settings', 'manage_options', 'parse_push_settings', 'parse_push_settings' );
	add_action( 'admin_init', 'register_parse_push_settings' );
}

function parse_push_sender(){
	$channels = esc_attr( get_option('pp-channels'));
	if ($channels) {
		$channels = explode(',', $channels);
		array_walk($channels, 'trim_value');
	}
	?>
	<div class="wrap">
		<h2>Send Parse Push</h2>
		<form action="admin-post.php" name="sendpush" method="POST">
			<table class="form-table">
				<tr valign="top">
		        	<th scope="row">Mensaje</th>
		        	<td>
		        	<textarea name="message" id="message" cols="30" rows="3"></textarea>
		        	<p class="description" id="message_characters">Caracteres Restantes: <span id="message_chars">100</span></p>
		        	</td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">Dispositivos</th>
		        	<td>
		        	<fieldset><legend class="screen-reader-text"><span>Dispositivos</span></legend>
					<label for="scope_ios">
						<input name="scope" id="scope_all" value="all" checked="checked" type="radio">
						All</label>
					<br>
					<label for="scope_android">
						<input name="scope" id="scope_android" value="android" type="radio"> 
						Android</label>
					<br>
					<label for="scope_ios">
						<input name="scope" id="scope_ios" value="ios" type="radio">
						iOs</label>
					</fieldset>
		        	</td>
		        </tr>
		        <?php if (is_array($channels) and count($channels) > 0): ?>		        	
		        <tr valign="top">
		        	<th scope="row">Canales</th>
		        	<td>
		        	<fieldset><legend class="screen-reader-text"><span>Canales</span></legend>
		        	<?php foreach ($channels as $c): ?>
						<label for="channels_<?php echo $c; ?>"><input name="channels" id="channels_<?php echo $c; ?>" value="<?php echo $c; ?>" type="radio"> <?php echo ucfirst($c); ?></label>
						<br/>
		        	<?php endforeach ?>
					</fieldset>
		        	</td>
		        </tr>
		        <?php endif ?>

		    </table>
		    <input type="hidden" name="action" value="send_push_parse">
			<?php submit_button('Send Push'); ?>
		</form>
		<p id="status"></p>
	</div>
<?php
}

function parse_push_settings(){?>
	<div class="wrap">
		<h2>Parse Push Settings</h2>
		<form method="post" action="options.php"> 
			<?php settings_fields( 'parse-push_settings-group' ); ?>
			<?php do_settings_sections( 'parse-push_settings-group' ); ?>
			<table class="form-table">
		        <tr valign="top">
		        	<th scope="row">Application ID</th>
		        	<td><input type="text" class="regular-text"name="pp-application_id" value="<?php echo esc_attr( get_option('pp-application_id') ); ?>" /></td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">Rest API Key</th>
		        	<td><input type="text" class="regular-text"name="pp-rest_api_key" value="<?php echo esc_attr( get_option('pp-rest_api_key') ); ?>" /></td>
		        </tr>
				<tr valign="top">
		        	<th scope="row">Master Api</th>
		        	<td><input type="text" class="regular-text"name="pp-master_api_key" value="<?php echo esc_attr( get_option('pp-master_api_key') ); ?>" />
		        	</td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">Max Length</th>
		        	<td><input type="number" class="regular-int"name="pp-max-length" min="0" value="<?php echo esc_attr( get_option('pp-max-length') ); ?>" />
		        	</td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">Channels</th>
		        	<td><input type="text" class="regular-text"name="pp-channels" min="0" value="<?php echo esc_attr( get_option('pp-channels') ); ?>" />
		        	<p class="description" id="message_characters">Coloque los canales literalmente como aparecen en parse y separados por comas. Ej: eudos,pitufo,conejo</p>
		        	</td>
		        </tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
<?php 
}

function register_parse_push_settings(){
	register_setting( 'parse-push_settings-group', 'pp-master_api_key' );
	register_setting( 'parse-push_settings-group', 'pp-application_id' );
	register_setting( 'parse-push_settings-group', 'pp-rest_api_key');
	register_setting( 'parse-push_settings-group', 'pp-max-length');
	register_setting( 'parse-push_settings-group', 'pp-channels');
}

add_action( 'wp_ajax_send_push_parse', 'send_push_parse_callback' );

function send_push_parse_callback(){
	check_ajax_referer( 'pp-super-security-string', 'security' );

	$message = sanitize_text_field( $_POST['message'] );

	$data = array(
		'data' => array(
			'alert' => 	$message
		)
	);
	
	$app_id = esc_attr( get_option('pp-application_id') );
	$rest_key = esc_attr( get_option('pp-rest_api_key') );
	$master_key = esc_attr( get_option('pp-master_api_key') );
	
	ParseClient::initialize( $app_id, $rest_key, $master_key );
	
	if (isset($_POST['scope']) and ($_POST['scope'] != '' || $_POST['scope'] != 'all' ) ) {
		$scope = sanitize_text_field( $_POST['scope'] );
		$query = ParseInstallation::query();
		$query->equalTo('deviceType', $scope);
	} 
	
	if (isset($_POST['channels']) and $_POST['channels'] != '') {
		$channels = sanitize_text_field( $_POST['channels'] );
		if (!isset($query)) $query = ParseInstallation::query();
		$query->equalTo('channels', $channels);		
	}
	
	if (isset($query)) {
		$data['where'] = $query;
	}

	try{
		$result = ParsePush::send($data);
	}catch (ParseException $error){
		status_header( 500 );
		$response = array(
			'code' => $error->getCode(),
			'message' => $error->getMessage()
		);
		wp_send_json( $response );
	}

	$res = $result;
	status_header(200);
	wp_send_json( $res );
}