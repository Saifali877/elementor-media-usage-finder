<?php
/** Read-only Elementor document scan. */

defined( 'ABSPATH' ) || exit;

final class EMUF_Scanner {
	const BATCH_SIZE = 50;

	public static function scan_batch( $attachment_id, $page ) {
		$types = get_post_types( array( 'show_ui' => true ), 'names' );
		unset( $types['attachment'] );
		if ( post_type_exists( 'elementor_library' ) ) {
			$types['elementor_library'] = 'elementor_library';
		}

		$query = new WP_Query(
			array(
				'post_type'              => array_values( $types ),
				'post_status'            => array( 'publish', 'private', 'draft', 'pending', 'future' ),
				'posts_per_page'         => self::BATCH_SIZE,
				'paged'                  => $page,
				'fields'                 => 'ids',
				'orderby'               => 'ID',
				'order'                 => 'ASC',
				'no_found_rows'         => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'            => array(
					'relation' => 'OR',
					array( 'key' => '_elementor_data', 'compare' => 'EXISTS' ),
					array( 'key' => '_elementor_page_settings', 'compare' => 'EXISTS' ),
				),
			)
		);

		$results = array();
		foreach ( $query->posts as $post_id ) {
			$raw  = get_post_meta( $post_id, '_elementor_data', true );
			$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
			$page_settings = get_post_meta( $post_id, '_elementor_page_settings', true );
			$count = self::count_references( $data, $attachment_id ) + self::count_references( $page_settings, $attachment_id );
			if ( ! $count ) {
				continue;
			}
			$post = get_post( $post_id );
			$type = $post ? get_post_type_object( $post->post_type ) : null;
			$edit = get_edit_post_link( $post_id, 'raw' );
			$results[] = array(
				'title'  => get_the_title( $post_id ) ?: __( '(Untitled)', 'elementor-media-usage-finder' ),
				'url'    => $edit ? esc_url_raw( $edit ) : '',
				'type'   => $type ? $type->labels->singular_name : __( 'Content', 'elementor-media-usage-finder' ),
				'status' => get_post_status( $post_id ),
				'count'  => $count,
			);
		}
		return array(
			'results' => $results,
			'scanned' => count( $query->posts ),
			'next'    => count( $query->posts ) === self::BATCH_SIZE ? $page + 1 : null,
		);
	}

	/** Match saved Elementor media objects, including nested galleries and backgrounds. */
	public static function count_references( $node, $attachment_id ) {
		if ( ! is_array( $node ) ) {
			return 0;
		}
		$count = 0;
		if (
			isset( $node['id'], $node['url'] ) &&
			is_scalar( $node['id'] ) &&
			is_string( $node['url'] ) &&
			(string) $attachment_id === (string) $node['id']
		) {
			++$count;
		}
		foreach ( $node as $child ) {
			if ( is_array( $child ) ) {
				$count += self::count_references( $child, $attachment_id );
			}
		}
		return $count;
	}
}
