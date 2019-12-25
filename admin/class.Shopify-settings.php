<?php

class ShopifySettingsPage {
	
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $nameKeys = array();
	

    /**
     * Start up
     */
    public function __construct(){
		$this->setOptionsName();
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_footer', array( $this, 'ajax_call' ),10 );
    }
	
	/*
	 * Set the options name
	 * @array $this->nameKeys
	 * Sections, groups.
	 */
	public function setOptionsName(){
		$this->nameKeys = array(
			'maintitle'		=> 'Shopify Import Settings',
			'menu' => array(
				'title' 		=> 'Shopify Import',
				'slug'			=> 'shopify-import',
				'pagetitle'		=> 'Shopify Import',
				'permissions' 	=> 'manage_options'
			),
			'option' 	=> 'shopifyimport_setting_options',
			'group'		=> 'shopifyimport_option_group',
			'sections'	=> array(
				array(
					'id'	=> 'shopifyimport_section_developers',
					'name'	=> 'shopifyimport-setting-admin',
					'title'	=> 'Shopify API settings'
				)
			)
		);
	}
	
	/**
	 * All setting fields for Shopify Import Sections
	 */
	public function setting_fields($key = ''){
		
		//all settings fields for Shopify Import option page
		$setting_fields = array(
			'shopifyimport_section_developers' => array(
				array('name'=>'api_url', 		'type'=>'text',     'title'=>'API Url'),
				array('name'=>'api_key', 		'type'=>'text',     'title'=>'API Key'),
				array('name'=>'api_token', 		'type'=>'text',     'title'=>'API Token'),
				array('name'=>'ppcall', 		'type'=>'number',   'title'=>'Limit', 		'tip'=>'AJAX call per page!'),
				array('name'=>'start', 			'type'=>'number',   'title'=>'Start From', 	'tip'=>'Start AJAX call from page!' ),
				array('name'=>'verifyapi', 		'type'=>'button',   'title'=>'Verify API',	'tip'=>'Verify your API details before run the import! If you got success message then you can import products.' ),
				array('name'=>'importprdct', 	'type'=>'button',   'title'=>'Run Import',	'tip'=>'Start AJAX call from page!' )
			)
		);
			
		if($key != ''){
			//check if Section fields available
			if(array_key_exists($key,$setting_fields)){
				$setting_fields = $setting_fields[$key];
			}
			else{
				$setting_fields = array();
			}
		}
		
		return $setting_fields;
	}

    /**
     * Add options page
     */
    public function add_plugin_page(){
		
        // This page will be under "Settings"
        add_options_page(
            $this->nameKeys['menu']['pagetitle'], 
            $this->nameKeys['menu']['title'], 
            $this->nameKeys['menu']['permissions'], 
            $this->nameKeys['menu']['slug'], 
            array( $this, 'create_admin_page' )
        );
    }
	
	/**
	 * Print Settings on settings page dynamically
	 */
	public function setting_fields_print($which,$sectionname){
		
		//all settings fields for Shopify Import option page
		$setting_fields = $this->setting_fields($which);
		
		if(sizeof($setting_fields) > 0){
			foreach($setting_fields as $fields){
				
				$tip = (isset($fields['tip']))? $fields['tip'] : '';
				add_settings_field(
					$fields['name'], 
					$fields['title'], 
					array( $this, $fields['type'].'_field_callback' ), 
					$sectionname, 
					$which,
					['name' => $fields['name'], 'id' => $fields['name'].'_field', 'title' => $fields['title'], 'tip' => $tip]
				);
			}
		}
		
	}

