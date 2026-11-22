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

		$args = array(
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
		);
		// ACF fields may live on posts that have no Elementor document data.
		if ( ! function_exists( 'acf_get_field' ) ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array( 'key' => '_elementor_data', 'compare' => 'EXISTS' ),
				array( 'key' => '_elementor_page_settings', 'compare' => 'EXISTS' ),
			);
		}
		$query = new WP_Query( $args );

		$results = array();
		foreach ( $query->posts as $post_id ) {
			$raw  = get_post_meta( $post_id, '_elementor_data', true );
			$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
			$page_settings = get_post_meta( $post_id, '_elementor_page_settings', true );
			$count = self::count_references( $data, $attachment_id ) + self::count_references( $page_settings, $attachment_id );
			if ( function_exists( 'acf_get_field' ) ) {
				$count += self::count_acf_references( $post_id, $attachment_id );
			}
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

	/** Count saved ACF image and gallery IDs, including fields inside repeater rows. */
	public static function count_acf_references( $post_id, $attachment_id ) {
		if ( ! function_exists( 'acf_get_field' ) ) {
			return 0;
		}
		$meta = get_post_meta( $post_id );
		if ( ! is_array( $meta ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $meta as $reference_key => $reference_values ) {
			if ( ! is_string( $reference_key ) || 0 !== strpos( $reference_key, '_' ) || ! is_array( $reference_values ) || ! isset( $reference_values[0] ) ) {
				continue;
			}
			$field_key = $reference_values[0];
			$field_name = substr( $reference_key, 1 );
			if ( ! is_string( $field_key ) || 0 !== strpos( $field_key, 'field_' ) || ! array_key_exists( $field_name, $meta ) ) {
				continue;
			}
			$field = acf_get_field( $field_key );
			if ( ! is_array( $field ) || empty( $field['type'] ) ) {
				continue;
			}
			$value = get_post_meta( $post_id, $field_name, true );
			if ( 'image' === $field['type'] ) {
				$count += self::matches_id( $value, $attachment_id ) ? 1 : 0;
			} elseif ( 'gallery' === $field['type'] && is_array( $value ) ) {
				foreach ( $value as $image_id ) {
					$count += self::matches_id( $image_id, $attachment_id ) ? 1 : 0;
				}
			}
		}
		return $count;
	}

	private static function matches_id( $value, $attachment_id ) {
		return ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) &&
			(string) $value === (string) $attachment_id;
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
