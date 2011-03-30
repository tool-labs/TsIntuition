<?php
/**
 * Classes for %Toolserver tools translation for TranslateWiki.net
 *
 * @file
 * @author Niklas Laxström
 * @author Krinkle
 * @copyright Copyright © 2008-2010, Niklas Laxström
 * @copyright Copyright © 2011, Krinkle
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * Support for tools using TsIntuition at the Toolserver.
 */
class PremadeToolserverTextdomains extends PremadeMediawikiExtensionGroups {
	protected $useConfigure = false;
	protected $groups;
	protected $idPrefix = 'tsi18n-';
	protected $namespaces = array( NS_TOOLSERVER, NS_TOOLSERVER_TALK );


	public function __construct() {
		global $wgTranslateGroupRoot;

		parent::__construct();
		$dir = dirname( __FILE__ );
		$this->definitionFile = $dir . '/toolserver-textdomains.txt';
		$this->path = "$wgTranslateGroupRoot/ToolserverI18N/language/messages/";
	}

	/// Initialisation function
	public function init() {
		if ( $this->groups !== null ) return;

		$defines = file_get_contents( $this->definitionFile );

		$linefeed = '(\r\n|\n)';

		$sections = array_map( 'trim', preg_split( "/$linefeed{2,}/", $defines, - 1, PREG_SPLIT_NO_EMPTY ) );

		$groups = $fixedGroups = array();

		foreach ( $sections as $section ) {
			$lines = array_map( 'trim', preg_split( "/$linefeed/", $section ) );
			$newgroup = array();

			foreach ( $lines as $line ) {
				if ( $line === '' || $line[0] === '#' ) continue;

				if ( strpos( $line, '=' ) === false ) {
					if ( empty( $newgroup['name'] ) ) {
						$newgroup['name'] = $line;
					} else {
						throw new MWException( "Trying to define name twice: " . $line );
					}
				} else {
					list( $key, $value ) = array_map( 'trim', explode( '=', $line, 2 ) );
					switch ( $key ) {
					case 'file':
					case 'var':
					case 'id':
					case 'descmsg':
						$newgroup[$key] = $value;
						break;
					case 'optional':
					case 'ignored':
						$values = array_map( 'trim', explode( ',', $value ) );
						if ( !isset( $newgroup[$key] ) ) {
							$newgroup[$key] = array();
						}
						$newgroup[$key] = array_merge( $newgroup[$key], $values );
						break;
					case 'prefix':
						$prefix = trim( $value );
						if ( isset( $newgroup['prefix'] ) && $newgroup['prefix'] !== $prefix ) {
							throw new MWException( "Only one prefix supported: {$newgroup['prefix']} !== $prefix" );
						}
						$newgroup['prefix'] = $prefix;

						break;
					default:
						throw new MWException( "Unknown key:" . $key );
					}
				}
			}

			if ( count( $newgroup ) ) {
				if ( empty( $newgroup['name'] ) ) {
					throw new MWException( "Name missing\n" . print_r( $newgroup, true ) );
				}
				$groups[] = $newgroup;
			}
		}


		foreach ( $groups as $g ) {
			if ( !is_array( $g ) ) {
				$g = array( $g );
			}

			$name = $g['name'];

			if ( isset( $g['id'] ) ) {
				$id = $g['id'];
			} else {
				$id = $this->idPrefix . preg_replace( '/\s+/', '', strtolower( $name ) );
			}

			if ( isset( $g['file'] ) ) {
				$file = $g['file'];
			} else {
				// TsIntuition text-domains are case-insensitive and internally
				// converts to lowercase names starting with a capital letter.
				// eg. "My Tool" -> "Mytool.i18n.php"
				$sanatized = preg_replace( '/\s+/', '', ucfirst( strtolower( $name ) ) );
				// No subdirectories
				$file = "$sanatized.i18n.php";
			}

			if ( isset( $g['descmsg'] ) ) {
				$descmsg = $g['descmsg'];
			} else {
				$descmsg = str_replace( $this->idPrefix, '', $id ) . '-desc';
			}

			if ( isset( $g['url'] ) ) {
				$url = $g['url'];
			} else {
				$url = false;
			}

			$newgroup = array(
				'name' => $name,
				'file' => $file,
				'descmsg' => $descmsg,
				'url' => $url,
			);

			$copyvars = array( 'ignored', 'optional', 'var', 'prefix' );
			foreach ( $copyvars as $var ) {
				if ( isset( $g[$var] ) ) {
					$newgroup[$var] = $g[$var];
				}
			}

			$fixedGroups[$id] = $newgroup;
		}

		$this->groups = $fixedGroups;
	}
}