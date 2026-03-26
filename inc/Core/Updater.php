<?php

namespace Charts\Core;

/**
 * Handle automatic updates from GitHub Releases.
 */
class Updater {

	private $file;
	private $plugin_slug;
	private $basename;
	private $active;
	private $username;
	private $repository;
	private $github_api_result;

	public function __construct( $file ) {
		$this->file = $file;
		$this->username = CHARTS_GITHUB_OWNER;
		$this->repository = CHARTS_GITHUB_REPO;
		$this->basename = plugin_basename( $this->file );
		$this->plugin_slug = dirname( $this->basename );

		$this->init();
	}

	/**
	 * Initialize the updater.
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_transient' ) );
		add_filter( 'plugins_api', array( $this, 'set_plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
	}

	/**
	 * Get repository info from GitHub.
	 */
	private function get_repository_info() {
		if ( ! is_null( $this->github_api_result ) ) {
			return $this->github_api_result;
		}

		$request_uri = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository );
		
		$args = array(
			'headers' => array(
				'User-Agent' => 'WordPress-Charts-Plugin-Updater',
			),
		);
		
		$response = wp_remote_get( $request_uri, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$this->github_api_result = json_decode( wp_remote_retrieve_body( $response ) );
		return $this->github_api_result;
	}

	/**
	 * Set the update transient.
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
			$obj = new \stdClass();
			$obj->slug = $this->plugin_slug;
			$obj->new_version = $remote_version;
			$obj->url = $repo_info->html_url;
			$obj->package = $this->get_release_asset_url( $repo_info );
			$obj->plugin = $this->basename;

			$transient->response[ $this->basename ] = $obj;
		}

		return $transient;
	}

	/**
	 * Get the download URL from release assets.
	 */
	private function get_release_asset_url( $repo_info ) {
		if ( ! empty( $repo_info->assets ) ) {
			foreach ( $repo_info->assets as $asset ) {
				if ( false !== strpos( $asset->name, '.zip' ) ) {
					return $asset->browser_download_url;
				}
			}
		}
		// Fallback to source zip if no asset found (not preferred as per task)
		return $repo_info->zipball_url;
	}

	/**
	 * Set plugin info for the info modal.
	 */
	public function set_plugin_info( $false, $action, $response ) {
		if ( 'plugin_information' !== $action ) {
			return $false;
		}

		if ( ! isset( $response->slug ) || $response->slug !== $this->plugin_slug ) {
			return $false;
		}

		$repo_info = $this->get_repository_info();
		if ( ! $repo_info ) {
			return $false;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( $this->file );

		$response->last_updated = $repo_info->published_at;
		$response->slug = $this->plugin_slug;
		$response->plugin_name  = $plugin_data['Name'];
		$response->version = ltrim( $repo_info->tag_name, 'v' );
		$response->author = $plugin_data['AuthorName'];
		$response->homepage = $plugin_data['PluginURI'];
		$response->download_link = $this->get_release_asset_url( $repo_info );

		$response->sections = array(
			'description' => $plugin_data['Description'],
			'changelog'   => $repo_info->body,
		);

		return $response;
	}

	/**
	 * Handle post-install cleanup.
	 */
	public function post_install( $true, $hooks, $result ) {
		// Ensure the directory name is correct if GitHub changed it
		// But as per instructions, we expect the ZIP to be correct.
		return $result;
	}
}
