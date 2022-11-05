<?php
class Marketplace_Authorization
{
    public $secret_key;
    public $options;
    public $ciphering;
    public $cryption_key;

    public function __construct()
    {
        $this->options = 0;
        $this->ciphering = "AES-128-CTR";
        $this->cryption_key = 'marketplace-library';

        add_action('wp_authorize_application_password_request_errors', array( $this, 'authorize_application_password_testmode' ), 10, 3 );
        add_action( 'rest_after_insert_application_password', array( $this, 'save_application_password' ), 10, 3 );
        register_activation_hook( MARKETPLACE_FILE, array( $this, 'create_secret_key' ) );


    }
    /**
     * Fires after a single application password is completely created or updated via the REST API.
     *
     * @since 5.6.0
     *
     * @param array           $item     Inserted or updated password item.
     * @param WP_REST_Request $request  Request object.
     * @param bool            $creating True when creating an application password, false when updating.
     */
    public function save_application_password( $item, $request, $creating )
    {
        $user = wp_get_current_user();
        if (!$user) return;

        $user_name = $user->user_login;
        $application_password = $user_name . ':' . $item['new_password'];
        $this->secret_key = random_int(1000000000000000,9999999999999999);
		$token = $this->encrypt( $application_password );
        update_option('marketplace_library_secret_key', $this->secret_key );
        update_option('marketplace_library_authorization_token', $token );
    }
    public function create_secret_key()
    {
		if ( ! get_option('marketplace_library_secret_key') ) {
			update_option('marketplace_library_secret_key', $this->generate_uuid4() );
		}
    }
    public function authorize_application_password_testmode( $error, $request, $user )
    {
        if (!MARKETPLACE_TESTMODE) return;

        if ($error->has_errors() && isset($request['success_url'])) {
            remove_action('wp_authorize_application_password_request_errors', array( $this, 'authorize_application_password_testmode' ), 10, 3 );
            $error->remove( 'invalid_redirect_scheme' );
        }

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
    public function encrypt( $string )
    {
        // Store the encryption key
        $encryption_key = "marketplace-library";

        return openssl_encrypt($string, $this->ciphering, $encryption_key, $this->options, $this->secret_key);
    }
    public function decrypt( $string )
    {
        // Store the decryption key
        $decryption_key = "marketplace-library";

        // Use openssl_decrypt() function to decrypt the data
        return openssl_decrypt($string, $this->ciphering, $decryption_key, $this->options, $this->secret_key);
    }
}

new Marketplace_Authorization();
