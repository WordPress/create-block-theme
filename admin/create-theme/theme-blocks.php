<?php

require_once( __DIR__ . '/theme-media.php' );
require_once( __DIR__ . '/theme-patterns.php' );
require_once( __DIR__ . '/theme-utils.php' );

class Theme_Blocks {
	// find all the media files used in the templates and add them to the zip
	public static function make_template_images_local( $template ) {
		$new_content     = $template->content;
		$template_blocks = parse_blocks( $template->content );
		$flatten_blocks  = _flatten_blocks( $template_blocks );

		$blocks = self::make_media_blocks_local( $template_blocks );
		$blocks = serialize_blocks( $blocks );

		$template->content = self::clean_serialized_markup( $blocks );
		$template->media   = Theme_Media::get_media_absolute_urls_from_blocks( $flatten_blocks );
		return $template;
	}

	static function make_media_blocks_local( $nested_blocks ) {
		$new_blocks = array();
		foreach ( $nested_blocks as $block ) {
			$inner_blocks = $block['innerBlocks'];
			switch ( $block['blockName'] ) {
				case 'core/image':
				case 'core/video':
					$block = self::make_image_video_block_local( $block );
					break;
				case 'core/cover':
					$block = self::make_cover_block_local( $block );
					break;
				case 'core/media-text':
					$block = self::make_mediatext_block_local( $block );
					break;
			}
			// recursive call for inner blocks
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::make_media_blocks_local( $inner_blocks );
			}
			$new_blocks[] = $block;
		}
		return $new_blocks;
	}

	static function make_image_video_block_local( $block ) {
		if ( 'core/image' === $block['blockName'] || 'core/video' === $block['blockName'] ) {
			$inner_html            = self::make_html_media_local( $block['innerHTML'] );
			$inner_html            = Theme_Patterns::escape_alt_for_pattern( $inner_html );
			$block['innerHTML']    = $inner_html;
			$block['innerContent'] = array( $inner_html );
		}
		return $block;
	}

	static function make_cover_block_local( $block ) {
		if ( 'core/cover' === $block['blockName'] ) {
			$inner_html    = self::make_html_media_local( $block['innerHTML'] );
			$inner_html    = Theme_Patterns::escape_alt_for_pattern( $inner_html );
			$inner_content = array();
			foreach ( $block['innerContent'] as $content ) {
				$content_html    = self::make_html_media_local( $content );
				$content_html    = Theme_Patterns::escape_alt_for_pattern( $content_html );
				$inner_content[] = $content_html;
			}
			$block['innerHTML']    = $inner_html;
			$block['innerContent'] = $inner_content;
			if ( isset( $block['attrs']['url'] ) && Theme_Utils::is_absolute_url( $block['attrs']['url'] ) ) {
				$block['attrs']['url'] = Theme_Media::make_relative_media_url( $block['attrs']['url'] );
			}
		}
		return $block;
	}

	static function make_mediatext_block_local( $block ) {
		if ( 'core/media-text' === $block['blockName'] ) {
			$inner_html    = self::make_html_media_local( $block['innerHTML'] );
			$inner_html    = Theme_Patterns::escape_alt_for_pattern( $inner_html );
			$inner_content = array();
			foreach ( $block['innerContent'] as $content ) {
				$content_html    = self::make_html_media_local( $content );
				$content_html    = Theme_Patterns::escape_alt_for_pattern( $content_html );
				$inner_content[] = $content_html;
			}
			$block['innerHTML']    = $inner_html;
			$block['innerContent'] = $inner_content;
			if ( isset( $block['attrs']['mediaLink'] ) && Theme_Utils::is_absolute_url( $block['attrs']['mediaLink'] ) ) {
				$block['attrs']['mediaLink'] = Theme_Media::make_relative_media_url( $block['attrs']['mediaLink'] );
			}
		}
		return $block;
	}

	static function make_html_media_local( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}

		$html = new WP_HTML_Tag_Processor( $html );
		while ( $html->next_tag( 'img' ) ) {
			if ( Theme_Utils::is_absolute_url( $html->get_attribute( 'src' ) ) ) {
				$html->set_attribute( 'src', Theme_Media::make_relative_media_url( $html->get_attribute( 'src' ) ) );
			}
		}
		$html = new WP_HTML_Tag_Processor( $html->__toString() );
		while ( $html->next_tag( 'video' ) ) {
			if ( Theme_Utils::is_absolute_url( $html->get_attribute( 'src' ) ) ) {
				$html->set_attribute( 'src', Theme_Media::make_relative_media_url( $html->get_attribute( 'src' ) ) );
			}
			if ( Theme_Utils::is_absolute_url( $html->get_attribute( 'poster' ) ) ) {
				$html->set_attribute( 'poster', Theme_Media::make_relative_media_url( $html->get_attribute( 'poster' ) ) );
			}
		}
		$html = new WP_HTML_Tag_Processor( $html->__toString() );
		while ( $html->next_tag( 'div' ) ) {
			$style = $html->get_attribute( 'style' );
			if ( $style ) {
				preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $style, $match );
				$urls = $match[0];
				foreach ( $urls as $url ) {
					if ( Theme_Utils::is_absolute_url( $url ) ) {
						$html->set_attribute( 'style', str_replace( $url, Theme_Media::make_relative_media_url( $url ), $style ) );
					}
				}
			}
		}
		return $html->__toString();
	}

	static function clean_serialized_markup( $markup ) {
		$markup = str_replace( '%20', ' ', $markup );
		$markup = str_replace( '\u003c', '<', $markup );
		$markup = str_replace( '\u003e', '>', $markup );
		$markup = str_replace( '\u002d', '-', $markup );
		$markup = html_entity_decode( $markup, ENT_QUOTES | ENT_XML1, 'UTF-8' );
		return $markup;
	}

}
