<?php
/**
 * Small, self-contained Markdown to HTML converter.
 *
 * Covers the Markdown that blog posts actually use: headings, paragraphs,
 * bold/italic, inline code, fenced code blocks, links, images, blockquotes,
 * ordered and unordered lists, and horizontal rules. Raw HTML in the source is
 * escaped rather than passed through, so a synced file can never inject markup.
 *
 * This is plugin code, not a third-party library, so there is nothing to keep
 * in sync with an upstream release.
 *
 * @package RepoBridgeForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RepoBridgeForge_Markdown {

	/** @var string[] Protected inline fragments, restored after inline parsing. */
	private $tokens = array();

	/**
	 * Convert a Markdown string to HTML.
	 *
	 * @param string $markdown
	 * @return string
	 */
	public function text( $markdown ) {
		$markdown = str_replace( array( "\r\n", "\r" ), "\n", (string) $markdown );

		return trim( $this->parse_blocks( explode( "\n", $markdown ) ) );
	}

	/**
	 * Turn an array of lines into block-level HTML.
	 *
	 * @param string[] $lines
	 * @return string
	 */
	private function parse_blocks( $lines ) {
		$out = array();
		$i   = 0;
		$n   = count( $lines );

		while ( $i < $n ) {
			$line = $lines[ $i ];

			if ( '' === trim( $line ) ) {
				$i++;
				continue;
			}

			// Fenced code block.
			if ( preg_match( '/^\s*(`{3,}|~{3,})/', $line, $m ) ) {
				$fence = $m[1][0];
				$buf   = array();
				$i++;
				while ( $i < $n && ! preg_match( '/^\s*' . $fence . '{3,}\s*$/', $lines[ $i ] ) ) {
					$buf[] = $lines[ $i ];
					$i++;
				}
				$i++;
				$out[] = '<pre><code>' . htmlspecialchars( implode( "\n", $buf ), ENT_QUOTES, 'UTF-8' ) . '</code></pre>';
				continue;
			}

			// ATX heading.
			if ( preg_match( '/^(#{1,6})\s+(.*?)\s*#*\s*$/', $line, $m ) ) {
				$level = strlen( $m[1] );
				$out[] = '<h' . $level . '>' . $this->inline( $m[2] ) . '</h' . $level . '>';
				$i++;
				continue;
			}

			// Horizontal rule.
			if ( preg_match( '/^\s*([-*_])(\s*\1){2,}\s*$/', $line ) ) {
				$out[] = '<hr />';
				$i++;
				continue;
			}

			// Blockquote.
			if ( preg_match( '/^\s*>\s?/', $line ) ) {
				$buf = array();
				while ( $i < $n && preg_match( '/^\s*>\s?(.*)$/', $lines[ $i ], $m ) ) {
					$buf[] = $m[1];
					$i++;
				}
				$out[] = '<blockquote>' . "\n" . $this->parse_blocks( $buf ) . "\n" . '</blockquote>';
				continue;
			}

			// List (unordered or ordered).
			if ( preg_match( '/^\s*([-*+]|\d+\.)\s+/', $line ) ) {
				$ordered = (bool) preg_match( '/^\s*\d+\.\s+/', $line );
				$items   = array();
				$current = null;
				while ( $i < $n ) {
					if ( preg_match( '/^\s*(?:[-*+]|\d+\.)\s+(.*)$/', $lines[ $i ], $m ) ) {
						if ( null !== $current ) {
							$items[] = $current;
						}
						$current = $m[1];
					} elseif ( '' === trim( $lines[ $i ] ) ) {
						break;
					} elseif ( null !== $current && preg_match( '/^\s+(\S.*)$/', $lines[ $i ], $m ) ) {
						$current .= "\n" . $m[1];
					} else {
						break;
					}
					$i++;
				}
				if ( null !== $current ) {
					$items[] = $current;
				}
				$li = '';
				foreach ( $items as $item ) {
					$li .= '<li>' . $this->inline( trim( $item ) ) . '</li>' . "\n";
				}
				$tag   = $ordered ? 'ol' : 'ul';
				$out[] = '<' . $tag . '>' . "\n" . $li . '</' . $tag . '>';
				continue;
			}

			// Paragraph.
			$buf = array();
			while ( $i < $n && '' !== trim( $lines[ $i ] ) && ! $this->starts_block( $lines[ $i ] ) ) {
				$buf[] = trim( $lines[ $i ] );
				$i++;
			}
			if ( $buf ) {
				$out[] = '<p>' . $this->inline( implode( "\n", $buf ) ) . '</p>';
			}
		}

		return implode( "\n", $out );
	}

	/**
	 * Whether a line begins a non-paragraph block.
	 *
	 * @param string $line
	 * @return bool
	 */
	private function starts_block( $line ) {
		return (bool) preg_match( '/^(\s*(`{3,}|~{3,})|#{1,6}\s|\s*([-*_])(\s*\3){2,}\s*$|\s*>\s?|\s*([-*+]|\d+\.)\s+)/', $line );
	}

	/**
	 * Stash an already-built HTML fragment and return its placeholder token.
	 *
	 * @param string $html
	 * @return string
	 */
	private function stash( $html ) {
		$this->tokens[] = $html;
		return "\x02" . ( count( $this->tokens ) - 1 ) . "\x03";
	}

	/**
	 * Inline formatting: code, escaping, images, links, emphasis, breaks.
	 *
	 * @param string $text
	 * @return string
	 */
	private function inline( $text ) {
		// Inline code spans, kept verbatim.
		$text = preg_replace_callback(
			'/`([^`]+)`/',
			function ( $m ) {
				return $this->stash( '<code>' . htmlspecialchars( $m[1], ENT_QUOTES, 'UTF-8' ) . '</code>' );
			},
			$text
		);

		// Escape any raw HTML in what remains.
		$text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );

		// Images: ![alt](url) — any title after the URL is ignored.
		$text = preg_replace_callback(
			'/!\[([^\]]*)\]\(\s*([^)\s]+)[^)]*\)/',
			function ( $m ) {
				$url = esc_url( html_entity_decode( $m[2], ENT_QUOTES ) );
				$alt = esc_attr( html_entity_decode( $m[1], ENT_QUOTES ) );
				return $this->stash( '<img src="' . $url . '" alt="' . $alt . '" />' );
			},
			$text
		);

		// Links: [text](url) — the visible text still gets emphasis.
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\(\s*([^)\s]+)[^)]*\)/',
			function ( $m ) {
				$url = esc_url( html_entity_decode( $m[2], ENT_QUOTES ) );
				return $this->stash( '<a href="' . $url . '">' . $this->emphasis( $m[1] ) . '</a>' );
			},
			$text
		);

		$text = $this->emphasis( $text );

		// Hard breaks, then fold remaining newlines to spaces.
		$text = preg_replace( '/ {2,}\n/', "<br />\n", $text );
		$text = str_replace( "\n", ' ', $text );

		// Restore stashed fragments (loop in case a link wrapped a code span).
		while ( false !== strpos( $text, "\x02" ) ) {
			$text = preg_replace_callback(
				'/\x02(\d+)\x03/',
				function ( $m ) {
					return isset( $this->tokens[ (int) $m[1] ] ) ? $this->tokens[ (int) $m[1] ] : '';
				},
				$text
			);
		}

		return $text;
	}

	/**
	 * Bold and italic.
	 *
	 * @param string $text
	 * @return string
	 */
	private function emphasis( $text ) {
		$text = preg_replace( '/(\*\*|__)(?=\S)(.+?)(?<=\S)\1/s', '<strong>$2</strong>', $text );
		$text = preg_replace( '/(\*|_)(?=\S)(.+?)(?<=\S)\1/s', '<em>$2</em>', $text );
		return $text;
	}
}
