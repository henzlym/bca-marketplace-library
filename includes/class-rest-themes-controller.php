<?php
/**
 * REST API: WP_REST_Themes_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 5.0.0
 */

/**
 * Core class used to manage themes via the REST API.
 *
 * @since 5.0.0
 *
 * @see WP_REST_Controller
 */
class MarketPlace_REST_Themes_Controller extends WP_REST_Themes_Controller {

	/**
	 * Matches theme's directory: `/themes/<subdirectory>/<theme>/` or `/themes/<theme>/`.
	 * Excludes invalid directory name characters: `/:<>*?"|`.
	 */
	const PATTERN = '[^\/:<>\*\?"\|]+(?:\/[^\/:<>\*\?"\|]+)?';

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		$this->namespace = 'wp/v2/marketplace';
		$this->rest_base = 'themes';

		add_filter( 'rest_prepare_theme', array( $this, 'rest_prepare_theme' ), 10, 3 );
		add_filter( 'rest_themes_collection_params', array( $this, 'rest_themes_collection_params' ),  );

	}

	/**
	 * Registers the routes for themes.
	 *
	 * @since 5.0.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'set_get_items' ),
					'permission_callback' => ( MARKETPLACE_TESTMODE ) ? '__return_true' : array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			sprintf( '/%s/(?P<stylesheet>%s)', $this->rest_base, self::PATTERN ),
			array(
				'args'   => array(
					'stylesheet' => array(
						'description'       => __( "The theme's stylesheet. This uniquely identifies the theme." ),
						'type'              => 'string',
						'sanitize_callback' => array( $this, '_sanitize_stylesheet_callback' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	public function get_theme_download_link($stylesheet){
		$plugin_zip_file =  WP_CONTENT_URL . '/themes/' . $stylesheet . '/' . $stylesheet . '.zip';
		$plugin_zip_file_path =  WP_CONTENT_DIR . '/themes/' . $stylesheet . '/' . $stylesheet . '.zip';
	
		if (!$stylesheet || !file_exists($plugin_zip_file_path)) return false;
	
		return $plugin_zip_file;
	}

	public function rest_themes_collection_params( $query_params )
	{
		$query_params = array(
			'tpd' => array(
				'description' => __( 'Limit result set to themes assigned one or more statuses.' ),
				'type'        => 'array',
				'items'       => array(
					'enum' => array( 'active', 'inactive' ),
					'type' => 'string',
				),
			),
		);

		return $query_params;
	}
	public function rest_prepare_theme( $response, $theme, $request )
	{		
		if ($download_url = $this->get_theme_download_link($response->data['stylesheet']) ) {
			$data = $response->data;
			$theme = $data;
			$default_information = array(
				'tpd' => true,
				'name' => $data['name']['raw'],
				'slug' => $data['stylesheet'],
				'version' => $data['version'],
				'preview_url' => 'https://www.publisherdesk.com/',
				'author' => $data['author']['raw'],
				'screenshot_url' => $data['screenshot'],
				'tested' => $data['requires_wp'],
				'requires' => $data['requires_wp'],
				'author_profile' => $data['author_uri']['raw'],
				'download_link' => $download_url,
				'trunk' => $download_url,
				'requires_php' => $data['requires_php'],
				'last_updated' => date("Y-m-d h:i:sa"),
			);
			$author_information = array(
				'user_nicename' => '',
				'profile' => '',
				'avatar' => '',
				'display_name' => $data['author']['raw'],
				'author' => $data['author']['raw'],
				'author_url' => $data['author_uri']['raw'],
			);
			$reviews_information = array(
				'rating' => 0,
				'num_ratings' => 0,
				'reviews_url' => ''
			);

			$tags = [];
			if ( isset( $data['tags'] ) && !empty( $data['tags'] ) ) {
				$theme_tags = array();
				foreach ( $data['tags']['raw'] as $tag ) {
					$theme_tags[$tag] = ucfirst( str_replace('-', ' ', $tag) );
				}
				$tags = $theme_tags;
			}
			
			$theme['tpd'] = true;
			$theme['download_link'] = $download_url;
			$theme['details_url'] = '';
			$theme['package'] = array(
				'default' => $default_information,
				'theme_information' => array(
					'tpd' => true,
					'author' => $author_information,
					'rating' => 0,
					'num_ratings' => 0,
					'reviews_url' => '',
					'downloaded' => 0,
					'last_updated' => date("Y-m-d"),
					'last_updated_time' => date("Y-m-d h:i:sa"),
					'creation_time' => '',
					'homepage' => $data['theme_uri']['raw'],
					'tags' => $tags
				),
				'query_themes' => array(
					'tpd' => true,
					'author' => $author_information,
					'homepage' => $data['theme_uri']['raw'],
					'description' => $data['description']['raw'],
				),
				'update' => array(
					'tpd' => true,
					'theme' => $data['stylesheet'],
					'slug' => $data['stylesheet'],
					'url' => 'https://www.publisherdesk.com/',
					'requires' => $data['requires_wp'],
					'requires_php' => $data['requires_php'],
					'new_version' => $data['version'],
					'package' => $download_url,
				)
			);

			$theme['package']['query_themes'] = array_merge( $theme['package']['query_themes'], $reviews_information);
			
			$response->data = $theme;
		
		}

		// error_log(print_r($response,true));
		return $response;
	}
	public function set_get_items( $request )
	{
		$response = $this->get_items( $request );
		$themes = (array) $response->get_data();
		if ( is_array($themes) ) {
			foreach ( $themes as $key => $theme ) {
				if (isset($theme['stylesheet']) && isset($theme['download_link'])) {
					$themes[$theme['stylesheet']] = $theme;
				}
				unset($themes[$key]);
			}
			$response->set_data( $themes );
		}
		return $response;
	}
}

function marketplace_market_place_register_routes_init()
{
    $controller = new MarketPlace_REST_Themes_Controller();
    $controller->register_routes();
}
add_action('rest_api_init', 'marketplace_market_place_register_routes_init');
