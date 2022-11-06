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
		update_user_meta(
			$user->ID,
			'marketplace_library_keys',
			[
				'marketplace_library_secret_key' => $this->secret_key,
				'marketplace_library_authorization_token' => $token
			]
		);
    }
    public function authorize_application_password_testmode( $error, $request, $user )
    {
        if (!MARKETPLACE_TESTMODE) return;

        if ($error->has_errors() && isset($request['success_url'])) {
            remove_action('wp_authorize_application_password_request_errors', array( $this, 'authorize_application_password_testmode' ), 10, 3 );
            $error->remove( 'invalid_redirect_scheme' );
        }

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
