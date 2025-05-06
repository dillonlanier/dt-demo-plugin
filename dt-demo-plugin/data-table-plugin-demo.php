<?php
/**
 * Plugin Name: Data Table Demo
 * Description: Demo plugin using Data Tables, REST API, AJAX, SQL 
 * Author: Dillon Lanier
 * Auther URI: https://dsmokerlanier.com/
 * Version: 1.0.0
 * Text Domain: data table demo
 */
if( !defined('ABSPATH') ) {
	exit;
}
class TradeLog {
	public function __construct() {
		// Add assets (js, css, etc)
		add_action('wp_enqueue_scripts', array($this, 'load_assets'));
		// shortcode
		add_shortcode('data_table_demo', array($this, 'load_shortcode'));
		// register rest apis 
		add_action('rest_api_init', array($this, 'register_rest_api'));
	}
	// ------------------------------------------
	// Enqueue the JS and CSS files 
	// ------------------------------------------	
	public function load_assets() {
		wp_enqueue_style('log_table', plugin_dir_url(__FILE__) . 'css/style.css', array(), 1, 'all');
		wp_enqueue_style('bootstrap', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css', array(), 1, 'all');
		wp_enqueue_style('datatables.min', plugin_dir_url(__FILE__) . 'css/datatables.min.css', array(), 1, 'all');
		wp_enqueue_style('datatables', plugin_dir_url(__FILE__) . 'css/datatables.css', array(), 1, 'all');
		wp_enqueue_script('log_input_form', plugin_dir_url(__FILE__) . 'js/javascript.js', array(), 1, 'all');
		wp_enqueue_script('jquery', plugin_dir_url(__FILE__) . 'js/jquery-3.7.1.min.js', array(), 1, 'all');
		wp_enqueue_script('bootstrap.bundle.min.js', plugin_dir_url(__FILE__) . 'js/bootstrap.bundle.min.js', array( 'jquery' ), '', true);
		wp_enqueue_script('datatables.min.js', plugin_dir_url(__FILE__) . 'js/datatables.min.js', array( 'jquery' ), '', true);
		wp_enqueue_script('datatables.js', plugin_dir_url(__FILE__) . 'js/datatables.js', array( 'jquery' ), '', true);
	}
	// ------------------------------------------
	// Register API endpoints
	// ------------------------------------------	
	public function register_rest_api() {
		register_rest_route('data_table_demo_ep/v1', 'create_new_row', array(
			'methods' => 'POST',
			'callback' => array($this, 'create_new_row'),
			'permission_callback' => '__return_true'
		));
		register_rest_route('data_table_demo_ep/v1', 'update_table', array(
			'methods' => 'POST',
			'callback' => array($this, 'update_table'),
			'permission_callback' => '__return_true'
		));
		register_rest_route('data_table_demo_ep/v1', 'refresh_table', array(
			'methods' => 'GET',
			'callback' => array($this, 'refresh_table'),
			'permission_callback' => '__return_true'
		));
	}
	// ------------------------------------------
	// Basic HTML structure for the demo Data Tables plugin
	// ------------------------------------------	
	public function load_shortcode() {
		ob_start();?>
		<div id='log-table-container'>
			<div id='log-table-inner-container'>
				<table id='custom-display-table'>
					<thead>
						<tr>
							<th> ID </th>
							<th>Artist</th>
							<th>Album</th>
							<th>Release Date</th>
							<th>Length</th>
							<th>Nubmer of Songs</th>
							<th>Score</th>
							<th>EDIT</th>
							
						</tr>
					</thead>
					<!-- tbody goes here, populated by refresh_table() -->
				</table>
			</div>
		</div>
		<div class="button_container">
		    <form class="modal-log-form initial-input-form">
		    	<button type="submit" method="post" class="btn btn-primary log-form-btn" data-toggle="modal" data-target="#fm-1">Add New Row</button>
		    </form>
		</div>
		<?php return ob_get_clean();
	}
	// ------------------------------------------
	// Creates most current table row and data html 
	// Used on site load AND at end of AJAX requests 
	// ------------------------------------------	
	public function refresh_table($data) {
		// $data = 0 when first loading site (no nonce to check)
		if ($data != 0) {
			$params = $this->check_nonce_get_params($data);
		}
		global $wpdb;
		$current_user_id = get_current_user_id();
		$prepared_id = $wpdb->prepare('%s', $current_user_id);
		$data = $wpdb->get_results("SELECT * FROM `dt_demo_plugin_table` WHERE `account_id`=$prepared_id");
		// Add dispaly ID to each json object
		$i = 1;
		foreach($data as $item) {
			$item->display_id = strval($i);
			$i = $i + 1;
		}
		return $data;
	}
	// ------------------------------------------
	// Creates new DB entry when user clicks 'log new trade' button
	// In current implementation, no data is passed, all empty vals
	// ------------------------------------------
	public function create_new_row($data) {
		$params = $this->check_nonce_get_params($data);
		$current_user_id = get_current_user_id();
		global $wpdb;
		$fields = array();
		$values = array();
		foreach($params as $key => $val) {
			if($val) {
				array_push($fields, $wpdb->prepare('%i', $key));
				array_push($values, $wpdb->prepare('%s', $val));
			}
		}
		array_push($fields, $wpdb->prepare('%i', 'account_id'));
		array_push($values, $wpdb->prepare('%s', $current_user_id));
		// implode() combines an array with the specified seperator
		$fields = implode(', ', $fields);
		$values = implode(', ', $values);
		$query = "INSERT INTO `dt_demo_plugin_table` ($fields) VALUES ($values)";
		$list = $wpdb->get_results($query);
		return $list;
	}	
	// ------------------------------------------
	// Used to save in-line table editor updates
	// ------------------------------------------
	public function update_table($data) {
		$params = $this->check_nonce_get_params($data);
		global $wpdb;
		$key_val_statement = '';
		foreach($params as $key => $val) {
			if($key=='id') {
				$id_to_edit = $wpdb->prepare('%s', $val);
			} else if($val) {
				if($key_val_statement != ''){
					$key_val_statement = $key_val_statement . ', ';
				}
				$key_val_statement = $key_val_statement . $wpdb->prepare('%i=%s', [$key, $val]);
			}
		}
		$update_query = "UPDATE `dt_demo_plugin_table` SET $key_val_statement WHERE `id`=$id_to_edit";
		$list = $wpdb->get_results($update_query);
		return $list;
	}
	// ------------------------------------------
	// Helper function that checks the nonce is valid for security
	// ------------------------------------------
	public function check_nonce_get_params($data) {
		$headers = $data->get_headers();
		$params = $data->get_params();
		$nonce = $headers['x_wp_nonce'][0];
		if(!wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_REST_Response('Trade not logged', 422);
		}
		return $params;
	}			
}
new TradeLog;
?>