<?php
/**
 * RayPay gateway settings.
 *
 * @package RCP_RayPay
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


function rcp_raypay_settings( $rcp_options ) {

	?>
	<hr>

	<table class="form-table">
		<tr valign="top">
			<th colspan="2">
				<h3><?php _e( 'RayPay gateway settings', 'raypay-for-rcp' ); ?></h3>
			</th>
		</tr>
		<tr valign="top">
			<th>
				<label for="rcp_settings[raypay_user_id]" id="raypayUserID"><?php _e( 'User ID', 'raypay-for-rcp' ); ?></label>
			</th>
			<td>
				<input class="regular-text" name="rcp_settings[raypay_user_id]" id="raypayUserID" value="<?php echo isset( $rcp_options['raypay_user_id'] ) ? $rcp_options['raypay_user_id'] : ''; ?>">
				<p class="description"><?php _e( 'You can receive your User ID by going to your RayPay panel', 'raypay-for-rcp' ); ?></p>
			</td>
		</tr>
        <tr valign="top">
            <th>
                <label for="rcp_settings[raypay_marketing_id]" id="raypayAcceptorCode"><?php _e( 'Marketing ID', 'raypay-for-rcp' ); ?></label>
            </th>
            <td>
                <input class="regular-text" name="rcp_settings[raypay_marketing_id]" id="raypayMarketingID" value="<?php echo isset( $rcp_options['raypay_marketing_id'] ) ? $rcp_options['raypay_marketing_id'] : ''; ?>">
                <p class="description"><?php _e( 'You can receive your Marketing ID by going to your RayPay panel', 'raypay-for-rcp' ); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th>
                <label for="rcp_settings[raypay_sandbox]" id="raypaySandbox"><?php _e( 'Sandbox mode', 'raypay-for-rcp' ); ?></label>
            </th>
            <td>
                <p class="description">
                    <select id="rcp_settings[raypay_sandbox]" name="rcp_settings[raypay_sandbox]">
                        <option value="yes" <?php selected('yes', isset($rcp_options['raypay_sandbox']) ? $rcp_options['raypay_sandbox'] : '');?>><?php _e('Yes', 'raypay-for-rcp');?></option>
                        <option value="no" <?php selected('no', isset($rcp_options['raypay_sandbox']) ? $rcp_options['raypay_sandbox'] : '');?>><?php _e('No', 'raypay-for-rcp');?></option>
                    </select>
                    <?php _e( 'If you check this option, the gateway will work in Test (Sandbox) mode.', 'raypay-for-rcp' ); ?>
                </p>
            </td>
        </tr>
		<tr valign="top">
			<th>
				<label for="rcp_settings[raypay_symbol]" id="raypaySymbol"><?php _e( 'Show currency?', 'raypay-for-rcp' ); ?></label>
			</th>
			<td>
				<p class="description">
					<select id="rcp_settings[raypay_symbol]" name="rcp_settings[raypay_symbol]">
						<option value="yes" <?php selected('yes', isset($rcp_options['raypay_symbol']) ? $rcp_options['raypay_symbol'] : '');?>><?php _e('Yes', 'raypay-for-rcp');?></option>
						<option value="no" <?php selected('no', isset($rcp_options['raypay_symbol']) ? $rcp_options['raypay_symbol'] : '');?>><?php _e('No', 'raypay-for-rcp');?></option>
					</select>
				</p>
			</td>
		</tr>
	</table>
	<?php
}

add_action('rcp_payments_settings', 'rcp_raypay_settings');
