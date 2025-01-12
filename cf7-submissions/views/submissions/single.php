<?php
use Codexpert\Plugin\Table;
use Codexpert\CF7_Submissions\Helper;
use Codexpert\CF7_Submissions\Database;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if(
	! isset( $_REQUEST['cf7sub_nonce'] )
	|| ! wp_verify_nonce( sanitize_text_field( $_REQUEST['cf7sub_nonce'] ), 'cf7-submissions' )
	|| ! isset( $_GET['view'] )
	|| '' == ( $submission_id = (int) sanitize_text_field( $_GET['view'] ) )
) return;

$db = new Database;

$cf7sub_nonce = wp_create_nonce( 'cf7-submissions' );

// mark it as seen
$db->update( 'submissions', [ 'seen' => 1 ], [ 'id' => $submission_id ] );

// fetch submission data
$submissions = $db->list( 'submissions', '*', [ 'id' => $submission_id ] );

if( count( $submissions ) <= 0 ) return;

$submission	= $submissions[0];
$fields		= $db->list( 'submission_data', 'field, value', [ 'submission_id' => $submission_id ] );
$_files		= $db->list( 'submission_files', 'id, field, name, path, type, size', [ 'submission_id' => $submission_id ] );

$files = [];
foreach ( $_files as $row ) {
	$files[ $row['field'] ][] = $row;
}

