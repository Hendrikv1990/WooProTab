<?php 
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	echo 'This plugin required woocommerce installed!';
    exit;
}

if ( !class_exists( 'FMET_Front_Class' ) ) {

	class FMET_Front_Class extends FME_Tabs { 

		public function __construct() {

			$this->front_scripts();
			add_filter( 'woocommerce_product_tabs', array($this,'woocommerce_fme_product_tabs'),98 ); 
		}

		public function front_scripts() {
            
        	wp_enqueue_style( 'FontAwesome-style', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );
        	wp_enqueue_style( 'fme-tabs-css', FMET_URL . 'fme_tabs.css', '', false );
        	
        }


		function woocommerce_fme_product_tabs($tabs) {

			global $post;

			$product_custom_tabs = $this->get_product_custom_tabs($post->ID);

			$product_default_tabs = $this->get_product_default_tabs($post->ID);

			foreach ($product_default_tabs as $dtabs) {
				if($dtabs->tab_id == 1 && $tabs['description'])
				{
					$tabs['description']['title'] = '&'.$dtabs->tab_icon.' '.$dtabs->tab_name;
					$tabs['description']['priority'] = 1; 

				}

				if($dtabs->tab_id == 2 && $tabs['reviews'])
				{
					$tabs['reviews']['title'] = '&'.$dtabs->tab_icon.' '.$dtabs->tab_name;
					$tabs['reviews']['priority'] = 2;
				}


				if($dtabs->tab_id == 3 && $tabs['additional_information'])
				{
					$tabs['additional_information']['title'] = '&'.$dtabs->tab_icon.' '.$dtabs->tab_name;
					$tabs['additional_information']['priority'] = 3;
				}
			}

			foreach ($product_custom_tabs as $ptabs) {
                $tabs['fmeptab_'.$ptabs->tab_id] = array(
                	'icon'    => $ptabs->tab_icon,
                    'title'    => '&'.$ptabs->tab_icon.' '.$ptabs->tab_name,
                    'title2'    => $ptabs->tab_name,
                    'priority' => 50,
                    'callback' => array($this,'render_tab'),
                    'content'  => apply_filters('the_content', $ptabs->tab_description) //this allows shortcodes in custom tabs
                );


            }

            $is_use_gtabs = $this->get_is_enbale_global_tab_product($post->ID);
			if($is_use_gtabs->use_gtabs!='no') {
				
				$product_global_tabs = $this->get_product_global_tabs($post->ID);

					if(count($product_global_tabs)!=0)
					{
						$product_gtabs = $product_global_tabs;
						foreach ($product_gtabs as $gtabs) {

						if($gtabs->status!='update')
						{
							$gtdate = $this->get_globaltab_by_id($gtabs->postid);
							$icon = get_post_meta($gtdate->ID, 'fme_tabs_icon', true);
							$sort_order = get_post_meta($gtdate->ID, 'fme_tab_sort_order', true);

							$tabs['fmegtab_'.$gtabs->tab_id] = array(
		                	'icon'    => $icon,
		                    'title'    => '&'.$icon.' '.$gtdate->post_title,
		                    'title2'    => $gtdate->post_title,
		                    'priority' => $sort_order+3,
		                    'callback' => array($this,'render_gtab'),
		                    'content'  => apply_filters('the_content', $gtdate->post_content) //this allows shortcodes in custom tabs
		                	);
						}
						else
						{
							$tabs['fmegtab_'.$gtabs->tab_id] = array(
		                	'icon'    => $gtabs->tab_icon,
		                    'title'    => '&'.$gtabs->tab_icon.' '.$gtabs->tab_name,
		                    'title2'    => $gtabs->tab_name,
		                    'priority' => $sort_order+3,
		                    'callback' => array($this,'render_gtab'),
		                    'content'  => apply_filters('the_content', $gtabs->tab_description) //this allows shortcodes in custom tabs
		                );

						}


	            		}
	            			
					
					}
				else
				{
					$product_gtabs = $this->get_all_global_tabs();
					foreach ($product_gtabs as $gtabs) {
		            	$icon = get_post_meta($gtabs->ID, 'fme_tabs_icon', true);
		            	$sort_order = get_post_meta($gtabs->ID, 'fme_tab_sort_order', true);
		                $tabs['fmegtab_'.$gtabs->ID] = array(
		                	'icon'    => $icon,
		                    'title'    => '&'.$icon.' '.$gtabs->post_title,
		                    'title2'    => $gtabs->post_title,
		                    'priority' => $sort_order+3,
		                    'callback' => array($this,'render_gtab'),
		                    'content'  => apply_filters('the_content', $gtabs->post_content) //this allows shortcodes in custom tabs
		                );


	            		}
				}
			}



			return $tabs;

		}

		function render_tab($key,$tab){
	        global $post;
	        echo '<h2>'.apply_filters('fme_ptab_title',$tab['title2'],$tab,$key).'</h2>';
	        echo apply_filters('fme_ptab_content',$tab['content'],$tab,$key);
	    }

	    function render_gtab($key,$tab){
	        global $post;
	        echo '<h2>'.apply_filters('fme_gtab_title',$tab['title2'],$tab,$key).'</h2>';
	        echo apply_filters('fme_gtab_content',$tab['content'],$tab,$key);
	    }

		function get_product_custom_tabs($product_id)
		{
			global $wpdb;
	        $result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_tabs WHERE product_id = %d", $product_id ) ); 

	        return $result;
		}

		function get_product_default_tabs($product_id)
		{
			global $wpdb;
	        
	        $res = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_dtabs WHERE product_id = %d", $product_id ) ); 
	        if(count($res)!=0)
	        {
	        	$result = $res;
	        }
	        else
	        {
	        	$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_dtabs", ARRAY_A ) ); 
	        }
	        return $result;
		}


		function get_is_enbale_global_tab_product($product_id)
		{
			global $wpdb;
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_use_gtabs WHERE product_id = %d", $product_id ) );
		
			return $result;
		}

		function get_product_global_tabs($product_id)
		{
			global $wpdb;
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_gtabs WHERE product_id = %d ORDER BY sort_order asc", $product_id ) );
		
			return $result;
		}

		function get_all_global_tabs()
		{
			global $wpdb;
			//$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "posts WHERE post_type = 'fme_tabs' AND post_status='publish'", ARRAY_A ) );
			
			$result = get_posts(array(
				'post_type'			=> 'fme_tabs',
				'post_status'			=> 'publish',
				'posts_per_page'	=> -1,
				'meta_key'			=> 'fme_tab_sort_order',
				'orderby'			=> 'meta_value_num',
				'order'				=> 'DESC'
			));

			return $result;
		}

		function get_globaltab_by_id($id)
		{
			global $wpdb;
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "posts WHERE post_type = 'fme_tabs' AND post_status='publish' AND ID = %d", $id ) );
		
			return $result;
		}

	}


new FMET_Front_Class;

}
?>