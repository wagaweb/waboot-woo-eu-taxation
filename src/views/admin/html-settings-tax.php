<?php foreach ($rates as $name => $values): ?>
	<h3><?php printf( __( '"%s" Tax Rates', 'woocommerce' ), $name != "" ? __( $name, 'woocommerce' ) : __( 'Standard', 'woocommerce' ) ); ?></h3>
	<table class="wc_tax_rates wc_input_table sortable widefat">
	<thead>
	<tr>
		<th width="8%"><?php _e( 'Country&nbsp;Code', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'State&nbsp;Code', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'ZIP/Postcode', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'City', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'Rate&nbsp;%', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'Tax&nbsp;Name', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'Priority', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'Compound', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'Shipping', 'woocommerce' ); ?></th>
	</tr>
	</thead>
	<tfoot>
	</tfoot>
	<tbody id="rates">
	<?php foreach($values as $value): ?>
		<tr>
			<th colspan="10" style="text-align: center;"><?php esc_html_e( 'Loading&hellip;', 'woocommerce' ); ?></th>
		</tr>
	<?php endforeach; ?>
	</tbody>
	</table>
<?php endforeach; ?>
