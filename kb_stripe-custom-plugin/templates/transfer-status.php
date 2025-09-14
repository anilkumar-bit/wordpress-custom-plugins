<?php

global $wpdb;

$records_per_page = 10;
$current_page = isset($_GET["paged"]) ? max(1, intval($_GET["paged"])) : 1;
$offset = ($current_page - 1) * $records_per_page;

$total_records = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}transferlog_table"
);

// Handle submission
//handle_transfer_now();

$transfer_data = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}transferlog_table ORDER BY order_id DESC LIMIT %d OFFSET %d",
        $records_per_page,
        $offset
    )
);

// Calculate the total number of pages
$total_pages = ceil($total_records / $records_per_page);
?>

<div class="wrap">
    <h2><?php _e("Vendor Dashboard", "custom-stripe"); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e("ID", "custom-stripe"); ?></th>
                <th><?php _e("Connected Account ID", "custom-stripe"); ?></th>
                <th><?php _e("Transfer Amount", "custom-stripe"); ?></th>
                <th><?php _e("Order ID", "custom-stripe"); ?></th>
                <th><?php _e("Error Message", "custom-stripe"); ?></th>
                <th><?php _e("Status", "custom-stripe"); ?></th>
                <th><?php _e("Action", "custom-stripe"); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($transfer_data) {
                foreach ($transfer_data as $data) { ?>
                    <tr>
                        <td><?php echo $data->id; ?></td>
                        <td><?php echo $data->connected_account_id; ?></td>
                        <td><?php echo $data->currency . ' ' . $data->amount; ?></td>
                        <td>#<?php echo $data->order_id; ?></td>
                        <td><?php echo $data->error_message; ?></td>
                        <td><?php echo $data->status; ?></td>
                        <td>
                            <form method="post" action="<?php echo admin_url("admin.php?page=custom_gateway_transfer_status"); ?>">
                                <input type="hidden" name="action" value="transfer_now">
                                <input type="hidden" name="id" value="<?php echo $data->id; ?>">
                                <input type="hidden" name="connected_account_id" value="<?php echo $data->connected_account_id; ?>">
                                <input type="hidden" name="amount" value="<?php echo $data->amount; ?>">
                                <input type="hidden" name="currency" value="<?php echo $data->currency; ?>">
                                <input type="hidden" name="order_id" value="<?php echo $data->order_id; ?>">
                                <button type="submit">Transfer Now</button>
                            </form>
                        </td>
                    </tr>
                <?php }
            } else { ?>
                <tr>
                    <td colspan="7"><?php _e("No records found.", "custom-stripe"); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php echo paginate_links([
                "base" => add_query_arg("paged", "%#%"),
                "format" => "",
                "prev_text" => __("&laquo; Previous"),
                "next_text" => __("Next &raquo;"),
                "total" => $total_pages,
                "current" => $current_page,
            ]); ?>
        </div>
    </div>
</div>
