<?php

namespace Charts\Core;

/**
 * Handle automatic updates from GitHub Releases.
 * Hardened for production-grade reliability.
 */
class Updater {

	private $file;
	private $plugin_slug;
	private $basename;
	private $username;
	private $repository;
	private $github_api_result;

	public function __construct( $file ) {
		$this->file        = $file;
		$this->username    = CHARTS_GITHUB_OWNER;
		$this->repository  = CHARTS_GITHUB_REPO;
		$this->basename    = CHARTS_PLUGIN_BASENAME;
		$this->plugin_slug = dirname( $this->basename );

		$this->init();
	}

	/**
	 * Initialize the updater hooks.
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_transient' ) );
		add_filter( 'plugins_api', array( $this, 'set_plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
	}

	/**
	 * Query the latest published release from GitHub.
	 */
	private function get_repository_info() {
		if ( ! is_null( $this->github_api_result ) ) {
			return $this->github_api_result;
		}

		$cache_key     = 'charts_github_update_check';
		$cached        = get_transient( $cache_key );
		$force_refresh = ( isset( $_GET['force-check'] ) || ( isset( $_GET['action'] ) && $_GET['action'] === 'upgrade-plugin' ) );

		if ( ! $force_refresh && $cached ) {
			$this->github_api_result = $cached;
			return $cached;
		}

		$request_uri = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository );
		$args = array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress-Charts-Updater'
			),
		);

		$response = wp_remote_get( $request_uri, $args );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$this->github_api_result = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $this->github_api_result ) {
			set_transient( $cache_key, $this->github_api_result, 2 * HOUR_IN_SECONDS );
		}

		return $this->github_api_result;
	}

	/**
	 * Inject update data into the plugins transient.
	 */
	public function set_transient( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$repo_info = $this->get_repository_info();
		if ( ! $repo_info || empty( $repo_info->tag_name ) ) {
			return $transient;
		}

		$remote_version = ltrim( $repo_info->tag_name, 'v' );
		$local_version  = CHARTS_VERSION;

		if ( version_compare( $remote_version, $local_version, '>' ) ) {
			$package = $this->get_release_asset_url( $repo_info );
			
			if ( $package ) {
				$obj = new \stdClass();
				$obj->slug        = $this->plugin_slug;
				$obj->new_version = $remote_version;
				$obj->url         = $repo_info->html_url;
				$obj->package     = $package;
				$obj->plugin      = $this->basename;

				$transient->response[ $this->basename ] = $obj;
			}
		}

		return $transient;
	}

	/**
	 * Strictly select the uploaded ZIP asset from the Release.
	 */
	private function get_release_asset_url( $repo_info ) {
		if ( empty( $repo_info->assets ) || ! is_array( $repo_info->assets ) ) {
			return false;
		}

		foreach ( $repo_info->assets as $asset ) {
			// Look for a zip file that isn't the generic source-code zip
			if ( isset( $asset->content_type ) && $asset->content_type === 'application/zip' ) {
				return $asset->browser_download_url;
			}
			if ( isset( $asset->name ) && strpos( $asset->name, '.zip' ) !== false ) {
				return $asset->browser_download_url;
			}
		}

		return false;
	}

	/**
	 * Provide data for the "View Details" modal.
	 */
	public function set_plugin_info( $res, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $res;
		}

		$repo_info = $this->get_repository_info();
		if ( ! $repo_info ) {
			return $res;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( $this->file );

		$res = new \stdClass();
		$res->name          = $plugin_data['Name'];
		$res->slug          = $this->plugin_slug;
		$res->version       = ltrim( $repo_info->tag_name, 'v' );
		$res->author        = '<a href="' . esc_url($plugin_data['AuthorURI']) . '">' . esc_html($plugin_data['Author']) . '</a>';
		$res->homepage      = $plugin_data['PluginURI'];
		$res->download_link = $this->get_release_asset_url( $repo_info );
		$res->last_updated  = $repo_info->published_at;
		$res->requires      = $plugin_data['RequiresWP'] ?? '5.0';
		$res->tested        = $plugin_data['TestedUpTo'] ?? '6.4';

		$res->sections = array(
			'description' => $plugin_data['Description'],
			'changelog'   => isset( $repo_info->body ) ? wp_kses_post( wpautop($repo_info->body) ) : '',
		);

		return $res;
	}

	/**
	 * Ensure the plugin folder is corrected after update.
	 */
	public function post_install( $true, $hooks, $result ) {
		global $wp_filesystem;

		$plugin_folder = CHARTS_PATH;
		$plugin_slug   = basename($plugin_folder); // charts
		$install_dir   = $result['destination']; // full path to new temp folder

		// If the new folder name is different (e.g. charts-0.1.0), rename it to our fixed slug
		if ( basename($install_dir) !== $plugin_slug ) {
			$new_destination = trailingslashit( $result['local_destination'] ) . $plugin_slug;
			
			if ( $wp_filesystem->exists( $new_destination ) ) {
				$wp_filesystem->delete( $new_destination, true );
			}

			$wp_filesystem->move( $install_dir, $new_destination );
			$result['destination'] = $new_destination;
		}

		return $result;
	}
}
