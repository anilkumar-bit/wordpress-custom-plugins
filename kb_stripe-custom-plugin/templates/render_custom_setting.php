<?php 
	global $wpdb;
	if (isset($_POST["custom_stripe_account_id"])) {
		$custom_stripe_account_id = sanitize_text_field($_POST["custom_stripe_account_id"]);
		$kb_conncted_account_commission = sanitize_text_field($_POST["kb_conncted_account_commission"]);

		update_option("kb_conncted_account_id_stripe", $custom_stripe_account_id);
		update_option("kb_conncted_account_commission", $kb_conncted_account_commission);

		echo '<div class="updated"><p>Settings saved.</p></div>';
	}

	$table_name = $wpdb->prefix . "custom_stripe_data1";
	$api_key = $wpdb->get_var("SELECT api_key FROM $table_name ORDER BY id DESC LIMIT 1");

	$stripe = new \Stripe\StripeClient($api_key);
	try {
		$accounts = $stripe->accounts->all(["limit" => 50]);
	} catch (Exception $e) {
		echo "Error retrieving connected accounts: " . $e->getMessage();
		exit();
	}

	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["account_id"]) && !empty($_POST["account_id"])) {
		try {
			$selected_account = $stripe->accounts->retrieve($_POST["account_id"]);
		} catch (Exception $e) {
			echo "Error retrieving account details: " . $e->getMessage();
			exit();
		}
	}

	?>
	<div class="wrap">
		<h2><?php _e("Custom Gateway Settings", "custom-stripe"); ?></h2>
		<form method="post" action="">
			<?php settings_fields("custom_stripe_settings_group"); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e("Connect Account ID", "custom-stripe"); ?></th>
					<td>
						<input type="text" name="custom_stripe_account_id" value="<?php echo esc_attr(get_option("kb_conncted_account_id_stripe")); ?>">
					</td>
					<th scope="row"><?php _e("Admin Commission :", "custom-stripe"); ?>  </th>
					<td>
						<input type="text" name="kb_conncted_account_commission" value="<?php echo esc_attr(get_option("kb_conncted_account_commission")); ?>">%
					</td> 
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<div>
			<!--<div id="create_account_section">
				<div class="row">
					<div class="col">
						<h1 class="calendar-heading">Shortcode for Create New Connected Account</h1>
					</div>
				</div>
				<div class="div mt-5 py-2 px-2">
					<p><b>Copy ShortCode : </b>[custom_template]</p>
				</div>
			</div>-->
		</div>
		<h2>Select a Account To see details of Connected Account </h2>
		<form id="accountForm" method="post" action="">
			<label for="account_id">Select an account:</label>
			<select name="account_id" id="account_id" onchange="document.getElementById('accountForm').submit();">
				<option value="">Select an account</option>
				<?php foreach ($accounts->data as $account): ?>
					<option value="<?= $account->id ?>" <?= isset($selected_account) && $selected_account->id == $account->id ? "selected" : "" ?>><?= $account->id ?></option>
				<?php endforeach; ?>
			</select>
		</form>

		<?php if (isset($selected_account)): ?>
			<div>
				<h3>Account Details:</h3>
				<pre><?php print_r($selected_account); ?></pre>
			</div>
		<?php endif; ?>
	</div>
	<?php