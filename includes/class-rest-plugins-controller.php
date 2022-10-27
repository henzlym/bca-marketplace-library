<?php
class Marketplace_REST_Posts_Controller extends WP_REST_Plugins_Controller
{

    const PATTERN = '[^.\/]+(?:\/[^.\/]+)?';
    
    // Here initialize our namespace and resource name.
    public function __construct()
    {
        $this->namespace     = 'wp/v2/marketplace';
        $this->rest_base = 'plugins';
        add_filter( 'rest_prepare_plugin', array( $this, 'rest_prepare_plugin' ), 10, 3 );
    }
    // Register our routes.
    public function register_routes()
    {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'set_get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'slug'   => array(
							'type'        => 'string',
							'required'    => true,
							'description' => __( 'WordPress.org plugin directory slug.' ),
							'pattern'     => '[\w\-]+',
						),
						'status' => array(
							'description' => __( 'The plugin activation status.' ),
							'type'        => 'string',
							'enum'        => is_multisite() ? array( 'inactive', 'active', 'network-active' ) : array( 'inactive', 'active' ),
							'default'     => 'inactive',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<plugin>' . self::PATTERN . ')',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'args'   => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					'plugin'  => array(
						'type'              => 'string',
						'pattern'           => self::PATTERN,
						'validate_callback' => array( $this, 'validate_plugin_param' ),
						'sanitize_callback' => array( $this, 'sanitize_plugin_param' ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
    }
    public function get_plugin_slug( $item )
    {
        $slug = preg_replace('/\/([a-z-]+)/', '', $item['plugin']);
        if (!$slug) return false;

        return $slug;
    }
    public function get_plugin_download_link($item)
    {
        $slug = preg_replace('/\/.*.php/', '', $item['plugin']);
        $plugin_zip_file =  WP_PLUGIN_URL . '/' . $slug . '.zip';
        $plugin_zip_file_path =  WP_PLUGIN_DIR . '/' . $slug . '.zip';

        if (!$slug || !file_exists($plugin_zip_file_path)) return false;

        return $plugin_zip_file;
    }
    /**
     * Filters plugin data for a REST API response.
     *
     * @since 5.5.0
     *
     * @param WP_REST_Response $response The response object.
     * @param array            $item     The plugin item from {@see get_plugin_data()}.
     * @param WP_REST_Request  $request  The request object.
     */
    public function rest_prepare_plugin( $response, $plugin, $request )
    {

        $plugin = $response->get_data();
        $download_url = $this->get_plugin_download_link($plugin);

        if ( $request->get_route() !== '/wp/v2/marketplace/plugins' || !$download_url ) return $response;

        $plugin['download_link'] = $this->get_plugin_download_link($plugin);
        $plugin['slug'] = $this->get_plugin_slug($plugin);

        $default_information = array(
            'tpd' => true,
            'name' => $plugin['name'],
            'slug' => $plugin['slug'],
            'author' => $plugin['author'],
            'author_profile' => $plugin['author_uri'],
            'version' => $plugin['version'],
            'download_link' => $plugin['download_link'],
            'icons' => array(
                'default'  => MARKETPLACE_URL . 'assets/icon-256x256.jpg',
                '1x' => MARKETPLACE_URL . 'assets/icon-128x128.jpg',
                '2x' => MARKETPLACE_URL . 'assets/icon-256x256.jpg'
            ),
            'banners' => array(
                'low'  => MARKETPLACE_URL . 'assets/banner-772x250.jpg',
                'high' => MARKETPLACE_URL . 'assets/banner-772x250.jpg'
            )
        );

        $tags = [];
        if ( isset( $data['tags'] ) && !empty( $data['tags'] ) ) {
            $plugin_tags = array();
            foreach ( $data['tags']['raw'] as $tag ) {
                $plugin_tags[$tag] = str_replace('-', ' ', $tag);
            }
            $tags = $plugin_tags;
        }

        $plugin_information = array(
            'tpd' => true,
            'name' => $plugin['name'],
            'slug' => $plugin['slug'],
            'version' => $plugin['version'],
            'author' => $plugin['author'],
            'author_profile' => $plugin['author_uri'],
            'requires'=> $plugin['requires_wp'],
            'tested' => $plugin['requires_wp'],
            'requires_php' => $plugin['requires_php'],
            'rating' => 0,
            'ratings' => array(),
            'num_ratings' => 0,
            'support_threads' => 0,
            'support_threads_resolved' => 0,
            'active_installs' => 0,
            'downloaded' => 0,
            'last_updated' => date("Y-m-d h:i:sa"),
            'added' => date("Y-m-d h:i:sa"),
            'homepage' => $plugin['author'],
            'download_link' => $this->get_plugin_download_link($plugin),
            'tags' => $tags,
            'donate_link' => '',
            'icons' => array(
                'default'  => MARKETPLACE_URL . 'assets/icon-256x256.jpg',
                '1x' => MARKETPLACE_URL . 'assets/icon-128x128.jpg',
                '2x' => MARKETPLACE_URL . 'assets/icon-256x256.jpg'
            ),
            'banners' => array(
                'low' => $default_information['banners']['low'],
                'high' => $default_information['banners']['high']
            )
        );

        $query_plugins = array(
            'tpd' => true,
            'name' => $plugin['name'],
            'slug' => $plugin['slug'],
            'version' => $plugin['version'],
            'author' => $plugin['author'],
            'author_profile' => $plugin['author_uri'],
            'requires'=> $plugin['requires_wp'],
            'tested' => $plugin['requires_wp'],
            'requires_php' => $plugin['requires_php'],
            'rating' => 0,
            'ratings' => array(),
            'num_ratings' => 0,
            'support_threads' => 0,
            'support_threads_resolved' => 0,
            'active_installs' => 0,
            'downloaded' => 0,
            'last_updated' => date("Y-m-d h:i:sa"),
            'added' => date("Y-m-d h:i:sa"),
            'homepage' => $plugin['author'],
            'short_description' => $plugin['description']['raw'],
            'description' => $plugin['description']['rendered'],
            'download_link' => $this->get_plugin_download_link($plugin),
            'tags' => $tags,
            'donate_link' => '',
            'icons' => array(
                'default'  => MARKETPLACE_URL . 'assets/icon-256x256.jpg',
                '1x' => MARKETPLACE_URL . 'assets/icon-128x128.jpg',
                '2x' => MARKETPLACE_URL . 'assets/icon-256x256.jpg'
            )
        );

        $plugin['package'] = array(
            'default' => $default_information,
            'plugin_information' => $plugin_information,
            'query_plugins' => $query_plugins,
            'update' => array(
                'tpd' => true,
				'id' => 'tpd/plugins/' . $default_information['slug'],
				'slug' => $default_information['slug'],
				'plugin'=> $plugin['plugin'] . '.php', // plugin-slug/plugin-slug.php
				'new_version' => $plugin['version'],
				'url' => 'https://www.publisherdesk.com/',
				'package' => $default_information['download_link'],
                'icons' => $default_information['icons'],
                'banners' => $default_information['banners'],
                'banners_rtl' => array(),
                'requires'=> $plugin['requires_wp'],
                'tested' => $plugin['requires_wp'],
                'screenshot_url' => $default_information['icons']['default'],
            )
        );
        // error_log(print_r($plugin,true));

        $response->data = $plugin;

        return $response;
    }

    public function set_get_items( $request )
	{
		$response = $this->get_items( $request );
		$plugins = (array) $response->get_data();

        if ( $request->get_route() !== '/wp/v2/marketplace/plugins' ) return $response;

		if ( is_array($plugins) ) {
			foreach ( $plugins as $key => $plugin ) {
               
				if (isset($plugin['slug']) && isset($plugin['download_link'])) {
					$plugins[$plugin['slug']] = $plugin;
				}
				unset($plugins[$key]);
			}
			$response->set_data( $plugins );
		}

        return $response;
	}

}


function marketplace_plugin_library_register_routes_init()
{
    $controller = new Marketplace_REST_Posts_Controller();
    $controller->register_routes();
}
add_action('rest_api_init', 'marketplace_plugin_library_register_routes_init');
