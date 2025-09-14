<?php 
 global $wpdb;

    $records_per_page = 10;
    $current_page = isset($_GET["paged"]) ? max(1, intval($_GET["paged"])) : 1;
    $offset = ($current_page - 1) * $records_per_page;

    $total_records = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}Commission_data"
    );

   $commission_data = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}Commission_data ORDER BY order_id DESC LIMIT %d OFFSET %d",
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
                    <th><?php _e(
                        "Connected Account ID",
                        "custom-stripe"
                    ); ?></th>
                    <th><?php _e("Transfer Amount", "custom-stripe"); ?></th>
                    <th><?php _e("Order ID", "custom-stripe"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($commission_data) {
                    foreach ($commission_data as $data) { ?>
                        <tr>
                            <td><?php echo $data->id; ?></td>
                            <td><?php echo $data->connected_account_id; ?></td>
                            <td><?php echo $data->amount; ?></td>
                            <td><?php echo $data->order_id; ?></td>
                        </tr>
                        <?php }
                } else {
                     ?>
                    <tr>
                        <td colspan="4"><?php _e(
                            "No records found.",
                            "custom-stripe"
                        ); ?></td>
                    </tr>
                    <?php
                } ?>
            </tbody>
        </table>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php // Display pagination

    echo paginate_links([
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
	
    <?php
	
?>