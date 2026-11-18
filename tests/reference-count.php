<?php
// Run with: php tests/reference-count.php
define( 'ABSPATH', __DIR__ );
function add_action() {}
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
require dirname( __DIR__ ) . '/elementor-media-usage-finder.php';

$document = array(
	array(
		'elType'  => 'widget',
		'settings' => array(
			'image'       => array( 'id' => 42, 'url' => 'https://example.test/uploads/photo.jpg' ),
			'link'        => array( 'id' => 42 ),
			'background'  => array( 'image' => array( 'id' => '42', 'url' => 'https://example.test/uploads/photo.jpg' ) ),
			'gallery'     => array( array( 'id' => 43, 'url' => 'https://example.test/uploads/other.jpg' ) ),
		),
	),
);

$cases = array(
	array( $document, 42, 2 ),
	array( $document, 43, 1 ),
	array( $document, 99, 0 ),
	array( array( 'id' => 42, 'url' => 123 ), 42, 0 ),
	array( array( 'id' => 42, 'url' => '' ), 42, 1 ),
	array( array( 'id' => 42, 'link' => 'https://example.test' ), 42, 0 ),
	array( 'invalid', 42, 0 ),
);

foreach ( $cases as $index => $case ) {
	$actual = EMUF_Scanner::count_references( $case[0], $case[1] );
	if ( $case[2] !== $actual ) {
		fwrite( STDERR, 'Case ' . $index . ': expected ' . $case[2] . ', got ' . $actual . PHP_EOL );
		exit( 1 );
	}
}
echo "Reference count cases passed.\n";
