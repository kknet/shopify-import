<?php
/*
 * Class to Get the all products from API and make a new array to map the fields for woocommerce import.
 */
class generateCSVfromJSON{
	
	public $product = array();
	public $products = array();
	public $productJson;
	public $cProduct;
	
	//Autoload function
	public function __construct($productJson){
		//current product
		$this->productJson = $productJson;
		$this->getProducts();
	}
	
	//Get all Products
	private function getProducts(){
		unset($this->product);
		foreach($this->productJson as $product){
			$this->cProduct = $product;
			$this->init();
			array_push($this->products,$this->product);
		}
	}
	
	
	/*
	 * Get the data for the products and stored into the array
	 * Call the Variations, Tags, Categories, Attrubutes for Simple and Variable Product.
	 */
	private function init(){
		$cProduct = $this->cProduct;
		$this->product['name'] 			= $cProduct['title'];
		$this->product['sku'] 			= $cProduct['id'];
		$this->product['description'] 	= $cProduct['body_html'];
		$this->product['vendor'] 		= $cProduct['vendor'];
		$this->product['product_type'] 	= $cProduct['product_type'];
		$this->product['slug'] 			= $cProduct['handle'];
		$this->product['tags'] 			= $cProduct['tags'];
		$this->product['price'] 		= 0;
		
		$this->getTags();
		$this->getCategories();
		$this->getAvailableAttributes();
		$this->getImages();
		$this->getVariations();
	}
	
	
	/*
	 * Get all attributes for Variable products
	 */
	private function getAvailableAttributes(){
		$avaAttr = '';
		foreach($this->cProduct['options'] as $p_options){
			$avaAttr .= strtolower($p_options['name']).',';
		}
		
		$avaAttr = rtrim($avaAttr,',');
			
		$this->product['available_attributes'] = $avaAttr;
		
	}

	
	/*
	 * Get all Variations for Variable products
	 */
	private function getVariations(){
		
		//Simple Product
		if(isset($this->product['available_attributes']) && $this->product['available_attributes'] == 'title'){
			unset($this->product['available_attributes']);
			
			$this->product['price'] = $this->cProduct['variants'][0]['price'];
			$comparePrice = $this->cProduct['variants'][0]['compare_at_price'];
			if(!empty($comparePrice)){
				$this->product['price'] = $comparePrice;
				$this->product['sale_price'] = $this->cProduct['variants'][0]['price'];
			}
			
			$this->product['qty'] = $this->cProduct['variants'][0]['inventory_quantity'];
			$this->product['weight'] = $this->cProduct['variants'][0]['weight'];
			$this->product['type'] = 'simple';
		}
		else{
			//Variable Product
			
			//get ALl product options
			$Prdoptions = explode(',',$this->product['available_attributes']);
			
			//get ALl attributes
			$attributes = array();
			$attributesArray = array();
			foreach($this->cProduct['variants'] as $p_variation){
				
				$varPrice = $p_variation['price'];
				$varSalePrice = false;
				
				$comparePrice = $p_variation['compare_at_price'];
				if(!empty($comparePrice)){
					$varPrice 		= $comparePrice;
					$varSalePrice 	= $this->cProduct['variants'][0]['price'];
				}
			
				//included for (Size,Material,Color)
				$attributesArray[strtolower($Prdoptions[0])] = $p_variation['option1'];
				
				if(isset($p_variation['option2'])){
					$attributesArray[strtolower($Prdoptions[1])] = $p_variation['option2'];
				}
				if(isset($p_variation['option3'])){
					$attributesArray[strtolower($Prdoptions[2])] = $p_variation['option3'];
				}
				
				$attributes[] = array(
					'attributes' 	=> $attributesArray,
					'price'			=> $varPrice,
					'sale_price'	=> $varSalePrice,
					'weight'		=> $p_variation['weight'],
					'sku'			=> $p_variation['sku'],
					'position'		=> $p_variation['position'],
					'manage_stock'	=> 'yes',
					'qty'			=> $p_variation['inventory_quantity'],
					'unique_id'		=> $p_variation['id']
				);
			}
			
			/* $product['variations'] = array(
				array('attributes' => array('size' => 'Small','color'=>'Red'),'price'=>'8.00')
			); */
			
			$this->product['variations'] = $attributes;
			$this->product['type'] = 'variable';
		}
	}
	
	/*
	 * Get all Images for products
	 */
	public function getImages(){
		$cProduct = $this->cProduct;
		$this->product['image'] 	= $cProduct['image']['src'];
		
		if(!empty($cProduct['images'])){
			$this->product['images'] = array();
			
			$imgno = 0;
			foreach($cProduct['images'] as $imagesA){
				//remove first image because it is already set as featured images
				$imgno++;
				if($imgno == 1){ continue; }
				$this->product['images'][] = $imagesA['src'];
				
			}
		}
		else{
			$this->product['images'] = '';
		}
	}
	
	/*
	 * Get all Categories for products
	 */
	public function getCategories(){
		$cProduct = $this->cProduct;
		$this->product['categories']  = array($cProduct['product_type'],$cProduct['vendor']);
	}	
	
	/*
	 * Get all Tags for products
	 */
	public function getTags(){
		$cProduct = $this->cProduct;
		$this->product['tags']  = $cProduct['tags'];
	}	
	
	/*
	 * Get the last product from the array.
	 */
	public function getLastProduct(){
		return $this->product;
	}
	
	/*
	 * Get all Products function to call directly
	 */
	public function getAllProducts(){
		return $this->products;
	}

}
