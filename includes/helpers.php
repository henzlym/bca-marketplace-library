<?php
function _marketplace_user_has_auth( $key = '')
{

	$marketplace_library_keys = get_user_meta(
		get_current_user_id(),
		'marketplace_library_keys',
		true
	);

	if (
		empty($marketplace_library_keys)
		|| !isset($marketplace_library_keys['marketplace_library_secret_key'])
		|| !isset($marketplace_library_keys['marketplace_library_authorization_token'])
	){
		return false;
	}

	return $key !== '' ? $marketplace_library_keys[$key] : $marketplace_library_keys;
}
