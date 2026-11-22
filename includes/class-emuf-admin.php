<?php
/** Admin-only interface and authenticated AJAX endpoint. */

defined( 'ABSPATH' ) || exit;

final class EMUF_Admin {
	const SLUG = 'elementor-media-usage-finder';
	const AJAX = 'emuf_scan_batch';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_' . self::AJAX, array( __CLASS__, 'scan_batch' ) );
		add_filter( 'media_row_actions', array( __CLASS__, 'media_row_action' ), 10, 2 );
	}

	public static function register_page() {
		add_management_page(
			__( 'Elementor Media Finder', 'elementor-media-usage-finder' ),
			__( 'Elementor Media Finder', 'elementor-media-usage-finder' ),
			'manage_options', self::SLUG, array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( 'tools_page_' . self::SLUG !== $hook || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'emuf-admin', plugins_url( 'assets/css/admin.css', EMUF_FILE ), array(), EMUF_VERSION );
		wp_enqueue_script( 'emuf-admin', plugins_url( 'assets/js/admin.js', EMUF_FILE ), array( 'jquery', 'media-views' ), EMUF_VERSION, true );
		wp_localize_script( 'emuf-admin', 'EMUF', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::AJAX ),
			'labels'  => array(
				'choose'   => __( 'Choose an image', 'elementor-media-usage-finder' ),
				'select'   => __( 'Use this image', 'elementor-media-usage-finder' ),
				'working'  => __( 'Scanning saved Elementor content…', 'elementor-media-usage-finder' ),
				'finished' => __( 'Scan complete', 'elementor-media-usage-finder' ),
				'checked'  => __( 'items checked', 'elementor-media-usage-finder' ),
				'found'    => __( 'posts, pages or templates found', 'elementor-media-usage-finder' ),
				'none'     => __( 'No saved Elementor or ACF references found for this image.', 'elementor-media-usage-finder' ),
				'error'    => __( 'The scan stopped. Please try again.', 'elementor-media-usage-finder' ),
				'cancelled' => __( 'Scan cancelled.', 'elementor-media-usage-finder' ),
				'image'    => __( 'Image', 'elementor-media-usage-finder' ),
			),
		) );
	}

	public static function media_row_action( $actions, $post ) {
		if ( current_user_can( 'manage_options' ) && wp_attachment_is_image( $post->ID ) ) {
			$actions['emuf_find'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'tools.php?page=' . self::SLUG . '&attachment_id=' . absint( $post->ID ) ) ),
				esc_html__( 'Find Elementor usage', 'elementor-media-usage-finder' )
			);
		}
		return $actions;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot access this page.', 'elementor-media-usage-finder' ) );
		}
		$id = isset( $_GET['attachment_id'] ) ? absint( wp_unslash( $_GET['attachment_id'] ) ) : 0;
		if ( $id && ! wp_attachment_is_image( $id ) ) {
			$id = 0;
		}
		$image_url = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
		?>
		<div class="wrap emuf" id="emuf-app" data-attachment-id="<?php echo esc_attr( $id ); ?>">
			<header class="emuf__head"><div><p class="emuf__eyebrow"><?php esc_html_e( 'Media Library / Elementor', 'elementor-media-usage-finder' ); ?></p><h1><?php esc_html_e( 'Media Usage Finder', 'elementor-media-usage-finder' ); ?></h1><p><?php esc_html_e( 'Find where an image is saved in Elementor content or ACF image fields.', 'elementor-media-usage-finder' ); ?></p></div><span class="emuf__version">v<?php echo esc_html( EMUF_VERSION ); ?></span></header>
			<?php if ( ! did_action( 'elementor/loaded' ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Elementor is inactive. Saved pages may still be found, but Elementor templates need Elementor active to appear in the scan.', 'elementor-media-usage-finder' ); ?></p></div><?php endif; ?>
			<section class="emuf__panel" aria-labelledby="emuf-image-heading">
				<h2 id="emuf-image-heading"><?php esc_html_e( 'Choose an image', 'elementor-media-usage-finder' ); ?></h2>
				<p><?php esc_html_e( 'The scan reads saved Elementor and ACF data. It does not change any content or media.', 'elementor-media-usage-finder' ); ?></p>
				<div class="emuf__picker"><div class="emuf__preview"><img id="emuf-preview" src="<?php echo esc_url( $image_url ? $image_url : '' ); ?>" alt="" <?php echo $image_url ? '' : 'hidden'; ?> /><span id="emuf-placeholder" <?php echo $image_url ? 'hidden' : ''; ?>><?php esc_html_e( 'No image selected', 'elementor-media-usage-finder' ); ?></span></div>
					<div class="emuf__picker-actions"><strong id="emuf-image-name"><?php echo $id ? esc_html( get_the_title( $id ) ) : esc_html__( 'Select an image to begin', 'elementor-media-usage-finder' ); ?></strong>
						<div class="emuf__buttons"><button type="button" class="button" id="emuf-choose"><?php esc_html_e( 'Choose image', 'elementor-media-usage-finder' ); ?></button><button type="button" class="button button-primary" id="emuf-scan" <?php disabled( ! $id ); ?>><?php esc_html_e( 'Find usage', 'elementor-media-usage-finder' ); ?></button><button type="button" class="button" id="emuf-cancel" hidden><?php esc_html_e( 'Cancel', 'elementor-media-usage-finder' ); ?></button></div>
					</div></div>
			</section>
			<section class="emuf__panel emuf__output" aria-labelledby="emuf-results-heading"><div class="emuf__result-head"><div><h2 id="emuf-results-heading"><?php esc_html_e( 'Results', 'elementor-media-usage-finder' ); ?></h2><p id="emuf-status" role="status" aria-live="polite"><?php esc_html_e( 'Choose an image and run a scan.', 'elementor-media-usage-finder' ); ?></p></div><span id="emuf-result-count" class="emuf__count" hidden></span></div>
				<div id="emuf-progress" class="emuf__progress" role="progressbar" aria-label="<?php esc_attr_e( 'Scan in progress', 'elementor-media-usage-finder' ); ?>" hidden><span></span></div>
				<div id="emuf-empty" class="emuf__empty"><?php esc_html_e( 'Results will appear here after the scan.', 'elementor-media-usage-finder' ); ?></div>
				<div id="emuf-results" class="emuf__table-wrap" hidden><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Page or template', 'elementor-media-usage-finder' ); ?></th><th><?php esc_html_e( 'Type', 'elementor-media-usage-finder' ); ?></th><th><?php esc_html_e( 'Status', 'elementor-media-usage-finder' ); ?></th><th><?php esc_html_e( 'References', 'elementor-media-usage-finder' ); ?></th></tr></thead><tbody id="emuf-rows"></tbody></table></div>
			</section>
			<p class="emuf__footnote"><?php esc_html_e( 'Scope: saved Elementor references and ACF image, gallery and repeater image fields when ACF is active. CSS, shortcodes, theme files and sitewide options are outside this scan. A blank result does not mean an image is safe to delete.', 'elementor-media-usage-finder' ); ?></p>
		</div>
		<?php
	}

	public static function scan_batch() {
		check_ajax_referer( self::AJAX, 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'elementor-media-usage-finder' ) ), 403 );
		}
		$id   = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;
		$page = isset( $_POST['page_number'] ) ? absint( wp_unslash( $_POST['page_number'] ) ) : 0;
		if ( ! $id || ! wp_attachment_is_image( $id ) || $page < 1 || $page > 10000 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid image or page.', 'elementor-media-usage-finder' ) ), 400 );
		}
		wp_send_json_success( EMUF_Scanner::scan_batch( $id, $page ) );
	}
}
