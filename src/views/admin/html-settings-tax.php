<?php foreach ($rates as $name => $values): ?>
	<h3><?php printf( __( '"%s" Tax Rates', 'woocommerce' ), $name != "" ? __( $name, 'woocommerce' ) : __( 'Standard', 'woocommerce' ) ); ?></h3>
	<table class="wc_tax_rates wc_input_table sortable widefat">
	<thead>
	<tr>
		<!-- <th width="8%"><?php _e( 'Country&nbsp;Code', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'State&nbsp;Code', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'ZIP/Postcode', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'City', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'Rate&nbsp;%', 'woocommerce' ); ?></th> -->
		<th width="8%"><?php _e( 'Tax&nbsp;Name', 'woocommerce' ); ?></th>
		<!-- <th width="8%"><?php _e( 'Priority', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'Compound', 'woocommerce' ); ?></th>
		<th width="8%"><?php _e( 'Shipping', 'woocommerce' ); ?></th> -->
		<th width="8%"><?php _ex( 'Applies to', 'Admin table', $textdomain ); ?></th>
	</tr>
	</thead>
	<tfoot>
	</tfoot>
	<tbody id="rates">
	<?php if(!isset($values) || empty($values) || !is_array($values)) : ?>
		<th colspan="10" style="text-align:center"><?php esc_html_e( 'No Matching Tax Rates Found.', 'woocommerce' ); ?></th>
	<?php else: ?>
	<?php foreach($values as $value): ?>
			<tr>
				<!-- <td><?php if($value->tax_rate_country == "") echo "*"; else echo $value->tax_rate_country; ?></td>
				<td><?php if($value->tax_rate_state == "") echo "*"; else echo $value->tax_rate_state; ?></td>
				<td><?php if(!isset($value->postcode)) echo "*"; else echo implode(";",$value->postcode); ?></td>
				<td><?php if(!isset($value->city)) echo "*"; else echo implode(";",$value->city); ?></td>
				<td><?php echo $value->tax_rate; ?></td> -->
				<td><?php echo $value->tax_rate_name; ?></td>
				<!-- <td><?php echo $value->tax_rate_priority; ?></td>
				<td><?php if($value->tax_rate_compound == "0") _e("Yes"); else _e("No"); ?></td>
				<td><?php if($value->tax_rate_shipping == "0") _e("Yes"); else _e("No"); ?></td> -->
				<td>
					<label for="apply_to_customer_type[<?php echo $value->tax_rate_id ?>]"></label><select name="apply_to_customer_type[<?php echo $value->tax_rate_id ?>]" id="apply_to_customer_type">
						<?php foreach($select_options as $opt_name => $opt_label): ?>
							<?php $selected = array_key_exists($value->tax_rate_id,$settings) && $settings[$value->tax_rate_id] == $opt_name; ?>
							<option value="<?php echo $opt_name ?>" <?php if($selected) echo "selected"; ?>><?php echo $opt_label; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
	</table>
<?php endforeach; ?>
