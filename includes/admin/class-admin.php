<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('Marketplace_Admin')) {
    class Marketplace_Admin
    {

        public function __construct()
        {
            add_action('init', array($this, 'init'));
        }
        public function init()
        {
            $this->init_settings();
            // register fields
            add_action('admin_init', array($this, 'register_settings_fields'));
            // create admin setting page
            add_action('admin_menu', array($this, 'add_admin_menus'));

            add_action('admin_head', array($this, 'admin_head') );
        }
        public function init_settings()
        {
            $this->pages = array(
                array(
                    'page_slug' => 'options-general.php',
                    'page_title' => 'General Settings',
                    'menu_title' => 'Market Place',
                    'capability' => 'manage_options',
                    'menu_slug' => 'marketplace-library-general',
                    'function' => array($this, 'menu_page'),
                    'icon_url' => 'dashicons-block-default',
                    'position' => 65
                )
            );
            $this->settings = array(
                array(
                    'option_group' => 'marketplace-library-general',
                    'option_name' => 'marketplace_general',
                    'page' => 'marketplace-library-general',
                ),
                array(
                    'option_group' => 'marketplace-library-general',
                    'option_name' => 'marketplace_testmode',
                    'page' => 'marketplace-library-general'
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
                    'callback' => array($this, 'page_section'),
                    'page' => 'marketplace-library-general'
                )
            );

            $this->fields = array(
                array(
                    'id' => 'marketplace_testmode',
                    'title' => 'Enable Testmode',
                    'callback' => array($this, 'input_field'),
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
                    )
                ),
                array(
                    'id' => 'marketplace_library_authorization_token',
                    'title' => 'Authorization Token',
                    'callback' => array($this, 'input_field'),
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
                    )
                ),
                array(
                    'id' => 'marketplace_library_secret_key',
                    'title' => 'Secret API Key',
                    'callback' => array($this, 'input_field'),
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
                    )
                ),
                array(
                    'id' => 'marketplace_authorization',
                    'title' => 'Authorize Marketplace',
                    'callback' => array($this, 'submit_button'),
                    'page' => 'marketplace-library-general',
                    'section' => 'general',
                    'args' => array(
                        'name' => 'marketplace_authorization',
                        'label_for' => 'marketplace_authorization',
                        'title' => 'Authorize',
                        'class' => 'marketplace',
                        'description' => '',
                        'default' => '',
                        'type' => 'checkbox',
                        'option_group' => 'marketplace_authorization',
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
        public function admin_head()
        {
            ?>
            <style>
                .marketplace.hide-title > th {display: none;}
                .marketplace.hide-title > td {padding-left: 0;padding-right: 0;}
            </style>
            <?php
        }
        public function add_admin_menus()
        {
            foreach ($this->pages as $key => $page) {
                if (isset($page['page_slug'])) {
                    add_submenu_page(
                        $page['page_slug'],
                        $page['page_title'],
                        $page['menu_title'],
                        $page['capability'],
                        $page['menu_slug'],
                        $page['function'],
                        $page['position'],
                    );
                } else {
                    add_menu_page(
                        $page['page_title'],
                        $page['menu_title'],
                        $page['capability'],
                        $page['menu_slug'],
                        $page['function'],
                        $page['icon_url'],
                        $page['position'],
                    );
                }
            }
        }
        public function page_section($args)
        {
            echo '<hr />';
        }
        public function menu_page()
        {

            if (!current_user_can('manage_options')) {
                return;
            }

            // add error/update messages
            settings_errors('marketplace_manager_notices');

            require_once MARKETPLACE_PATH . 'includes/admin/pages/general.php';
        }
       /**
         * Generate a random UUID (version 4).
         *
         * @since 4.7.0
         *
         * @return string UUID.
         */
        public function generate_uuid4() {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0fff ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff )
            );
        }
        public function marketplace_testmode_sanitize( $values )
        {
            if (isset($values) && $values == 'Clear Cache') {
                add_settings_error('marketplace_library_notices', 'marketplace_library_settings_message','Plugin is now in testmode.', 'updated');
            }

            return $values;

        }
        public function marketplace_auth_sanitize( $values )
        {
            error_log(print_r($values,true));
            $admin_url = admin_url( 'authorize-application.php' );
            $auth_url = add_query_arg( array( 
                'app_name' => get_bloginfo() . ': Market Place',
                'app_id' => $this->generate_uuid4(),
                'sitename' => get_bloginfo(),
                'success_url' => admin_url( 'options-general.php?page=marketplace-library-general' )
            ), $admin_url );

            wp_redirect( $auth_url, 301 );
            exit;

        }
        public function input_field($args)
        {
            $option = isset($args['option_group']) ? get_option($args['option_group']) : false;
            if ( isset($args['name']) && $args['option_group'] !== $args['name'] ) {
                $value = (isset($option[$args['name']])) ? $option[$args['name']] : $args['default'];
                $name = isset($args['name']) ? $args['option_group'] . '[' . $args['name'] . ']' : false;
            } else {
                $value = ( $option ) ? $option : $args['default'];
                $name = $args['name'];
            }
            $type = isset($args['type']) ? $args['type'] : 'text';
            $disabled = isset($args['disabled']) && $args['disabled'] ? 'disabled="disabled"' : false;

            if (!$name) return null;

            $attributes = '';
            $attributes_args = array();
            if ($type == 'checkbox') {
                $value = true;
                $check = (isset($option[$args['name']])) ? $option[$args['name']] : $args['default'];
                $attributes_args[] = checked($check, true, false);
                $attributes .= implode(' ', $attributes_args);
            }
            echo '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" value="' . $value . '" ' . $attributes . $disabled .'/>';
        }
        public function submit_button($args)
        {
            $option = isset($args['option_group']) ? get_option($args['option_group']) : false;
            if ( isset($args['name']) && $args['option_group'] !== $args['name'] ) {
                $value = (isset($option[$args['name']])) ? $option[$args['name']] : $args['default'];
                $name = isset($args['name']) ? $args['option_group'] . '[' . $args['name'] . ']' : false;
                $type = isset($args['type']) ? $args['type'] : 'text';
            } else {
                $value = ( $option ) ? $option : $args['default'];
                $name = $args['name'];
                $type = isset($args['type']) ? $args['type'] : 'text';
            }
            $title = isset($args['title']) ? $args['title'] : '';

            if (!$name) return null;

            $attributes = '';
            $attributes_args = array();
            if ($type == 'checkbox') {
                $attributes_args[] = checked($value, true, false);
                $attributes .= implode(' ', $attributes_args);
            }
            submit_button( $title, 'small', $args['name'] );
        }
    }

    new Marketplace_Admin;

}