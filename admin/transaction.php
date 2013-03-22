<?php
global $wpdb;

$total_income = $wpdb->get_var( "SELECT SUM(cost) FROM {$wpdb->prefix}wpuf_transaction WHERE status = 'completed'" );
$month_income = $wpdb->get_var( "SELECT SUM(cost) FROM {$wpdb->prefix}wpuf_transaction WHERE YEAR(`created`) = YEAR(NOW()) AND MONTH(`created`) = MONTH(NOW()) AND status = 'completed'" );
$transactions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpuf_transaction ORDER BY `created` DESC LIMIT 0, 60", OBJECT );
?>
<div class="wrap">
    <?php screen_icon( 'options-general' ); ?>
    <h2><?php _e( 'WP User Frontend: Payments Received', 'wpuf' ); ?></h2>

    <ul>
        <li>
            <strong><?php _e( 'Total Income:', 'wpuf' ); ?></strong> <?php echo get_option( 'wpuf_sub_currency_sym' ) . $total_income; ?><br />
        </li>
        <li>
            <strong><?php _e( 'This Month:', 'wpuf' ); ?></strong> <?php echo get_option( 'wpuf_sub_currency_sym' ) . $month_income; ?>
        </li>
    </ul>

    <table class="widefat meta" style="margin-top: 20px;">
        <thead>
            <tr>
                <th scope="col"><?php _e( 'ID', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'User ID', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Status', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Cost', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Post', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Pack ID', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Payer', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Email', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Type', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Transaction ID', 'wpuf' ); ?></th>
                <th scope="col"><?php _e( 'Created', 'wpuf' ); ?></th>
            </tr>
        </thead>
        <?php
        if ( $transactions ) {
            $count = 0;
            foreach ($transactions as $row) {
                ?>
                <tr valign="top" <?php echo ( ($count % 2) == 0) ? 'class="alternate"' : ''; ?>>
                    <td><?php echo stripslashes( htmlspecialchars( $row->id ) ); ?></td>
                    <td><?php echo stripslashes( htmlspecialchars( $row->user_id ) ); ?></td>
                    <td><?php echo stripslashes( htmlspecialchars( $row->status ) ); ?></td>
                    <td><?php echo stripslashes( htmlspecialchars( $row->cost ) ); ?></td>
                    <td>
                        <?php
                        if ( $row->post_id ) {
                            $post = WPUF_Subscription::post_by_orderid( $row->post_id );
                            if ( $post) {
                                printf( '<a href="%s">%s</a>', get_permalink( $post->ID ), $post->post_title );
                            }
                        } else {
                            echo $row->post_id;
                        }
                        ?>
                    </td>
                    <td><?php echo stripslashes( htmlspecialchars( $row->pack_id ) ); ?></td>
                    <td><?php echo $row->payer_first_name . ' ' . $row->payer_last_name; ?></td>
                    <td><?php echo stripslashes( htmlspecialchars( $row->payer_email ) ); ?></td>
                    <td><?php echo stripslashes( htmlspecialchars( $row->payment_type ) ); ?></td>
                    <td><?php echo stripslashes( htmlspecialchars( $row->transaction_id ) ); ?></td>
                    <td><?php echo stripslashes( htmlspecialchars( $row->created ) ); ?></td>

                </tr>
                <?php
                $count++;
            }
            ?>
        <?php } else { ?>
            <tr>
                <td colspan="11"><?php _e( 'Nothing Found', 'wpuf' ); ?></td>
            </tr>
        <?php } ?>

    </table>
</div>
