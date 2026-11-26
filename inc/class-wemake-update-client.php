<?php
/**
 * Wemake Update Client 1.0
 *
 * Shared by all Wemake plugins: the same file is copied into each plugin (canonical copy:
 * wemake-digital-agency/wemake-repository, client/class-wemake-update-client.php).
 *
 * - Asks https://repository.wemake.co.il/check.php for the latest version when WordPress reads its plugin update list
 *   (at most once per 12 hours, once per hour after an error; "Check again" on Dashboard > Updates skips the cache).
 *   The same request records the site on the update server. Works also when wordpress.org is not reachable.
 * - Shows the update in wp-admin and lets WordPress install it with its standard updater (auto-updates work too).
 * - Accepts only packages from https://repository.wemake.co.il/get-plugin.php, and only a valid version number.
 * - Keeps the plugin's current folder name after the update, so the plugin stays active.
 * - Never replaces files itself: WordPress downloads the whole zip (checked against the server's Content-MD5),
 *   unpacks it into a temporary folder, this client checks that the plugin's main file is there, and only then
 *   WordPress swaps the folders, keeping a backup of the old version that it restores if the copy fails.
 *
 * Usage in the main plugin file:
 *   require_once __DIR__ . '/inc/class-wemake-update-client.php';
 *   new Wemake_Update_Client_1_0( __FILE__, 'plugin-slug', 'x.y.z' );
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wemake_Update_Client_1_0' ) ) {

	final class Wemake_Update_Client_1_0 {

		const ENDPOINT       = 'https://repository.wemake.co.il/check.php';
		const PACKAGE_PREFIX = 'https://repository.wemake.co.il/get-plugin.php?';
		const CACHE_TTL      = 43200; // 12 hours
		const ERROR_TTL      = 3600;  // retry after 1 hour when the server did not answer

		/** @var string */
		private $basename;
		/** @var string */
		private $slug;
		/** @var string */
		private $version;
		/** @var string */
		private $cache_key;
		/** @var array|string|null Answer already fetched in this request (WordPress saves the update list twice per check). */
		private $memo = null;
		/** @var bool The plugin was updated in this request: the loaded code is the old version, offer nothing more. */
		private $upgraded = false;

		public function __construct( $plugin_file, $slug, $version ) {
			$this->basename  = plugin_basename( $plugin_file );
			$this->slug      = (string) $slug;
			$this->version   = (string) $version;
			$this->cache_key = 'wemake_update_' . md5( $this->basename );

			// Added when the update list is read, not when it is saved: WordPress saves the list only after
			// wordpress.org answers, so a site that can't reach wordpress.org would never see our update.
			add_filter( 'site_transient_update_plugins', array( $this, 'inject_update' ) );
			add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'keep_folder_name' ), 10, 4 );
			add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
		}

		/**
		 * Latest release from the update server: array( 'new_version', 'package' ) or null.
		 */
		private function remote() {
			if ( null !== $this->memo ) {
				return is_array( $this->memo ) ? $this->memo : null;
			}
			$force  = is_admin() && isset( $_GET['force-check'] ); // "Check again" on Dashboard > Updates
			$cached = get_site_transient( $this->cache_key );
			if ( false !== $cached && ! $force ) {
				$this->memo = $cached;
				return is_array( $cached ) ? $cached : null;
			}

			$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
			$port = wp_parse_url( home_url(), PHP_URL_PORT );
			if ( $port ) {
				$host .= ':' . (int) $port;
			}

			$response = wp_remote_post(
				self::ENDPOINT,
				array(
					'timeout'     => 10,
					'redirection' => 0,
					'user-agent'  => 'WemakeUpdateClient/1.0',
					'body'        => array(
						'host'    => $host,
						'plugin'  => $this->slug,
						'version' => $this->version,
						'wp'      => (string) get_bloginfo( 'version' ),
						'php'     => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
					),
				)
			);

			$data = 'none';
			$ttl  = self::CACHE_TTL;
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				$ttl = self::ERROR_TTL;
			} else {
				$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );
				if ( is_array( $json )
					&& isset( $json['new_version'], $json['package'] )
					&& is_string( $json['new_version'] ) && is_string( $json['package'] )
					&& preg_match( '/^[0-9]{1,4}(\.[0-9]{1,4}){0,3}$/', $json['new_version'] )
					&& 0 === strpos( $json['package'], self::PACKAGE_PREFIX )
					&& strlen( $json['package'] ) < 1000 ) {
					$data = array(
						'new_version' => $json['new_version'],
						'package'     => $json['package'],
					);
					// Requirements of the new version: WordPress shows the update as incompatible and skips auto-updates
					foreach ( array( 'requires_php', 'requires' ) as $key ) {
						if ( isset( $json[ $key ] ) && is_string( $json[ $key ] ) && preg_match( '/^[0-9]{1,3}(\.[0-9]{1,3}){0,2}$/', $json[ $key ] ) ) {
							$data[ $key ] = $json[ $key ];
						}
					}
				}
			}

			set_site_transient( $this->cache_key, $data, $ttl );
			$this->memo = $data;
			return is_array( $data ) ? $data : null;
		}

		/**
		 * Puts our plugin into WordPress' update list (response) or the "up to date" list (no_update).
		 */
		public function inject_update( $transient ) {
			if ( ! is_object( $transient ) || wp_installing() ) {
				return $transient;
			}
			if ( $this->upgraded ) {
				unset( $transient->response[ $this->basename ] );
				return $transient;
			}
			$data = $this->remote();

			$item = (object) array(
				'id'          => 'repository.wemake.co.il/' . $this->slug,
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $this->version,
				'url'         => 'https://www.wemake.co.il/',
				'package'     => '',
				'icons'       => array(),
				'banners'     => array(),
			);

			if ( $data && version_compare( $data['new_version'], $this->version, '>' ) ) {
				$item->new_version = $data['new_version'];
				$item->package     = $data['package'];
				if ( isset( $data['requires_php'] ) ) {
					$item->requires_php = $data['requires_php'];
				}
				if ( isset( $data['requires'] ) ) {
					$item->requires = $data['requires'];
				}
				$transient->response[ $this->basename ] = $item;
				unset( $transient->no_update[ $this->basename ] );
			} else {
				unset( $transient->response[ $this->basename ] );
				$transient->no_update[ $this->basename ] = $item;
			}
			return $transient;
		}

		/**
		 * "View details" in wp-admin.
		 */
		public function plugin_info( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || ! is_object( $args ) || ! isset( $args->slug ) || $args->slug !== $this->slug ) {
				return $result;
			}
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugin = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->basename, false, false );
			$data   = $this->remote();
			// Author and links from the plugin header (Author, Author URI, Plugin URI)
			$author     = '' !== trim( (string) $plugin['Author'] ) ? trim( (string) $plugin['Author'] ) : 'WeMake';
			$author_uri = trim( (string) $plugin['AuthorURI'] );
			$homepage   = trim( (string) $plugin['PluginURI'] );
			if ( '' === $homepage ) {
				$homepage = '' !== $author_uri ? $author_uri : 'https://www.wemake.co.il/';
			}

			return (object) array(
				'name'          => $plugin['Name'],
				'slug'          => $this->slug,
				'version'       => $data ? $data['new_version'] : $this->version,
				'author'        => '' !== $author_uri ? '<a href="' . esc_url( $author_uri ) . '">' . esc_html( $author ) . '</a>' : esc_html( $author ),
				'homepage'      => esc_url_raw( $homepage ),
				'requires_php'  => ( $data && isset( $data['requires_php'] ) ) ? $data['requires_php'] : ( isset( $plugin['RequiresPHP'] ) ? $plugin['RequiresPHP'] : '' ),
				'requires'      => ( $data && isset( $data['requires'] ) ) ? $data['requires'] : ( isset( $plugin['RequiresWP'] ) ? $plugin['RequiresWP'] : '' ),
				'download_link' => $data ? $data['package'] : '',
				'sections'      => array(
					'description' => esc_html( (string) $plugin['Description'] ),
				),
			);
		}

		/**
		 * Runs after WordPress downloaded and unpacked the update into a temporary folder, before it touches the
		 * installed plugin. An error here stops the update and the installed version stays as it is.
		 * - The package must hold this plugin's main file with a plugin header.
		 * - The site must meet the new version's "Requires PHP" and "Requires at least" (WordPress itself checks them
		 *   only in some update paths).
		 * - The release zip may unpack into another folder name than the installed one (e.g. "plugin-main");
		 *   it is renamed to the installed folder so the plugin stays active.
		 */
		public function keep_folder_name( $source, $remote_source, $upgrader, $hook_extra = array() ) {
			if ( is_wp_error( $source ) || ! is_array( $hook_extra ) || ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
				return $source;
			}
			global $wp_filesystem;
			$main = trailingslashit( $source ) . basename( $this->basename );
			$headers = is_file( $main ) ? get_file_data(
				$main,
				array(
					'Name'        => 'Plugin Name',
					'Version'     => 'Version',
					'RequiresPHP' => 'Requires PHP',
					'RequiresWP'  => 'Requires at least',
				)
			) : null;
			$error = null;
			if ( ! $headers || '' === trim( (string) $headers['Name'] ) || '' === trim( (string) $headers['Version'] ) ) {
				$error = new WP_Error( 'wemake_update_package', 'The update package does not contain the plugin. The installed version was kept.' );
			} elseif ( '' !== trim( (string) $headers['RequiresPHP'] ) && version_compare( PHP_VERSION, trim( (string) $headers['RequiresPHP'] ), '<' ) ) {
				$error = new WP_Error( 'wemake_update_php', sprintf( 'The new version requires PHP %s; this site runs PHP %s. The installed version was kept.', trim( (string) $headers['RequiresPHP'] ), PHP_VERSION ) );
			} elseif ( '' !== trim( (string) $headers['RequiresWP'] ) && version_compare( (string) get_bloginfo( 'version' ), trim( (string) $headers['RequiresWP'] ), '<' ) ) {
				$error = new WP_Error( 'wemake_update_wp', sprintf( 'The new version requires WordPress %s; this site runs WordPress %s. The installed version was kept.', trim( (string) $headers['RequiresWP'] ), get_bloginfo( 'version' ) ) );
			}
			if ( $error ) {
				if ( $wp_filesystem ) {
					$wp_filesystem->delete( $remote_source, true ); // the unpacked copy in wp-content/upgrade
				}
				return $error;
			}

			$folder = dirname( $this->basename );
			if ( '.' === $folder || '' === $folder ) {
				return $source;
			}
			$wanted = trailingslashit( $remote_source ) . $folder;
			if ( untrailingslashit( $source ) === untrailingslashit( $wanted ) ) {
				return $source;
			}
			if ( $wp_filesystem && $wp_filesystem->move( untrailingslashit( $source ), $wanted, true ) ) {
				return trailingslashit( $wanted );
			}
			return new WP_Error( 'wemake_update_folder', 'Could not rename the update folder. The installed version was kept.' );
		}

		public function clear_cache( $upgrader, $options ) {
			if ( isset( $options['type'], $options['plugins'] ) && 'plugin' === $options['type']
				&& is_array( $options['plugins'] ) && in_array( $this->basename, $options['plugins'], true ) ) {
				delete_site_transient( $this->cache_key );
				$this->memo     = null;
				$this->upgraded = true;
			}
		}
	}
}
