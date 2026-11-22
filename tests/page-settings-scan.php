<?php
// Run with: php tests/page-settings-scan.php
define( 'ABSPATH', __DIR__ );
function add_action() {}
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
require dirname( __DIR__ ) . '/elementor-media-usage-finder.php';

function get_post_types() { return array( 'page' => 'page', 'attachment' => 'attachment' ); }
function post_type_exists( $type ) { return false; }
function get_post_meta( $id, $key ) {
	$meta = array(
		7 => array( '_elementor_page_settings' => array( 'background_image' => array( 'id' => 42, 'url' => 'https://example.test/42.jpg' ) ) ),
		8 => array( '_elementor_data' => '[{"settings":{"image":{"id":42,"url":"https://example.test/42.jpg"}}}]' ),
	);
	return isset( $meta[ $id ][ $key ] ) ? $meta[ $id ][ $key ] : '';
}
function get_post( $id ) { return (object) array( 'post_type' => 'page' ); }
function get_post_type_object( $type ) { return (object) array( 'labels' => (object) array( 'singular_name' => 'Page' ) ); }
function get_edit_post_link( $id ) { return '/edit/' . $id; }
function get_the_title( $id ) { return 'Page ' . $id; }
function get_post_status( $id ) { return 'publish'; }
function esc_url_raw( $url ) { return $url; }
class WP_Query {
	public $posts;
	public function __construct( $args ) {
		$meta_query = $args['meta_query'];
		if (
			'OR' !== $meta_query['relation'] ||
			'_elementor_data' !== $meta_query[0]['key'] ||
			'_elementor_page_settings' !== $meta_query[1]['key'] ||
			'EXISTS' !== $meta_query[0]['compare'] ||
			'EXISTS' !== $meta_query[1]['compare']
		) {
			throw new Exception( 'The query must include layouts and standalone page settings.' );
		}
		$this->posts = array( 7, 8 );
	}
}

$batch = EMUF_Scanner::scan_batch( 42, 1 );
if ( 2 !== $batch['scanned'] || 2 !== count( $batch['results'] ) || 1 !== $batch['results'][0]['count'] || 1 !== $batch['results'][1]['count'] ) {
	fwrite( STDERR, "Page settings scan failed.\n" );
	exit( 1 );
}
echo "Page settings scan passed.\n";
