(function($){
	// ----------------------------------------------
	// Set up DataTable functionality
	// ----------------------------------------------
	$(document).ready( function () {
		var nonce = '<?php echo wp_create_nonce('wp_rest');?>';
	    var theTable = new DataTable('#custom-display-table', {
		    ajax: {
		    	url:'<?php echo get_rest_url(null, 'iota_log_ep/v1/refresh_table');?>',
		    	dataSrc: '',
		    	method: 'get',
	            headers: {'x_wp_nonce': nonce},
	            dataType: 'json',
		    },
		    initComplete: function (settings, json) {
		        var numClosedTrades = 0;
		        var i = 0;
		        while(i < json.length) {
		        	if (json[i].contract_exit_price != null) {
		        		// only want to count trades with an exit price
		        		numClosedTrades = numClosedTrades + 1;
		        	} 
		        	i = i + 1;
		        }
		        const openTradesNum = json.length - numClosedTrades;
		        document.getElementsByClassName('open-trades-div')[0].innerHTML = 'Open Trades: ' + openTradesNum;
		    },
		    layout: {
		    	topStart: 'search',
		    	top2Start: function () {
		            let textOpenTrades = document.createElement('div');
		            textOpenTrades.classList.add('open-trades-div');
		            textOpenTrades.innerHTML = 'Open Trades: ';
		            return textOpenTrades;
		        },
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
		        bottom2Start: 'pageLength'
		    },
		    columns: [
		        { data: "display_id" },
		        { data: "entry_date" },
		        { data: "symbol" },
		        { data: "contract_entry_price" },
		        { data: "spread_description" },
		        { data: "debit_credit" },
		        { data: "net_debit_credit" },
		        { data: "moneyness" },
		        { data: "lots_quantity" },
		        { data: "dte" },
		        { data: "rr" },
		        { data: "exit_date" },
		        { data: "contract_exit_price" },
		        { 
		        	data: "",
		        	  	render: function (data) {
		                	return "<button type='submit' class='btn td-btn edit-row'>Edit</button>";
		            } 
		        }
		    ],
			columnDefs: [
			    { className: "display_id", targets: 0},
			    {  className: "entry_date", targets: 1},
			    {  className: "symbol", targets: 2},
			    {  className: "contract_entry_price", targets: 3},
			    {  className: "spread_description", targets: 4},
			    {  className: "debit_credit", targets: 5},
			    {  className: "net_debit_credit", targets: 6},
			    {  className: "moneyness", targets: 7},
			    {  className: "lots_quantity", targets: 8},
			    {  className: "dte", targets: 9},
			    {  className: "rr", targets: 10},
			    {  className: "exit_date", targets: 11},
			    {  className: "contract_exit_price", targets: 12},
			    {  className: "edit-button", targets: 13}
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
			$('#login-register-popup-modal').modal();
		}
		// ----------------------------------------------
		// Event listener log form buttons
		// ----------------------------------------------
		$('.modal-log-form').submit( function(event) {
			event.preventDefault();
			var form = $(this).serialize();
			var nonce = '<?php echo wp_create_nonce('wp_rest');?>';
			var parent = $(this).closest("tr");
    	    var id = $(parent).attr("data-row");
    	    var data_id = {id: id};
    	    const user_id = <?php echo get_current_user_id(); ?>;
    	    // Check if the user is logged in
    	    if (user_id == 0) {
    	    	alert('You most create an account to log trades');
    	    } else {
    	    	// ----------------------------------------------
				// If the button is the Log Trade button, create new database entry 
				// ----------------------------------------------
	    	    if (this.classList.contains('initial-input-form')) {
	    	    	$.ajax({
						method:'post',
						url:'<?php echo get_rest_url(null, 'iota_log_ep/v1/create_new_row');?>',
						headers: {'x_wp_nonce': nonce},
						data: form
					})
	    	    }
	    	    // ----------------------------------------------
				// Modal buttons functionality
				// ----------------------------------------------
	    	    else if (this.classList.contains('input-form')){
					$.ajax({
						method:'post',
						url:'<?php echo get_rest_url(null, 'iota_log_ep/v1/edit_trade');?>',
						headers: {'x_wp_nonce': nonce},
						data: form,
						success: function(){
	    	            	theTable.ajax.reload();
	    	            }
					})
				}
				$('.modal').modal('hide');
			}
			// Clear the form when the final input button is clicked
			if (this.classList.contains('final-input-form')) {
				const modal_forms = document.getElementsByClassName("input-form");
				for (let i = 0; i < modal_forms.length; i++) {
			        modal_forms[i].reset();
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
				const labels = ['.entry_date', '.symbol', '.contract_entry_price', '.spread_description', '.debit_credit', '.net_debit_credit',   '.moneyness', '.lots_quantity', '.dte', '.rr', '.exit_date', '.contract_exit_price'];
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
		        // var id = $(parent).attr("id");
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
    	            url: '<?php echo get_rest_url(null, 'iota_log_ep/v1/update_table');?>',
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