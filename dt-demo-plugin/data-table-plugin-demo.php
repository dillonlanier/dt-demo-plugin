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
class DemoDataTablePlugin {
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
		wp_enqueue_style('datatables.min', plugin_dir_url(__FILE__) . 'css/datatables.min.css', array(), 1, 'all');
		wp_enqueue_script('log_input_form', plugin_dir_url(__FILE__) . 'js/javascript.js', array(), 1, 'all');
		wp_enqueue_script('jquery', plugin_dir_url(__FILE__) . 'js/jquery-3.7.1.min.js', array(), 1, 'all');
		wp_enqueue_script('datatables.min.js', plugin_dir_url(__FILE__) . 'js/datatables.min.js', array( 'jquery' ), '', true);
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
							<th>Rating</th>
							<th>EDIT</th>
							
						</tr>
					</thead>
					<!-- tbody goes here, populated by refresh_table() -->
				</table>
			</div>
		</div>
		<div class="button_container">
		    <form class="create-row-form initial-input-form">
		    	<button type="submit" method="post" class="btn btn-primary form-btn">Add New Row</button>
		    </form>
		</div>
		<script>
		(function($){
		// ----------------------------------------------
		// Set up DataTable functionality
		// ----------------------------------------------
		$(document).ready( function () {
			var nonce = '<?php echo wp_create_nonce('wp_rest');?>';
		    	var theTable = new DataTable('#custom-display-table', {
			    ajax: {
			    	url:'<?php echo get_rest_url(null, 'data_table_demo_ep/v1/refresh_table');?>',
			    	dataSrc: '',
			    	method: 'get',
		            headers: {'x_wp_nonce': nonce},
		            dataType: 'json',
			    },
			    layout: {
			    	topStart: 'search',
			        topEnd: {
			            buttons: [{
	                        extend: 'csvHtml5',
	                        text: 'Download to CSV',
	                        exportOptions: {
	                            modifier: {
	                                search: 'none'
	                            }
	                        }
	                    }]
			        },
			        bottom2Start: 'pageLength',
			    },
			    columns: [
			    	{ data: "display_id" },
			        { data: "artist" },
			        { data: "album" },
			        { data: "release_date" },
			        { data: "length" },
			        { data: "number_songs" },
			        { data: "rating" },
			        { 
			        	data: "",
			        	  	render: function (data) {
			                	return "<button type='submit' class='btn td-btn edit-row'>Edit</button>";
			            } 
			        }
			    ],
				columnDefs: [
				    {  className: "display_id", targets: 0},
				    {  className: "artist", targets: 1},
				    {  className: "album", targets: 2},
				    {  className: "release_date", targets: 3},
				    {  className: "length", targets: 4},
				    {  className: "number_songs", targets: 5},
				    {  className: "rating", targets: 6},
				    {  className: "edit-button", targets: 7},
				],
			    rowId: 'id',
			});
			// ----------------------------------------------
			// Disable log button if user is not logged into an account
			// Pop up modal to login or register
			// ----------------------------------------------
			const user_id = <?php echo get_current_user_id(); ?>;
			if (user_id == 0) {
				const button = document.getElementsByClassName('log-form-btn')[0];
				button.setAttribute('data-target', '');
			}
			// ----------------------------------------------
			// Event listener log form buttons
			// ----------------------------------------------
			$('.create-row-form').submit( function(event) {
				event.preventDefault();
				var form = $(this).serialize();
				var nonce = '<?php echo wp_create_nonce('wp_rest');?>';
				const user_id = <?php echo get_current_user_id(); ?>;
			    	// Check if the user is logged in
			    	if (user_id == 0) {
					alert('You most create an account to use the table');
			    	} else {
				// ----------------------------------------------
				// If button has desired class, create new row
				// ----------------------------------------------
			    	    if (this.classList.contains('initial-input-form')) {
			    	    	$.ajax({
						method:'post',
						url:'<?php echo get_rest_url(null, 'data_table_demo_ep/v1/create_new_row');?>',
						headers: {'x_wp_nonce': nonce},
						data: form,
				    	        success: function(){
				    	            theTable.ajax.reload();
				    	        }
					});
		    	    	    }
		    		} 
			});
			// ----------------------------------------------
			// Event listener for table rows
			// ----------------------------------------------
			$(document).on('click', '.td-btn', function(element) {
				// ----------------------------------------------
				// Edit button functionality for table rows
				// ----------------------------------------------
				if(element.target.classList.contains('edit-row')) {
					const labels = ['.artist', '.album', '.release_date', '.length', '.number_songs', '.rating'];
					// Save any unsaved rows and close them
					var unsaved_rows = $( '.btn-info-save');
					if (unsaved_rows.length == 1) {
						var usr_parent = $(unsaved_rows[0]).closest("tr");
						usr_parent.removeClass('editing');
						var usr_inputs = $( '.edit-row-input');
						for (var i=0; i<labels.length; i += 1) {
							$(usr_parent).children(labels[i]).html(usr_inputs[i].value);
						}
						$(unsaved_rows[0]).children('button').prevObject.replaceWith("<button type='submit' class='btn td-btn edit-row'>Edit</button>");
					} 
					// Now create the new editable row
				        var parent = $(this).closest("tr");
				        parent.addClass('editing');
				        labels.forEach(function(key) {
						var text = $(parent).children(key).html();
						$(parent).children(key).html("<input class='edit-row-input' size='1' style='width:100%' name='" + key.slice(1) + "' value='" + text + "'>");
					});
			        	$('.editing button').replaceWith("<button type='submit' class='btn td-btn btn-info-save'>Save</button>");
				}
				// ----------------------------------------------
				// Save button functionality for table rows
				// ----------------------------------------------
				if(element.target.classList.contains('btn-info-save')) {
					var nonce = '<?php echo wp_create_nonce('wp_rest');?>';
		        		var parent = $(this).closest("tr");
	    	        		var id = $(parent).attr("id");
			    	        var data = {id: id};
			    	        $("input.edit-row-input").each( function(){
			    	            var col = $(this).attr("name");
			    	            var val = $(this).val();
			    	            data[col] = val;
			    	        });
			    	        $.ajax({
			    	            url: '<?php echo get_rest_url(null, 'data_table_demo_ep/v1/update_table');?>',
			    	            method: 'post',
			    	            headers: {'x_wp_nonce': nonce},
			    	            dataType: 'json',
			    	            data: data,
			    	            success: function(){
			    	            	theTable.ajax.reload();
			    	            }
			    	        });
				}
			});
		});
	})(jQuery)
	</script>
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
	// Creates new DB entry when user clicks 'Add new row' button
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
			return new WP_REST_Response('Nonce Error', 422);
		}
		return $params;
	}			
}
new DemoDataTablePlugin;
?>
