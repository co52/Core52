<?php

class Amazon {
	
	/**
	 * Amazon Web Services Key. Found in the AWS Security Credentials. You can also pass this value as the first
	 * parameter to a service constructor.
	 */
	public static $aws_key;
	
	/**
	 * Amazon Web Services Secret Key. Found in the AWS Security Credentials. You can also pass this value as
	 * the second parameter to a service constructor.
	 */
	public static $aws_secret_key;
	
	/**
	 * Amazon Account ID without dashes. Used for identification with Amazon EC2. Found in the AWS Security
	 * Credentials.
	 */
	public static $aws_account_id;
	
	/**
	 * Your CanonicalUser ID. Used for setting access control settings in AmazonS3. Found in the AWS Security
	 * Credentials.
	 */
	public static $aws_canonical_id;
	
	/**
	 * Your CanonicalUser DisplayName. Used for setting access control settings in AmazonS3. Found in the AWS
	 * Security Credentials (i.e. "Welcome, AWS_CANONICAL_NAME").
	 */
	public static $aws_canonical_name;
	
	/**
	 * Determines which Cerificate Authority file to use.
	 *
	 * A value of boolean `false` will use the Certificate Authority file available on the system. A value of
	 * boolean `true` will use the Certificate Authority provided by the SDK. Passing a file system path to a
	 * Certificate Authority file (chmodded to `0755`) will use that.
	 *
	 * Leave this set to `false` if you're not sure.
	 */
	public static $aws_certificate_authority = FALSE;
	
	/**
	 * This option allows you to configure a preferred storage type to use for caching by default. This can
	 * be changed later using the set_cache_config() method.
	 *
	 * Valid values are: `apc`, `xcache`, a DSN-style string such as `pdo.sqlite:/sqlite/cache.db`, a file
	 * system path such as `./cache` or `/tmp/cache/`, or a serialized array for memcached configuration.
	 *
	 * serialize(array(
	 * 	array(
	 * 		'host' => '127.0.0.1',
	 * 		'port' => '11211'
	 * 	),
	 * 	array(
	 * 		'host' => '127.0.0.2',
	 * 		'port' => '11211'
	 * 	)
	 * ));
	 */
	public static $aws_default_cache_config;
	
	/**
	 * 12-digit serial number taken from the Gemalto device used for Multi-Factor Authentication. Ignore this
	 * if you're not using MFA.
	 */
	public static $aws_mfa_serial;
	
	/**
	 * Amazon CloudFront key-pair to use for signing private URLs. Found in the AWS Security Credentials. This
	 * can be set programmatically with <AmazonCloudFront::set_keypair_id()>.
	 */
	public static $aws_cloudfront_keypair_id;
	
	/**
	 * The contents of the *.pem private key that matches with the CloudFront key-pair ID. Found in the AWS
	 * Security Credentials. This can be set programmatically with <AmazonCloudFront::set_private_key()>.
	 */
	public static $aws_cloudfront_private_key_pem;
	
