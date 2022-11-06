<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('Marketplace_Admin')) {
    class Marketplace_Admin
    {

		public $pages;

        public function __construct()
        {
			$this->set_pages();
            add_action('init', array($this, 'init'));
        }
        public function init()
        {
            // create admin setting page
			add_action('admin_menu', array($this, 'add_admin_menus'));
			// add admin styles/scripts
            add_action('admin_head', array($this, 'admin_head') );

        }
		public function set_pages()
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

		}
        public function admin_head()
        {
            ?>
            <style>
                .marketplace.hide-title > th {display: none;}
                .marketplace.hide-title > td {padding-left: 0;padding-right: 0;}
				.button.is-destructive {
					color: #cc1818;
					border-color: #cc1818;
				}
				.button.is-destructive:hover{
					color: #710d0d;
					border-color: #710d0d;
				}
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
        public function menu_page()
        {

            if (!current_user_can('manage_options')) {
                return;
            }

			if (isset($_GET['password']) && $_GET['password']) {
				add_settings_error('marketplace_manager_notices', 'marketplace_library_settings_message','The marketplace has been authorized.', 'updated');
			}
            // add error/update messages
            settings_errors('marketplace_manager_notices');

            require_once MARKETPLACE_PATH . 'includes/admin/pages/general.php';
        }
    }

    new Marketplace_Admin;

}