$format = get_option( 'links_updated_date_format' );
$admin_url = admin_url( 'admin.php' );
?>
<div id="cf7s-single-wrap">

	<div id="cf7s-left-col">

		<div id="cf7s-fields-wrap" class="cf7s-wrap postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php esc_html_e( 'Form Data', 'cf7-submissions' ); ?></h2>
			</div>
			<div class="inside">
				<?php if( ! empty( $fields ) ) : ?>
				<table class="cf7s-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Field', 'cf7-submissions' ); ?></th>
							<th><?php esc_html_e( 'Value', 'cf7-submissions' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $fields as $row ) {
							printf(
								'<tr>
									<td>%1$s</td>
									<td>%2$s</td>
								</tr>',
								esc_html( $row['field'] ),
								wpautop( esc_html( $row['value'] ) ),
							);
						}
						?>
					</tbody>
				</table>
				<?php else :
					printf( '<p>%1$s</p>', esc_html__( 'No data found!', 'cf7-submissions' ) );
				endif; ?>
			</div>
		</div><!-- #cf7s-fields-wrap -->

		<?php if( ! empty( $files ) ) : ?>
		<div id="cf7s-files-wrap" class="cf7s-wrap postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php esc_html_e( 'Uploaded Files', 'cf7-submissions' ); ?></h2>
			</div>
			<div class="inside">
				<table class="cf7s-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Field', 'cf7-submissions' ); ?></th>
							<th><?php esc_html_e( 'File Name', 'cf7-submissions' ); ?></th>
							<th><?php esc_html_e( 'Mime Type', 'cf7-submissions' ); ?></th>
							<th><?php esc_html_e( 'File Size', 'cf7-submissions' ); ?></th>
							<th><?php esc_html_e( 'Download', 'cf7-submissions' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $files as $field => $row ) {
						foreach ( $row as $file ) {
							printf(
								'<tr>
									<td>%1$s</td>
									<td><a href="%5$s">%2$s</a></td>
									<td>%3$s</td>
									<td>%4$s</td>
									<td><a href="%5$s" class="dashicons dashicons-download"></a></td>
								</tr>',
								esc_html( $field ),
								esc_html( $file['name'] ),
								esc_html( $file['type'] ),
								esc_html( cf7sub_size_format( $file['size'] ) ),
								esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'download' => $file['id'], 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) )
							);
						}
					}
					?>
					</tbody>
				</table>
			</div>
		</div><!-- #cf7s-files-wrap -->
		<?php endif; ?>

		<div id="cf7s-pagination">
			<p>
				<?php
				$prev_id = cf7sub_prev_submission( $submission_id );
				$next_id = cf7sub_next_submission( $submission_id );

				printf(
					'<a href="%1$s" class="button" %3$s>%2$s</a>',
					( ! $prev_id ? '#' : esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'view' => $prev_id, 'cf7sub_nonce' => $cf7sub_nonce ] ) ) ),
					esc_html__( 'Older Submission', 'cf7-submissions' ),
					( ! $prev_id ? 'disabled' : '' )
				);

				printf(
					'<a href="%1$s" class="button" %3$s>%2$s</a>',
					( ! $next_id ? '#' : esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'view' => $next_id, 'cf7sub_nonce' => $cf7sub_nonce ] ) ) ),
					esc_html__( 'Newer Submission', 'cf7-submissions' ),
					( ! $next_id ? 'disabled' : '' )
				);
				?>
			</p>
		</div>

	</div><!-- #cf7s-left-col -->

	<div id="cf7s-right-col">
		<div id="cf7s-actions-wrap" class="cf7s-wrap postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php esc_html_e( 'Meta Data', 'cf7-submissions' ); ?></h2>
			</div>
			<div class="inside">
				<p>
					<span class="dashicons dashicons-clock"></span>
					<label><?php esc_html_e( 'Submission ID:', 'cf7-submissions' ); ?></label>
					<?php echo esc_html( $submission_id ); ?>
				</p>
				<p>
					<span class="dashicons dashicons-media-document"></span>
					<label><?php esc_html_e( 'Form Name:', 'cf7-submissions' ); ?></label>
					<?php printf(
						'<a href="%2$s">%1$s</a>',
						esc_html( get_the_title( $submission['form_id'] ) ),
						esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'form' => $submission['form_id'], 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) )
					); ?>
				</p>
				<p>
					<span class="dashicons dashicons-admin-page"></span>
					<label><?php esc_html_e( 'Container Page:', 'cf7-submissions' ); ?></label>
					<?php printf(
						'<a href="%2$s">%1$s</a>',
						esc_html( get_the_title( $submission['post_id'] ) ),
						esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'container' => $submission['post_id'], 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) )
					); ?>
				</p>
				<p>
					<span class="dashicons dashicons-businessman"></span>
					<label><?php esc_html_e( 'User:', 'cf7-submissions' ); ?></label>
					<?php if( $submission['user_id'] == 0 ) {
						esc_html_e( 'Guest', 'cf7-submissions' );
					}
					else {
						printf(
							'<a href="%2$s">%1$s</a>',
							esc_html( get_userdata( $submission['user_id'] )->display_name ),
							esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'user' => $submission['user_id'], 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) )
						);
					} ?>
				</p>
				<p>
					<span class="dashicons dashicons-admin-site"></span>
					<label><?php esc_html_e( 'IP Address:', 'cf7-submissions' ); ?></label>
					<?php echo esc_html( $submission['ip'] ); ?>
				</p>
				<p>
					<span class="dashicons dashicons-clock"></span>
					<label><?php esc_html_e( 'Date &amp; Time:', 'cf7-submissions' ); ?></label>
					<?php echo esc_html( date_i18n( $format, $submission['time'] ) ); ?>
				</p>
			</div>
			<div class="handle-actions">
				<p id="cf7s-action-buttons">
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'unread' => $submission_id, 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) ); ?>" class="button button-small">
						<span class="dashicons dashicons-hidden"></span> <?php esc_html_e( 'Mark Unread', 'cf7-submissions' ); ?>
					</a>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'cf7-submissions', 'delete' => $submission_id, 'cf7sub_nonce' => $cf7sub_nonce ], $admin_url ) ); ?>" class="button button-small cf7s-delete">
						<span class="dashicons dashicons-no"></span> <?php esc_html_e( 'Delete Submission', 'cf7-submissions' ); ?>
					</a>
				</p>
			</div>
		</div>

		<?php
		$emails = [];
		foreach ( $fields as $row ) {
			if( is_email( $row['value'] ) ) {
				$emails[ $row['field'] ] = $row['value'];
			}
		}
		if( count( $emails ) > 0 ) : ?>
		
		<div id="cf7s-contact-wrap" class="cf7s-wrap postbox">
			<form id="cf7s-contact-form">
				<?php wp_nonce_field( 'cf7-submissions', 'cf7sub_nonce' ); ?>
				<input type="hidden" name="action" value="cf7s-contact">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Contact Sender', 'cf7-submissions' ); ?></h2>
				</div>
				<div class="inside">
					<p>
						<label><?php esc_html_e( 'Recipient(s)', 'cf7-submissions' ); ?></label>
						<select name="to[]" class="cf7s-contact-to chosen" multiple>
							<?php
							foreach ( $emails as $field => $email ) {
								printf(
									'<option value="%2$s">%1$s &lt;%2$s&gt;</option>',
									esc_attr( $field ),
									esc_html( $email )
								);
							}
							?>
						</select>
						<small><?php esc_html_e( 'Based on the email address(es) collected in the form submission', 'cf7-submissions' ) ?></small>
					</p>
					<p>
						<label><?php esc_html_e( 'Subject', 'cf7-submissions' ); ?></label>
						<input type="text" name="subject" placeholder="<?php esc_html_e( 'Mail subject line', 'cf7-submissions' ); ?>" />
					</p>
					<p>
						<label><?php esc_html_e( 'Message', 'cf7-submissions' ); ?></label>
						<textarea name="message" placeholder="<?php esc_html_e( 'Main message body', 'cf7-submissions' ); ?>" rows="5"></textarea>
					</p>
				</div>
				<div class="handle-contact">
					<p id="cf7s-contact-buttons">
						<span id="cf7s-contact-msg"></span>
						<button type="submit" class="button button-small button-primary cf7s-send">
							<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Send Email', 'cf7-submissions' ); ?>
						</button>
					</p>
				</div>
			</form>
		</div><!-- #cf7s-contact-wrap -->
		
		<?php endif; ?>

	</div><!-- #cf7s-right-col -->
</div>