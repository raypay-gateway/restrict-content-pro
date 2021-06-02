<?php
/**
 * Filters.
 *
 * @package RCP_RayPay
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register RayPay payment gateway.
 *
 * @param array $gateways
 * @return array
 */
function rcp_raypay_register_gateway( $gateways ) {

	$gateways['raypay']	= [
		'label'			=> __( 'RayPay Payment Gateway', 'raypay-for-rcp' ),
		'admin_label'	=> __( 'RayPay Payment Gateway', 'raypay-for-rcp' ),
	];

	return $gateways;

}

add_filter( 'rcp_payment_gateways', 'rcp_raypay_register_gateway' );

/**
 * Add IRR and IRT currencies to RCP.
 *
 * @param array $currencies
 * @return array
 */
function rcp_raypay_currencies( $currencies ) {
	unset( $currencies['RIAL'], $currencies['IRR'], $currencies['IRT'] );

	return array_merge( array(
		'IRT'		=> __( 'تومان ایران (تومان)', 'raypay-for-rcp' ),
		'IRR'		=> __( 'ریال ایران (&#65020;)', 'raypay-for-rcp' ),
	), $currencies );
}

add_filter( 'rcp_currencies', 'rcp_raypay_currencies' );

/**
 * Save old roles of a user when updating it.
 *
 * @param WP_User $user
 * @return WP_User
 */
function rcp_raypay_registration_data( $user ) {
//	$old_subscription_id = get_user_meta( $user['id'], 'rcp_subscription_level', true );
//	if ( ! empty( $old_subscription_id ) ) {
//		update_user_meta( $user['id'], 'rcp_subscription_level_old', $old_subscription_id );
//	}
//
//	$user_info     = get_userdata( $user['id'] );
//	$old_user_role = implode( ', ', $user_info->roles );
//	if ( ! empty( $old_user_role ) ) {
//		update_user_meta( $user['id'], 'rcp_user_role_old', $old_user_role );
//	}

	return $user;
}

add_filter( 'rcp_user_registration_data', 'rcp_raypay_registration_data' );

/**
 * Sets decimal to zero.
 *
 * @param bool $is_zero_decimal_currency
 *
 * @return bool
 */
function rcp_raypay_is_zero_decimal_currency( $is_zero_decimal_currency = FALSE ) {
	$currency = rcp_get_currency();
	if ( in_array($currency, ['IRT', 'IRR']) ) {
		return TRUE;
	}

	return $is_zero_decimal_currency;
}

add_filter( 'rcp_is_zero_decimal_currency', 'rcp_raypay_is_zero_decimal_currency' );

/**
 * Format IRT currency Symbol.
 *
 * @return string
 */
function rcp_raypay_irr_symbol() {
	global $rcp_options;
	return empty($rcp_options['raypay_symbol']) || $rcp_options['raypay_symbol'] == 'yes' ? ' &#65020; ' : '';
}

add_filter( 'rcp_irr_symbol', 'rcp_raypay_irr_symbol' );

/**
 * Format IRT currency Symbol.
 *
 * @return string
 */
function rcp_raypay_irt_symbol() {
	global $rcp_options;
	return empty($rcp_options['raypay_symbol']) || $rcp_options['raypay_symbol'] == 'yes' ? ' تومان ' : '';
}

add_filter( 'rcp_irt_symbol', 'rcp_raypay_irt_symbol' );
