<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('Marketplace_Settings')) {
    class Marketplace_Settings
    {

		public $Marketplace_Admin_Callbacks;

        public function __construct()
        {
			$this->Marketplace_Admin_Callbacks = new Marketplace_Admin_Callbacks;
            $this->init_settings();
            add_action('init', array($this, 'init'));
        }
        public function init()
        {
            // register fields
            add_action('admin_init', array($this, 'register_settings_fields'));
        }
        public function init_settings()
        {
            $this->settings = array(
                array(
                    'option_group' => 'marketplace-library-general',
                    'option_name' => 'marketplace_general',
                    'page' => 'marketplace-library-general',
                ),
                array(
                    'option_group' => 'marketplace-library-general',
                    'option_name' => 'marketplace_testmode',
                    'page' => 'marketplace-library-general',
					'args' => array(
                        'sanitize_callback' => array( $this, 'marketplace_testmode_sanitize' )
                    ),
                ),
                array(
                    'option_group' => 'marketplace-library-general',
                    'option_name' => 'marketplace_tokens',
                    'page' => 'marketplace-library-general'
                ),
                array(
                    'option_group' => 'marketplace-library-general',
                    'option_name' => 'marketplace_authorization',
                    'page' => 'marketplace-library-general',
                    'args' => array(
                        'sanitize_callback' => array( $this, 'marketplace_auth_sanitize' )
                    ),
                )
            );

            $this->sections = array(
                array(
                    'id' => 'general',
                    'title' => 'General',
                    'callback' => array($this->Marketplace_Admin_Callbacks, 'page_section'),
                    'page' => 'marketplace-library-general'
                )
            );

            $this->fields = array(
                array(
                    'id' => 'marketplace_testmode',
                    'title' => 'Enable Testmode',
                    'callback' => array($this->Marketplace_Admin_Callbacks, 'input_field'),
                    'page' => 'marketplace-library-general',
                    'section' => 'general',
                    'args' => array(
                        'name' => 'marketplace_testmode',
                        'label_for' => 'marketplace_testmode',
                        'title' => 'Enable Testmode',
                        'class' => 'marketplace',
                        'description' => '',
                        'default' => '',
                        'type' => 'checkbox',
                        'option_group' => 'marketplace_testmode',
						'permission_callback' => function () {
							return get_current_user_id() == 1;
						}
                    )
                ),
                array(
                    'id' => 'marketplace_library_authorization_token',
                    'title' => 'Authorization Token',
                    'callback' => array($this->Marketplace_Admin_Callbacks, 'input_field'),
                    'page' => 'marketplace-library-general',
                    'section' => 'general',
                    'args' => array(
                        'name' => 'marketplace_library_authorization_token',
                        'label_for' => 'marketplace_library_authorization_token',
                        'title' => 'Enable Testmode',
                        'class' => 'marketplace',
                        'description' => '',
                        'default' => '',
                        'type' => 'text',
                        'disabled' => true,
                        'option_group' => 'marketplace_library_authorization_token',
						'conditional_callback' => function () {
							return get_option('marketplace_library_authorization_token');
						}
                    )
                ),
                array(
                    'id' => 'marketplace_library_secret_key',
                    'title' => 'Secret API Key',
                    'callback' => array($this->Marketplace_Admin_Callbacks, 'input_field'),
                    'page' => 'marketplace-library-general',
                    'section' => 'general',
                    'args' => array(
                        'name' => 'marketplace_library_secret_key',
                        'label_for' => 'marketplace_library_secret_key',
                        'title' => 'Secret Key',
                        'class' => 'marketplace',
                        'description' => '',
                        'default' => '',
                        'type' => 'text',
                        'disabled' => true,
                        'option_group' => 'marketplace_library_secret_key',
						'conditional_callback' => function () {
							return get_option('marketplace_library_authorization_token');
						}
                    )
                ),
                array(
                    'id' => 'marketplace_authorization',
                    'title' => 'Authorize Marketplace',
                    'callback' => array($this->Marketplace_Admin_Callbacks, 'submit_button'),
                    'page' => 'marketplace-library-general',
                    'section' => 'general',
                    'args' => array(
                        'name' => 'marketplace_authorization',
                        'label_for' => 'marketplace_authorization',
                        'title' => 'Authorize Marketplace',
                        'class' => 'marketplace hide-title',
                        'description' => '',
                        'default' => '',
                        'type' => 'checkbox',
                        'option_group' => 'marketplace_authorization',
						'conditional_callback' => function () {
							return get_option('marketplace_library_authorization_token') ? false : true;
						}
                    )
                )
            );
        }
        public function register_settings_fields()
        {

            if (is_array($this->settings) && !empty($this->settings)) {
                foreach ($this->settings as $key => $setting) {
                    register_setting(
                        $setting['option_group'],
                        $setting['option_name'],
                        isset( $setting['args'] ) ? $setting['args'] : array()
                    );
                }
            }
            if (is_array($this->sections) && !empty($this->sections)) {
                foreach ($this->sections as $key => $section) {
                    add_settings_section(
                        $section['id'],
                        __($section['title']),
                        $section['callback'],
                        $section['page']
                    );
                }
            }
            if (is_array($this->fields) && !empty($this->fields)) {
                foreach ($this->fields as $key => $field) {

					if (isset($field['args']['permission_callback']) && !empty($field['args']['permission_callback']) ) {
						$permission = call_user_func( $field['args']['permission_callback'], $field['args'] );
						if (empty($permission)) {
							continue;
						}
					}

					if (isset($field['args']['conditional_callback']) && !empty($field['args']['conditional_callback']) ) {
						$conditional = call_user_func( $field['args']['conditional_callback'], $field['args'] );
						if (empty($conditional)) {
							continue;
						}
					}

                    add_settings_field(
                        $field['id'],
                        __($field['title']),
                        $field['callback'],
                        $field['page'], // add to this fields
                        $field['section'], // add to this section
                        $field['args']
                    );
                }
            }
        }
        public function marketplace_testmode_sanitize( $values )
        {
			if ( get_option('marketplace_testmode') == $values ) return $values;

            if (isset($values) && $values ) {
                add_settings_error('marketplace_library_notices', 'marketplace_library_settings_message','Plugin is now in testmode.', 'updated');
            } else {
				add_settings_error('marketplace_library_notices', 'marketplace_library_settings_message','Plugin testmode has been deactivated', 'updated');
			}

            return $values;
        }
        public function marketplace_auth_sanitize( $values )
        {
			if (empty($values)) return;

            $admin_url = admin_url( 'authorize-application.php' );
            $auth_url = add_query_arg( array(
                'app_name' => get_bloginfo() . ': Market Place',
                'app_id' => wp_generate_uuid4(),
                'sitename' => get_bloginfo(),
                'success_url' => admin_url( 'options-general.php?page=marketplace-library-general' )
            ), $admin_url );

            wp_redirect( $auth_url, 301 );
            exit;

        }
    }

    new Marketplace_Settings;

}