	/**
	 * Set the value to true to enable autoloading for classes not prefixed with "Amazon" or "CF". If enabled,
	 * load `sdk.class.php` last to avoid clobbering any other autoloaders.
	 */
	public static $aws_enable_extensions = 'false';
	
	
	public static $initialized = FALSE;
	
	
	public static function Initialize($aws_key = NULL, $aws_secret_key = NULL, $aws_account_id = NULL, $aws_canonical_id = NULL, $aws_canonical_name = NULL, $aws_certificate_authority = FALSE, $aws_default_cache_config = NULL, $aws_mfa_serial = NULL, $aws_cloudfront_keypair_id = NULL, $aws_cloudfront_private_key_pem = NULL, $aws_enable_extensions = 'false') {
		
		# don't call more than once
		if(self::$initialized) {
			throw new Exception('Amazon SDK already initialized');
		}
		
		if(is_array($aws_key)) {
			extract($aws_key);
		}
		
		# required params
		self::set_param('aws_key', $aws_key, TRUE, TRUE);
		self::set_param('aws_secret_key', $aws_secret_key, TRUE, TRUE);
		self::set_param('aws_account_id', $aws_account_id, TRUE, TRUE);
		self::set_param('aws_canonical_id', $aws_canonical_id, TRUE, TRUE);
		self::set_param('aws_canonical_name', $aws_canonical_name, TRUE, TRUE);
		
		# optional params
		self::set_param('aws_certificate_authority', $aws_certificate_authority, FALSE, TRUE);
		self::set_param('aws_default_cache_config', $aws_default_cache_config, FALSE, TRUE);
		self::set_param('aws_mfa_serial', $aws_mfa_serial, FALSE, TRUE);
		self::set_param('aws_cloudfront_keypair_id', $aws_cloudfront_keypair_id, FALSE, TRUE);
		self::set_param('aws_cloudfront_private_key_pem', $aws_cloudfront_private_key_pem, FALSE, TRUE);
		self::set_param('aws_enable_extensions', $aws_enable_extensions, FALSE, TRUE);
		
		# since the SDK conditionally declares classes after calling class_exists(),
		# we have to temporarily disable autoloading.
		#AutoClassLoader::Unregister();
		require_once PATH_CORE.'3rdparty/amazon_sdk/sdk-1.4.2.1/sdk.class.php';
		#AutoClassLoader::Register();
		
		self::$initialized = TRUE;
	}
	
	
	private static function set_param($param, $value = NULL, $required = FALSE, $set_const = FALSE) {
		self::${$param} = $value;
		if(is_null($value) && $required == TRUE) {
			throw new InvalidArgumentException("$param is required");
		}
		if($set_const == TRUE) {
			define(strtoupper($param), $value);
		}
	}

	
	/**
	 * Gets an AmazonCloudFront SDK object
	 *
	 * @return AmazonCloudFront
	 */
	public static function CloudFront() {
		if(!self::$initialized) {
			throw new Exception("Amazon SDK not initialized");
		}
		
		return new AmazonCloudFront();
	}
	
	
	/**
	 * Gets an AmazonS3 SDK object
	 *
	 * @return AmazonS3
	 */
	public static function S3() {
		if(!self::$initialized) {
			throw new Exception("Amazon SDK not initialized");
		}
		
		return new AmazonS3();
	}
	

	/**
	 * Formats the CFResponse::$body->AccessControlList->Grant CFSimpleXML object
	 * as a PHP array suitable for using in AmazonS3::set_bucket_acl() or
	 * AmazonS3::set_object_acl() calls
	 *
	 * @param CFSimpleXML $acls  AccessControlList->Grant XML node from a AmazonS3::get_bucket_acl() or AmazonS3::get_object_acl() call response object
	 * @return array
	 */
	public static function S3_format_acl_array(CFSimpleXML $AccessControlList_Grant) {
		$acls = array();
		foreach($AccessControlList_Grant as $acl) {
			$acls[] = array(
				'id' => ($acl->Grantee->ID)? (string) $acl->Grantee->ID : (string) $acl->Grantee->URI,
				'permission' => (string) $acl->Permission,
			);
		}
		return $acls;
	}
	
	
	/**
	 * Merges single or multiple ACLs with an ACL array
	 *
	 * @param array $acls Merge target array of ACLs
	 * @param array $acl  Single ACL or ACL array to merge into $acls
	 * @return array
	 */
	public static function S3_merge_acl_array(array $acls, array $acl) {
		
		if(isset($acl['permission'])) {
			
			# merge a single ACL into $acls
			$acls[] = $acl;
			
			# eliminate duplicate ACLs
			$deduped = array();
			foreach($acls as $i => $a) {
				$checksum = md5(serialize($a));
				$deduped[$checksum] = $a;
			}
			$acls = array_values($deduped);
			
		} else {
			
			# merge multiple ACLs into $acls
			foreach($acl as $a) {
				$acls = self::S3_merge_acl_array($acls, $a);
			}
			
		}
		
		return $acls;
	}
	
	
	/**
	 * Composes an ACL from a CanonicalUserId and permission level string
	 *
	 * @param string $id
	 * @param string $permission
	 * @return array
	 */
	public static function S3_compose_acl($id, $permission) {
		return array(
			'id' => $id,
			'permission' => $permission,
		);
	}
	
	
	public static function response_exception(CFResponse $response) {
		return new AmazonException((string) $response->body->Message, (int) $response->status);
	}
	
	
}

class AmazonException extends Exception {}
