<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CX_SITE_HEALTH_NOTICE_LOADED' ) ) {
	return;
}
define( 'CX_SITE_HEALTH_NOTICE_LOADED', true );

add_action( 'admin_notices', 'cx_site_health_notice' );
add_action( 'wp_ajax_cx_site_health_dismiss_notice', 'cx_site_health_dismiss_notice' );

function cx_site_health_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$dismissed_time = get_user_meta( get_current_user_id(), 'cx_site_health_notice_dismissed_at', true );
	if ( $dismissed_time ) {
		$two_weeks = 14 * DAY_IN_SECONDS;
		if ( time() - $dismissed_time < $two_weeks ) {
			return;
		}
	}

	$health_status = get_transient( 'health-check-site-status-result' );

	if ( false === $health_status ) {
		return;
	}

	$counts = json_decode( $health_status, true );

	if ( ! is_array( $counts ) ) {
		return;
	}

	$critical    = isset( $counts['critical'] ) ? absint( $counts['critical'] ) : 0;
	$recommended = isset( $counts['recommended'] ) ? absint( $counts['recommended'] ) : 0;

	if ( 0 === $critical && 0 === $recommended ) {
		return;
	}

	$message = '';
	$notice_class = 'notice-warning';

	if ( $critical > 0 ) {
		$message = sprintf(
			_n(
				'Your site has %d critical issue that requires immediate attention.',
				'Your site has %d critical issues that require immediate attention.',
				$critical,
				'audit-notice'
			),
			$critical
		);
		$notice_class = 'notice-error';
	} elseif ( $recommended > 0 ) {
		$message = sprintf(
			_n(
				'Your site has %d health recommendation to improve performance.',
				'Your site has %d health recommendations to improve performance.',
				$recommended,
				'audit-notice'
			),
			$recommended
		);
		$notice_class = 'notice-warning';
	}

	$site_health_url = admin_url( 'site-health.php' );
	$site_url        = rawurlencode( home_url() );
	$cta_url         = 'https://codexpert.io/services/?utm_source=plugin&utm_medium=admin_notice&utm_campaign=site_health_audit&utm_content=' . $site_url . '#cx-service-site-audit';

	?>
	<style>
	.cx-health-notice {
		display: flex;
		justify-content: space-between;
		align-items: center;
		align-content: center;
	}
	#health-check-issues-critical {
		border: 2px solid #d63638;
		padding: 10px;
	}
	#health-check-issues-recommended {
		border: 2px solid #dba617;
		padding: 10px;
	}
	.site-health-issues-wrapper h3 {
	    margin-top: 0;
	}
	</style>
	<div id="cx-health-notice" class="notice cx-health-notice <?php echo esc_attr( $notice_class ); ?> is-dismissible">
		<p>
			<?php echo esc_html( $message ); ?>
			— <a href="<?php echo esc_url( $site_health_url ); ?>"><?php esc_html_e( 'View Site Health', 'audit-notice' ); ?></a>
		</p>
		<p>
			<a href="<?php echo esc_url( $cta_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Get Free Audit', 'audit-notice' ); ?>
			</a>
		</p>
	</div>
	<script>
	jQuery(document).on('click', '#cx-health-notice .notice-dismiss', function() {
		jQuery.post(ajaxurl, {
			action: 'cx_site_health_dismiss_notice',
			nonce: '<?php echo wp_create_nonce( 'cx_site_health_dismiss_nonce' ); ?>'
		});
	});
	</script>
	<?php
}

function cx_site_health_dismiss_notice() {
	check_ajax_referer( 'cx_site_health_dismiss_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	update_user_meta( get_current_user_id(), 'cx_site_health_notice_dismissed_at', time() );

	wp_send_json_success();
}