    /**
     * Options page callback
     */
    public function create_admin_page(){
		
        // Set class property
        $this->options = get_option( $this->nameKeys['option'] );
		
		// add error/update messages		
		// echo '<pre>'.print_r($this->options,true).'</pre>'; 
        ?>
		
        <div class="wrap">
            <h1><?php echo $this->nameKeys['maintitle']; ?></h1>
            <form method="post" action="options.php">
				<?php
					// This prints out all hidden setting fields
					settings_fields( $this->nameKeys['group'] );
						//Load multiple sections
						foreach($this->nameKeys['sections'] as $section){
							do_settings_sections( $section['name'] );
						}
					submit_button();
				?>
            </form>
			
			<div class="response-products"><ul></ul></div>
			
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init(){        
	
        register_setting(
            $this->nameKeys['group'], // Option group
            $this->nameKeys['option'], // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

		//Load multiple sections
		foreach($this->nameKeys['sections'] as $section){
			add_settings_section(
				$section['id'], // ID
				$section['title'], // Title
				array( $this, 'print_section_info' ), // Callback
				$section['name'] // Page
			);  
			
			//Corcrm setting fields
			$this->setting_fields_print($section['id'],$section['name']);
		}
		
		
    }
	
	/*
	 * Print section info after the title.
	 */
	public function print_section_info(){
		
	}

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ){
        $new_input = array();
        
		$settings_fields_section = $this->setting_fields();
		
		foreach($settings_fields_section as $sfield_section){
			foreach($sfield_section as $sfield){
				if( isset( $sfield['name'] ) && $sfield['type'] == 'text'){
					$new_input[$sfield['name']] = sanitize_text_field( $input[$sfield['name']] );
				}
				if( isset( $input[$sfield['name']] ) && $sfield['type'] == 'number'){
					$new_input[$sfield['name']] = absint( $input[$sfield['name']] );
				}
			}
		}
		
        return $new_input;
    }
	
	/** 
     * Get the settings option for Text fields in settings
     */
	public function text_field_callback($args){
		printf(
            '<input type="text" id="%s" name="%s['.$args['name'].']" value="%s" />',
            $args['id'],
			$this->nameKeys['option'], 
			isset( $this->options[$args['name']] ) ? esc_attr( $this->options[$args['name']]) : ''
        );
		
		$this->printlabel($args);
	}
	
	/** 
     * Get the settings option for Number fields in settings
     */
	public function number_field_callback($args){
		printf(
            '<input type="number" id="%s" name="%s['.$args['name'].']" value="%s" />',
            $args['id'],
			$this->nameKeys['option'],
			isset( $this->options[$args['name']] ) ? esc_attr( $this->options[$args['name']]) : ''
        );
		
		$this->printlabel($args);
	}
	
	/** 
     * Get the settings option for Number fields in settings
     */
	public function button_field_callback($args){
		
		printf(
            '<button id="%s" class="button button-primary" >%s</button>',
            $args['id'], 
			$args['title']
        );
		
		echo '<span class="spinner"></span>';
		
		$this->printlabel($args);
	}

	/*
	 * Print Description for the setting field
	 */
	public function printlabel($args){
		if($args['tip'] != ''){
			printf(
				'<p class="description">%s</p>',$args['tip']
			);
		}
	}
	
	
	/*
	 * @ajax
	 * @admin footer
	 * Ajax call to verify API and import products.
	 */
	public function ajax_call(){
		
		$shopifyOptions =  get_option('shopifyimport_setting_options');
		$limit = isset($shopifyOptions['ppcall'])? $shopifyOptions['ppcall'] : 2;
		$startfrom = isset($shopifyOptions['start'])? $shopifyOptions['start'] : 1;
		$firstURL = add_query_arg(array('importshopify' => 'in091','limit' => $limit,'pagenum' => $startfrom),get_admin_url());
		
		?>
			<script type="text/javascript">
				(function($){
					
					//verify api details for SHOP
					$('#verifyapi_field').click(function(e){
						$(this).next().addClass('is-active');
						e.preventDefault();
						var data = {'action': 'verifyAPI'};
						jQuery.post(ajaxurl, data, function(res) {
							$('.spinner').removeClass('is-active');
							alert(res);
						});
					});
					
					//import products in the site
					$('#importprdct_field').click(function(e){
						e.preventDefault();
						var limit	  = '<?php echo $limit; ?>';
						var startfrom = '<?php echo $startfrom; ?>';
						var firstURL  = '<?php echo $firstURL; ?>';
						$(this).next().addClass('is-active');
						function importAgain(url){
							$.get( url, function( data ) {
								$('.response-products').show();
								// console.log(data);
								if(data.success){
									console.log(data.return_url);
									
									//import all products and the URL again and Again through ajax
									importAgain(data.return_url);
									
									var products = '<li> Products: <b>#'+data.pr_ids+'</b> ';
									var appendData = products+data.return_url+'</li>';
									$('.response-products > ul').append(appendData);
								}
								else if(data.error){
									console.log(data.error);
									var appendData = '<li>'+data.return_url+'</li>';
									$('.response-products > ul').append(appendData);
								}
							},'json');
						}
	
						importAgain(firstURL);
						return false;
					});
					
				}(jQuery));
			</script>
			<style>
				.spinner{ float:left; }
				.response-products {background: lightgreen;padding: 10px;border-radius: 3px;max-height: 500px;overflow-y: scroll;display:none;}
			</style>
		<?php
	}
}