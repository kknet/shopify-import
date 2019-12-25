<?php
/*
 * Plugin Name: Shopify Import
 * Author: IPS
 * description: Import all Product from Shopify store to Woocommerce
 * version: 1.0
*/

//Require Shopify SDK for API call
require __DIR__.'/vendor/autoload.php';
use phpish\shopify;

		
Class ShopifyImport{
	
	protected static $_instance = null;
	
	/*
	 * Instance for Singleton method
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/*
	 * Autoload to run plugin code
	 */
	public function __construct() {
		$this->load_setting();
		$this->load_config();
		// add_action( 'init', array($this,'testsetset'), 6 );
		add_action( 'admin_init', array($this,'pluginCode'), 6 );
		
		//load ajax to verify api is working or not
		add_action( 'wp_ajax_verifyAPI', array($this,'verifyAPI') );
	}

	/*
	 * @ Load Shopify Config
	 */
	public function load_config(){
		$shopifyOptions =  get_option('shopifyimport_setting_options');
		if(is_array($shopifyOptions)){
			define('SHOPIFY_APP_SHOP', 		$shopifyOptions['api_url']);
			define('SHOPIFY_APP_API_KEY', 	$shopifyOptions['api_key']);
			define('SHOPIFY_APP_TOKEN', 	$shopifyOptions['api_token']);
		}
		else{
			define('SHOPIFY_APP_SHOP', 		'exodusskateshop.myshopify.com');
			define('SHOPIFY_APP_API_KEY', 	'ae1b6a5856f80488168fabdcd5258a5b');
			define('SHOPIFY_APP_TOKEN', 	'c8584c2e670180f3952552aebfb73773');
		}
	}
	
	/**
	 * Load Hook for Settings Page
	 */
	public function load_setting(){
		//Include Options page in admin
		include_once( plugin_dir_path( __FILE__ ) . '/admin/class.Shopify-settings.php');
		$ShopifySettingsPage = new ShopifySettingsPage();
	}
	
	/**
	 * Load Hook for all Files
	 */
	public function load_files(){
		
		//woocommmerce create and get products files
		include_once('classes/class.createProduct.php');
		include_once('classes/class.getProduct.php');
		
		/* //use admin functions to import product images
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' ); */
	}
	
	/*
	 * Verify API details of the Shopify
	 */
	public function verifyAPI(){
		
		$shopify = shopify\client(SHOPIFY_APP_SHOP, SHOPIFY_APP_API_KEY, SHOPIFY_APP_TOKEN);
		try{
			$totalCount = $shopify('GET /admin/products/count.json', array('published_status'=>'published'));
			echo 'ACTIVE!';
		}
		catch (shopify\ApiException $e){
			# HTTP status code was >= 400 or response contained the key 'errors'
			echo 'Not ACTIVE! \n';
			echo $e;
			print_r($e->getRequest());
			print_r($e->getResponse());
		}
		catch (shopify\CurlException $e){
			# cURL error
			echo 'Not ACTIVE! \n';
			echo $e;
			print_r($e->getRequest());
			print_r($e->getResponse());
		}
		die;
	}
	
	/**
	 * Load Plugin code
	 */
	public function pluginCode(){

		if(isset($_REQUEST['importshopify']) && $_REQUEST['importshopify'] == 'in091' && isset($_REQUEST['pagenum'])){
			
			//Load required files
			$this->load_files();
			
			$shopify = shopify\client(SHOPIFY_APP_SHOP, SHOPIFY_APP_API_KEY, SHOPIFY_APP_TOKEN);

			try{
				$limit 	 = isset($_REQUEST['limit'])? $_REQUEST['limit'] : 2;
				$pagenum = $_REQUEST['pagenum'];
				$totalProducts 	= 1500;
				$totalPages		= 1500/50;
				$productIDs 	= array();
				
				$responseData = $shopify("GET /admin/products.json?limit={$limit}&page={$pagenum}", array('published_status'=>'published'));
				$productClass = new getProductsfromJson($responseData);
				$products = $productClass->getAllProducts();
				
				//all products import
				foreach($products as $product){
					$productsWOO = new createProductsWoo($product);
					$productIDs[] = $productsWOO->pID;
				}
				
				$pagenum++;
				
				//return next page url
				$returnURL = add_query_arg(array('importshopify' => 'in091','limit' => $limit,'pagenum' => $pagenum),get_admin_url());
				$response = array('success' => true, 'return_url' => $returnURL, 'pr_ids' => implode(',',$productIDs));
				echo json_encode($response);
				die();
			}
			catch (shopify\ApiException $e){
				# HTTP status code was >= 400 or response contained the key 'errors'
				/* echo $e;
				print_r($e->getRequest());
				print_r($e->getResponse()); */
				
				$response = array('error' => $e);
				echo json_encode($response);
				die();
			}
			catch (shopify\CurlException $e){
				# cURL error
				/* echo $e;
				print_r($e->getRequest());
				print_r($e->getResponse()); */
				$response = array('error' => $e);
				echo json_encode($response);
				die();
			}

			/* $jsonFileData = file_get_contents(plugin_dir_path(__FILE__).'/jsonfiles/product.json');
			$productClass = new getProductsfromJson($jsonFileData);*/
			
		}
	}
	
	/*
	 * Test function to print data
	 */
	public function testsetset(){
		$query = new WP_Query( array( 'post_type' => 'product_variation') );
		if ( $query->have_posts() ) :
			while ( $query->have_posts() ) : $query->the_post(); 
				global $post;
				the_title();
				// $vunique_id = get_post_meta($post->ID,'attribute_pa_size',true);
				$vunique_id = '11.5M';
				update_post_meta($post->ID, '_unique_id', $vunique_id);
			endwhile; 
			wp_reset_postdata();
		endif;
		
		exit;
	}
	
}

/*
 * Function to load in all files.
 */
function ShopifyImportfunc() {
	return ShopifyImport::instance();
}

// Global for backwards compatibility.
$GLOBALS['shopify_import'] = ShopifyImportfunc();