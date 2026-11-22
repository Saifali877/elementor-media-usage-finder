<?php
// Run with: php tests/acf-scan.php
define( 'ABSPATH', __DIR__ );
function add_action() {}
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
require dirname( __DIR__ ) . '/elementor-media-usage-finder.php';

$fields = array(
	'field_image'   => array( 'type' => 'image' ),
	'field_gallery' => array( 'type' => 'gallery' ),
	'field_text'    => array( 'type' => 'text' ),
);
function acf_get_field( $key ) {
	global $fields;
	return isset( $fields[ $key ] ) ? $fields[ $key ] : false;
}

$meta = array(
	7 => array(
		'portrait'  => 42,
		'_portrait' => 'field_image',
		'photos'    => array( 42, 43 ),
		'_photos'   => 'field_gallery',
		'plain'     => 42,
		'_plain'    => 'field_text',
		'other'     => 42,
		'_missing'  => 'field_image',
	),
	8 => array(
		'team'                  => 2,
		'_team'                 => 'field_repeater',
		'team_0_portrait'       => '42',
		'_team_0_portrait'      => 'field_image',
		'team_1_gallery'        => array( 41, 42, 42 ),
		'_team_1_gallery'       => 'field_gallery',
		'team_1_numeric_text'   => '42',
		'_team_1_numeric_text'  => 'field_text',
	),
	9 => array( 'unrelated_count' => 42 ),
);
function get_post_meta( $id, $key = '', $single = false ) {
	global $meta;
	if ( '' === $key ) {
		$result = array();
		foreach ( $meta[ $id ] as $name => $value ) {
			$result[ $name ] = array( $value );
		}
		return $result;
	}
	return isset( $meta[ $id ][ $key ] ) ? $meta[ $id ][ $key ] : '';
}
function get_post_types() { return array( 'page' => 'page', 'attachment' => 'attachment' ); }
function post_type_exists( $type ) { return false; }
function get_post( $id ) { return (object) array( 'post_type' => 'page' ); }
function get_post_type_object( $type ) { return (object) array( 'labels' => (object) array( 'singular_name' => 'Page' ) ); }
function get_edit_post_link( $id ) { return '/edit/' . $id; }
function get_the_title( $id ) { return 'Page ' . $id; }
function get_post_status( $id ) { return 'publish'; }
function esc_url_raw( $url ) { return $url; }
class WP_Query {
	public $posts;
	public function __construct( $args ) {
		if ( isset( $args['meta_query'] ) || 50 !== $args['posts_per_page'] ) {
			throw new Exception( 'ACF scan must include posts without Elementor metadata in bounded batches.' );
		}
		$this->posts = array( 7, 8, 9 );
	}
}

$batch = EMUF_Scanner::scan_batch( 42, 1 );
if ( 3 !== $batch['scanned'] || 2 !== count( $batch['results'] ) || 2 !== $batch['results'][0]['count'] || 3 !== $batch['results'][1]['count'] ) {
	fwrite( STDERR, "ACF image/gallery/repeater scan failed.\n" );
	exit( 1 );
}
if ( 0 !== EMUF_Scanner::count_acf_references( 7, 99 ) || 0 !== EMUF_Scanner::count_acf_references( 9, 42 ) ) {
	fwrite( STDERR, "ACF false match check failed.\n" );
	exit( 1 );
}
echo "ACF scan cases passed.\n";
