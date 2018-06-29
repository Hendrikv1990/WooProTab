<?php 

/*
 * Plugin Name:       Product Tabs Pro
 * Plugin URI:        http://fmeaddons.com/wordpress/product-tabs-pro
 * Description:       FME Product Tabs Pro provide the feature to add tabs for products. By using this module admin can add global or indivisual tabs for products.
 * Version:           1.0
 * Author:            FME Addons
 * Developed By:  	  Raja Usman Mehmood
 * Author URI:        http://fmeaddons.com/
 * Text Domain:       product-tabs-pro
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Check if WooCommerce is active
 * if wooCommerce is not active FME Tabs module will not work.
 **/
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	echo 'This plugin required woocommerce installed!';
    exit;
}

if ( !class_exists( 'FME_Tabs' ) ) {

	class FME_Tabs { 

		function __construct()
		{ 
			
			$this->module_constants();
			$this->module_tables();
			
			
			
			if ( is_admin() ) {
				add_action( 'init', array($this, 'fme_tabs_post_type'),0);
				add_action( 'add_meta_boxes', array($this, 'icon_meta_box_add' ));
				add_action( 'save_post', array($this, 'icon_meta_box_save' ));
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

				add_filter('manage_edit-fme_tabs_columns', array($this, 'add_new_fme_tabs_columns'));
				add_action('manage_fme_tabs_posts_custom_column', array($this, 'manage_fme_tabs_columns'), 10, 2);
				add_filter( 'manage_edit-fme_tabs_sortable_columns', array($this, 'fme_tabs_column_register_sortable' ));
				add_filter( 'request', array($this, 'fme_tabs_column_orderby' ));

				add_action('quick_edit_custom_box',  array($this, 'fme_add_quick_edit'), 10, 2);
				add_action('admin_footer', array($this, 'fme_quick_edit_javascript'));
				add_filter('post_row_actions', array($this, 'fme_expand_quick_edit_link'), 10, 2);

				//add product tabs pro link in admin
        		add_action( 'woocommerce_product_write_panel_tabs', array($this,'fme_product_write_panel_tabs' ));
        		//add product tab content in admin
       			add_action('woocommerce_product_write_panels', array($this,'fme_product_write_panels'));
       			add_action('wp_ajax_tab_session', array($this, 'tab_session')); 
       			add_action('wp_ajax_gtab_session', array($this, 'gtab_session')); 
       			add_action('wp_ajax_tab_session_del', array($this, 'tab_session_del')); 
       			add_action('wp_ajax_tab_session_edit', array($this, 'tab_session_edit')); 
       			add_action('wp_ajax_gtab_session_edit', array($this, 'gtab_session_edit')); 
       			add_action('wp_ajax_gtab_submit', array($this, 'gtab_submit')); 
       			add_action('wp_ajax_ptab_submit', array($this, 'ptab_submit')); 
       			add_action('wp_ajax_dtab_submit', array($this, 'dtab_submit')); 
       			add_action('wp_ajax_usegtab_submit', array($this, 'usegtab_submit')); 




            } 

            else
            {
            	require_once( FMET_PLUGIN_DIR . 'fme-tabs-front.php' );
            }



		}

		function fme_add_quick_edit($column_name, $post_type) {
		    if ($column_name != 'fme_tab_sort_order') return;
		    ?>
		    <fieldset class="inline-edit-col-left">
		        <div class="inline-edit-col">
		            <span class="title"><?php _e('Sort Order','FMET'); ?></span>
		            <input id="fme_tab_sort_orderr" type="text" name="fme_tab_sort_order" value=""/>
		        </div>
		    </fieldset>
		     <?php
		}

		function fme_quick_edit_javascript() {
		    global $current_screen;
		    if (($current_screen->post_type != 'fme_tabs')) return;
		 
		    ?>
		<script type="text/javascript">
		function set_myfield_value(fieldValue) { 
		        // refresh the quick menu properly
		        inlineEditPost.revert();
		        console.log(fieldValue);
		        jQuery('#fme_tab_sort_orderr').val(fieldValue);


		}
		</script>
		 <?php 
		}


		function fme_expand_quick_edit_link($actions, $post) {     
		    global $current_screen;     
		    if (($current_screen->post_type != 'fme_tabs')) 
		        return $actions;
		    $myfielvalue = get_post_meta( $post->ID, 'fme_tab_sort_order', TRUE);
		    $actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="';     
		    $actions['inline hide-if-no-js'] .= esc_attr( __( 'Edit this item inline' ) ) . '"';
		    $actions['inline hide-if-no-js'] .= " onclick=\"set_myfield_value('{$myfielvalue}')\" >";
		    $actions['inline hide-if-no-js'] .= __( 'Quick Edit' );
		    $actions['inline hide-if-no-js'] .= '</a>';
		    return $actions;
		}


		function add_new_fme_tabs_columns($gallery_columns) {
		    $new_columns['cb'] = '<input type="checkbox" />';
		    $new_columns['title'] = _x('Title', 'column name');
		    $new_columns['fme_tab_sort_order'] = _x('Sort Order', 'column name');
		    $new_columns['date'] = _x('Date', 'column name');
		 
		    return $new_columns;
		}

		function manage_fme_tabs_columns( $column_name, $post_id ) {
		    if ( 'fme_tab_sort_order' != $column_name )
		        return;

		    $price = get_post_meta($post_id, 'fme_tab_sort_order', true);
		    if ( !$price )
		        $price = __( '0', 'FMET' );

		    echo $price;
		}

		function fme_tabs_column_register_sortable( $columns ) {
		    $columns['fme_tab_sort_order'] = 'fme_tab_sort_order';

		    return $columns;
		}

		function fme_tabs_column_orderby( $vars ) {
		    if ( isset( $vars['orderby'] ) && 'fme_tab_sort_order' == $vars['orderby'] ) {
		        $vars = array_merge( $vars, array(
		            'meta_key' => 'fme_tab_sort_order',
		            'orderby' => 'meta_value_num'
		        ) );
		    }
		 
		    return $vars;
		}

		public function admin_scripts() {	
            
        	wp_enqueue_style( 'FontAwesome-style', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );
        	wp_enqueue_style( 'fme-tabs-css', FMET_URL . 'fme_tabs.css', false );
        	wp_enqueue_script( 'fme-popup', FMET_URL . 'resposive_popup.js', false );
        	
        }


       


        public function module_tables() {
            
			global $wpdb;
			
			$charset_collate = '';
			$wpdb->fmet_temp_tabs = $wpdb->prefix . 'fmet_temp_tabs';
			$wpdb->fmet_temp_gtabs = $wpdb->prefix . 'fmet_temp_gtabs';
			$wpdb->fmet_product_gtabs = $wpdb->prefix . 'fmet_product_gtabs';
			$wpdb->fmet_product_tabs = $wpdb->prefix . 'fmet_product_tabs';
			$wpdb->fmet_temp_dtabs = $wpdb->prefix . 'fmet_temp_dtabs';
			$wpdb->fmet_product_dtabs = $wpdb->prefix . 'fmet_product_dtabs';
			$wpdb->fmet_use_gtabs = $wpdb->prefix . 'fmet_use_gtabs';
			if ( !empty( $wpdb->charset ) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( !empty( $wpdb->collate ) )
				$charset_collate .= " COLLATE $wpdb->collate";	
				
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->fmet_temp_tabs'" ) != $wpdb->fmet_temp_tabs ) {
				$sql = "CREATE TABLE " . $wpdb->fmet_temp_tabs . " (
									 tab_id int(25) NOT NULL auto_increment,
									 ip varchar(255) NULL,
									 tab_name varchar(255) NULL,
									 tab_icon varchar(255) NULL,
									 tab_description text NULL,
									 date date NULL,
									 
									 PRIMARY KEY (tab_id)
									 ) $charset_collate;";
		
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}

			if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->fmet_temp_gtabs'" ) != $wpdb->fmet_temp_gtabs ) {
				$sql = "CREATE TABLE " . $wpdb->fmet_temp_gtabs . " (
									 tab_id int(25) NOT NULL auto_increment,
									 ip varchar(255) NULL,
									 tab_name varchar(255) NULL,
									 tab_icon varchar(255) NULL,
									 tab_description text NULL,
									 date date NULL,
									 status varchar(255) NULL,
									 postid varchar(255) NULL,
									 sort_order varchar(255) NULL,
									 
									 PRIMARY KEY (tab_id)
									 ) $charset_collate;";
		
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}


			if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->fmet_temp_dtabs'" ) != $wpdb->fmet_temp_dtabs ) {
				$sql = "CREATE TABLE " . $wpdb->fmet_temp_dtabs . " (
									 id int(25) NOT NULL auto_increment,
									 tab_id varchar(255) NULL,
									 tab_name varchar(255) NULL,
									 tab_icon varchar(255) NULL,
									 
									 PRIMARY KEY (id)
									 ) $charset_collate;";
		
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}



			if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->fmet_product_gtabs'" ) != $wpdb->fmet_product_gtabs ) {
				$sql1 = "CREATE TABLE " . $wpdb->fmet_product_gtabs . " (
									 tab_id int(25) NOT NULL auto_increment,
									 postid varchar(255) NULL,
									 product_id varchar(255) NULL,
									 tab_name varchar(255) NULL,
									 tab_icon varchar(255) NULL,
									 tab_description text NULL,
									 status varchar(255) NULL,
									 sort_order varchar(255) NULL,
									 
									 PRIMARY KEY (tab_id)
									 ) $charset_collate;";
		
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql1 );
			}


			if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->fmet_product_tabs'" ) != $wpdb->fmet_product_tabs ) {
				$sql1 = "CREATE TABLE " . $wpdb->fmet_product_tabs . " (
									 tab_id int(25) NOT NULL auto_increment,
									 product_id varchar(255) NULL,
									 tab_name varchar(255) NULL,
									 tab_icon varchar(255) NULL,
									 tab_description text NULL,
									 
									 PRIMARY KEY (tab_id)
									 ) $charset_collate;";
		
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql1 );
			}


			if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->fmet_product_dtabs'" ) != $wpdb->fmet_product_dtabs ) {
				$sql1 = "CREATE TABLE " . $wpdb->fmet_product_dtabs . " (
									 id int(25) NOT NULL auto_increment,
									 tab_id varchar(255) NULL,
									 product_id varchar(255) NULL,
									 tab_name varchar(255) NULL,
									 tab_icon varchar(255) NULL,
									 
									 PRIMARY KEY (id)
									 ) $charset_collate;";
		
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql1 );
			}

			if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->fmet_use_gtabs'" ) != $wpdb->fmet_use_gtabs ) {
				$sql1 = "CREATE TABLE " . $wpdb->fmet_use_gtabs . " (
									 id int(25) NOT NULL auto_increment,
									 product_id varchar(255) NULL,
									 use_gtabs varchar(255) NULL,
									 
									 PRIMARY KEY (id)
									 ) $charset_collate;";
		
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql1 );
			}


			


		}


		public function module_constants() {
            
            if ( !defined( 'FMET_URL' ) )
                define( 'FMET_URL', plugin_dir_url( __FILE__ ) );

            if ( !defined( 'FMET_BASENAME' ) )
                define( 'FMET_BASENAME', plugin_basename( __FILE__ ) );

            if ( ! defined( 'FMET_PLUGIN_DIR' ) )
                define( 'FMET_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        }


	        function fme_tabs_post_type() {
	 
		    $labels = array(
		        'name'                => _x( 'Global Tabs', 'Post Type General Name', 'FMET' ),
		        'singular_name'       => _x( 'Global Tabs', 'Post Type Singular Name', 'FMET' ),
		        'menu_name'           => __( 'Product Tabs Pro', 'FMET' ),
		        'parent_item_colon'   => __( 'errt', 'FMET' ),
		        'all_items'           => __( 'Global Tabs', 'FMET' ),
		        'view_item'           => __( '', 'FMET' ),
		        'add_new_item'        => __( 'Add Global Tab', 'FMET' ),
		        'add_new'             => __( 'Add New', 'FMET' ),
		        'edit_item'           => __( 'Edit Global Tab', 'FMET' ),
		        'update_item'         => __( 'Update Global Tab', 'FMET' ),
		        'search_items'        => __( 'Search Global Tab', 'FMET' ),
		        'not_found'           => __( 'Not found', 'FMET' ),
		        'not_found_in_trash'  => __( 'Not found in Trash', 'FMET' ),
		    );
		    $args = array(
		        'label'               => __( 'Product Global Tabs', 'FMET' ),
		        'description'         => __( 'Custom Product Tabs', 'FMET' ),
		        'labels'              => $labels,
		        'supports'            => array( 'title', 'editor',),
		        'hierarchical'        => false,
		        'public'              => true,
		        'show_ui'             => true,
		        'show_in_nav_menus'   => true,
		        'show_in_admin_bar'   => true,
		        'menu_position'       => 5,
		        'menu_icon'           => FMET_URL.'fma.jpg',
		        'can_export'          => true,
		        'has_archive'         => false,
		        'exclude_from_search' => false,
		        'publicly_queryable'  => false,
		        'capability_type'     => 'post',
		    );
		    register_post_type( 'fme_tabs', $args );
 
		}

		


		public function icon_meta_box_add()
		{
		    add_meta_box( 'icon-meta-box-id', 'Tab Icon', array($this, 'icon_meta_box'), 'fme_tabs', 'side', 'default' );
		    add_meta_box( 'fme-tab-sort_order-id', 'Sort Order', array($this, 'fme_tab_sort_order'), 'fme_tabs', 'side', 'default' );
		}

		public function icon_meta_box( $post )
		{
		$values = get_post_custom( $post->ID );
		$selected = isset( $values['fme_tabs_icon'] ) ? esc_attr( $values['fme_tabs_icon'][0] ) : ”;
		    ?>
		    <p>
		    
		        <select style="font-family: 'FontAwesome', Helvetica;" name="icon_meta_box_select" id="icon_meta_box_select" required>
		        	<option value="">Select Tab Icon</option>
		            <option value="#xf069;" <?php selected( $selected, '#xf069;' ); ?>>&#xf069; Astarik</option>
		            <option value="#xf1fe;" <?php selected( $selected, '#xf1fe;' ); ?>>&#xf1fe; Chart</option>
		            <option value="#xf0f3;" <?php selected( $selected, '#xf0f3;' ); ?>>&#xf0f3; Bell</option>
		            <option value="#xf02d;" <?php selected( $selected, '#xf02d;' ); ?>>&#xf02d; Book</option>
		            <option value="#xf02e;" <?php selected( $selected, '#xf02e;' ); ?>>&#xf02e; Bookmark</option>
		            <option value="#xf274;" <?php selected( $selected, '#xf274;' ); ?>>&#xf274; Calander</option>
		            <option value="#xf030;" <?php selected( $selected, '#xf030;' ); ?>>&#xf030; Camera</option>
		            <option value="#xf217;" <?php selected( $selected, '#xf217;' ); ?>>&#xf217; Cart</option>
		            <option value="#xf14a;" <?php selected( $selected, '#xf14a;' ); ?>>&#xf14a; Check</option>
		            <option value="#xf013;" <?php selected( $selected, '#xf013;' ); ?>>&#xf013; Cog</option>
		            <option value="#xf086;" <?php selected( $selected, '#xf086;' ); ?>>&#xf086; Comments</option>
		            <option value="#xf019;" <?php selected( $selected, '#xf019;' ); ?>>&#xf019; Download</option>
		            <option value="#xf0e0;" <?php selected( $selected, '#xf0e0;' ); ?>>&#xf0e0; Envelope</option>
		            <option value="#xf06a;" <?php selected( $selected, '#xf06a;' ); ?>>&#xf06a; Exclamation Circle</option>
		            <option value="#xf071;" <?php selected( $selected, '#xf071;' ); ?>>&#xf071; Exclamation Triangle</option>
		            <option value="#xf06e;" <?php selected( $selected, '#xf06e;' ); ?>>&#xf06e; Eye</option>
		            <option value="#xf1ac;" <?php selected( $selected, '#xf1ac;' ); ?>>&#xf1ac; Fax</option>
		            <option value="#xf008;" <?php selected( $selected, '#xf008;' ); ?>>&#xf008; Film / Video</option>
		            <option value="#xf024;" <?php selected( $selected, '#xf024;' ); ?>>&#xf024; Flag</option>
		            <option value="#xf004;" <?php selected( $selected, '#xf004;' ); ?>>&#xf004; Heart</option>
		            <option value="#xf015;" <?php selected( $selected, '#xf015;' ); ?>>&#xf015; Home</option>
		            <option value="#xf254;" <?php selected( $selected, '#xf254;' ); ?>>&#xf254; Hourglass</option>
		            <option value="#xf03e;" <?php selected( $selected, '#xf03e;' ); ?>>&#xf03e; Image</option>
		            <option value="#xf03c;" <?php selected( $selected, '#xf03c;' ); ?>>&#xf03c; Indent</option>
		            <option value="#xf05a;" <?php selected( $selected, '#xf05a;' ); ?>>&#xf05a; Info</option>
		            <option value="#xf084;" <?php selected( $selected, '#xf084;' ); ?>>&#xf084; Key</option>
		            <option value="#xf0e3;" <?php selected( $selected, '#xf0e3;' ); ?>>&#xf0e3; Legal</option>
		            <option value="#xf1cd;" <?php selected( $selected, '#xf1cd;' ); ?>>&#xf1cd; Life Saver</option>
		            <option value="#xf0eb;" <?php selected( $selected, '#xf0eb;' ); ?>>&#xf0eb; Light Bulb</option>
		            <option value="#xf03a;" <?php selected( $selected, '#xf03a;' ); ?>>&#xf03a; List</option>
		            <option value="#xf041;" <?php selected( $selected, '#xf041;' ); ?>>&#xf041; Map Marker</option>
		            <option value="#xf091;" <?php selected( $selected, '#xf091;' ); ?>>&#xf091; Trophy</option>
		            <option value="#xf0d1;" <?php selected( $selected, '#xf0d1;' ); ?>>&#xf0d1; Truck</option>
		            <option value="#xf02b;" <?php selected( $selected, '#xf02b;' ); ?>>&#xf02b; Tag</option>
		            <option value="#xf03d;" <?php selected( $selected, '#xf03d;' ); ?>>&#xf03d; Video Camera</option>
		            <option value="#xf0ad;" <?php selected( $selected, '#xf0ad;' ); ?>>&#xf0ad; Wrench</option>
		            <option value="#xf166;" <?php selected( $selected, '#xf166;' ); ?>>&#xf166; Youtube</option>
		        </select>


		    </p>
		    <?php    
		}

		public function fme_tab_sort_order( $post )
		{ 
			$values = get_post_custom( $post->ID );
			$selected = isset( $values['fme_tab_sort_order'] ) ? esc_attr( $values['fme_tab_sort_order'][0] ) : 0;
		?>
			<input type="text" name="fme_tab_sort_order" id="fme_tab_sort_order" value="<?php echo $selected; ?>">
		<?php }


		function icon_meta_box_save( $post_id )
		{ 

		    if( isset( $_POST['icon_meta_box_select'] ) ) {

		        update_post_meta( $post_id, 'fme_tabs_icon', esc_attr( $_POST['icon_meta_box_select'] ) );
		    }

		    if( isset( $_POST['fme_tab_sort_order'] ) ) {

		        update_post_meta( $post_id, 'fme_tab_sort_order', esc_attr( $_POST['fme_tab_sort_order'] ) );
		    }

		    if (isset($_POST['fme_tab_sort_order']) && ($post->post_type != 'revision')) {
		        $my_fieldvalue = esc_attr($_POST['fme_tab_sort_order']);
		        if ($my_fieldvalue)
		            update_post_meta( $post_id, 'fme_tab_sort_order', $my_fieldvalue);
		        else
		            delete_post_meta( $post_id, 'fme_tab_sort_order');
		    }
		    return $my_fieldvalue;
		     	
		}


		/**
	     * Used to add a product tabs pro link to product add / edit screen
	     * @return void
	    */
	    function fme_product_write_panel_tabs() {
	        ?>
	        <li class="fme_tab">
	            <a href="#fme_tab_data">
	                <?php _e('Product Tabs', 'FMET'); ?>
	            </a>
	        </li>
	        <?php
	    }


	    



	     /**
	     * Used to display a product tabs pro tab content (fields) to product add / edit screen
	     * @return void
	     */
	    function fme_product_write_panels() { 
	    	$this->add_more_js();
	    	$this->delete_all_session_tabs();
	    	$this->module_gtabs();
	    	
	    	?>
	        <div id="fme_tab_data" class="panel woocommerce_options_panel fmetabsarea">


	        	<div id="pdtabs">
	        		<h2>Default Tabs</h2>
	        		<div class="hlp">
	        			<img class="help_tip" data-tip='<?php _e( 'Default Tabs are by default tabs of WooCommerce these tabs are created with each product. You can change tab icon and title from here.', 'FMET' ) ?>' src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16" />
	        		</div>
	        		<form action="" method="post" id="dtabsform" name="dtabsform">
		        		<div id="p_dtabs">
		        			<?php $this->dtab_session_html(); ?>
		        		</div>
	        		</form>

	        	
			   </div>

	        	<div id="pgtabs">
	        	<h2>Global Tabs</h2>
	        	<div class="hlp">
	        			<img class="help_tip" data-tip='<?php _e( 'Global Tabs are created in Global Tabs section, by default these tabs are come with each product, but you can choose which tab to show and overwrite its icon, title and description here.', 'FMET' ) ?>' src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16" />
	        	</div>
	        		<div class="gt">
	        			<?php  
	        			if((isset($_GET['action']) && $_GET['action']=='edit'))
			        		{
			        			if(isset($_GET['post']) && $_GET['post']!='')
			        			{
			        				$product_id = $_GET['post'];
			        			} else $product_id = 0;

			        			global $wpdb;
	           					$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_use_gtabs WHERE product_id = '".$product_id."'", ARRAY_A ) ); 
	           					if($result->use_gtabs == 'yes')
	           					{ ?>
	           						<input checked="checked" type="checkbox" name="useglobaltabs" id="useglobaltabs" value="yes" />
	           					<?php } elseif($result->use_gtabs == 'no') { ?>
	           						<input type="checkbox" name="useglobaltabs" id="useglobaltabs" value="yes" />
	           					<?php } else { ?>
	           						<input checked="checked" type="checkbox" name="useglobaltabs" id="useglobaltabs" value="yes" />
	           					<?php } ?>

	           					<?php 
			        		} else {
	        			?>
	        				<input checked="checked" type="checkbox" name="useglobaltabs" id="useglobaltabs" value="yes" />
	        			<?php } ?>
	        			Want to use global tabs?
	        			<img class="help_tip" data-tip='<?php _e( 'If you do not want to use global tabs with this product then uncheck this checkbox', 'FMET' ) ?>' src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16" />
	        		</div>
	        		<div id="p_gtabs">
	        			<?php $this->gtab_session_html(); ?>
	        		</div>

	        	
			   </div>

			   
			   

	           <h2>Product Tabs</h2>
	           	   <div class="hlp">
	        			<img class="help_tip" data-tip='<?php _e( 'Product Tabs are specific tabs for this product. These tabs will only show with the current product on the frontend.', 'FMET' ) ?>' src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16" />
	        		</div>
		           <div id="p_tabs">
			           <?php $this->tab_session_html(); ?>
		           	</div>

	           <div class="ftabcustom">
	           		<div class="add_tab_bt"><span class="preview button add-box my_modal_open"><span class="fontas_bt">&#xf055;</span> Add Custom Tab</span></div>
	           </div>

	           <div id="my_modal" class="content">
			     <a href="#" class="my_modal_close"><img src="<?php echo FMET_URL; ?>close.png" alt="" border="0" /></a>
			      <form action="#" method="post" id="form">
			      <p><h2>Product Tab</h2></p>
			      <p>
			      
			      	<div class="tabsform">
           				<div class="tab_icon">
           					<b>Tab Icon:</b><br />
           					<select class="select" style="font-family: 'FontAwesome', Helvetica;" name="icon_meta_box_select" id="icon_meta_box" required>
					        	<option value="">Select Tab Icon</option>
					            <option value="#xf069;" <?php selected( $selected, '#xf069;' ); ?>>&#xf069; Astarik</option>
					            <option value="#xf1fe;" <?php selected( $selected, '#xf1fe;' ); ?>>&#xf1fe; Chart</option>
					            <option value="#xf0f3;" <?php selected( $selected, '#xf0f3;' ); ?>>&#xf0f3; Bell</option>
					            <option value="#xf02d;" <?php selected( $selected, '#xf02d;' ); ?>>&#xf02d; Book</option>
					            <option value="#xf02e;" <?php selected( $selected, '#xf02e;' ); ?>>&#xf02e; Bookmark</option>
					            <option value="#xf274;" <?php selected( $selected, '#xf274;' ); ?>>&#xf274; Calander</option>
					            <option value="#xf030;" <?php selected( $selected, '#xf030;' ); ?>>&#xf030; Camera</option>
					            <option value="#xf217;" <?php selected( $selected, '#xf217;' ); ?>>&#xf217; Cart</option>
					            <option value="#xf14a;" <?php selected( $selected, '#xf14a;' ); ?>>&#xf14a; Check</option>
					            <option value="#xf013;" <?php selected( $selected, '#xf013;' ); ?>>&#xf013; Cog</option>
					            <option value="#xf086;" <?php selected( $selected, '#xf086;' ); ?>>&#xf086; Comments</option>
					            <option value="#xf019;" <?php selected( $selected, '#xf019;' ); ?>>&#xf019; Download</option>
					            <option value="#xf0e0;" <?php selected( $selected, '#xf0e0;' ); ?>>&#xf0e0; Envelope</option>
					            <option value="#xf06a;" <?php selected( $selected, '#xf06a;' ); ?>>&#xf06a; Exclamation Circle</option>
					            <option value="#xf071;" <?php selected( $selected, '#xf071;' ); ?>>&#xf071; Exclamation Triangle</option>
					            <option value="#xf06e;" <?php selected( $selected, '#xf06e;' ); ?>>&#xf06e; Eye</option>
					            <option value="#xf1ac;" <?php selected( $selected, '#xf1ac;' ); ?>>&#xf1ac; Fax</option>
					            <option value="#xf008;" <?php selected( $selected, '#xf008;' ); ?>>&#xf008; Film / Video</option>
					            <option value="#xf024;" <?php selected( $selected, '#xf024;' ); ?>>&#xf024; Flag</option>
					            <option value="#xf004;" <?php selected( $selected, '#xf004;' ); ?>>&#xf004; Heart</option>
					            <option value="#xf015;" <?php selected( $selected, '#xf015;' ); ?>>&#xf015; Home</option>
					            <option value="#xf254;" <?php selected( $selected, '#xf254;' ); ?>>&#xf254; Hourglass</option>
					            <option value="#xf03e;" <?php selected( $selected, '#xf03e;' ); ?>>&#xf03e; Image</option>
					            <option value="#xf03c;" <?php selected( $selected, '#xf03c;' ); ?>>&#xf03c; Indent</option>
					            <option value="#xf05a;" <?php selected( $selected, '#xf05a;' ); ?>>&#xf05a; Info</option>
					            <option value="#xf084;" <?php selected( $selected, '#xf084;' ); ?>>&#xf084; Key</option>
					            <option value="#xf0e3;" <?php selected( $selected, '#xf0e3;' ); ?>>&#xf0e3; Legal</option>
					            <option value="#xf1cd;" <?php selected( $selected, '#xf1cd;' ); ?>>&#xf1cd; Life Saver</option>
					            <option value="#xf0eb;" <?php selected( $selected, '#xf0eb;' ); ?>>&#xf0eb; Light Bulb</option>
					            <option value="#xf03a;" <?php selected( $selected, '#xf03a;' ); ?>>&#xf03a; List</option>
					            <option value="#xf041;" <?php selected( $selected, '#xf041;' ); ?>>&#xf041; Map Marker</option>
					            <option value="#xf091;" <?php selected( $selected, '#xf091;' ); ?>>&#xf091; Trophy</option>
					            <option value="#xf0d1;" <?php selected( $selected, '#xf0d1;' ); ?>>&#xf0d1; Truck</option>
					            <option value="#xf02b;" <?php selected( $selected, '#xf02b;' ); ?>>&#xf02b; Tag</option>
					            <option value="#xf03d;" <?php selected( $selected, '#xf03d;' ); ?>>&#xf03d; Video Camera</option>
					            <option value="#xf0ad;" <?php selected( $selected, '#xf0ad;' ); ?>>&#xf0ad; Wrench</option>
					            <option value="#xf166;" <?php selected( $selected, '#xf166;' ); ?>>&#xf166; Youtube</option>
					        </select>

           				</div>
           				<div class="tab_input">
           					<b>Tab Title:</b><br />
           					<input type="text" name="fme_product_custom_tab" class="" style="width:100%;" id="tabtitle" />
           				</div>
           				<div class="tab_content"> 
           					<b>Tab Description:</b><br />
           					<?php $editor_id = "addfmetabcontent"; if (!empty( $_POST["fme_custom_tab_content"] ) ) { $content = esc_attr( html_entity_decode(stripslashes( $_POST["fme_custom_tab_content"] ) )); } $settings = array(  "textarea_name" => "fme_custom_tab_content", "textarea_rows" => "20", 'editor_height' => 425 ); wp_editor( $content, $editor_id, $settings ); ?> 
           				</div>
           				<input type="hidden" name="tab_id" id="tab_id" value="">
       				</div>

			      </p>
			      <p>
			      	<input id="submit" class="button button-primary button-large" type="button" value="Save" name="save">
			      	&nbsp;
			      	<input id="cancel" class="button button-primary button-large" type="button" value="Cancel" name="cancel">
			      </p>
			      
			      </form>
			      
			  </div>



			  <div id="my_gmodal" class="content">
			     <a href="#" class="my_gmodal_close"><img src="<?php echo FMET_URL; ?>close.png" alt="" border="0" /></a>
			      <form action="#" method="post" id="form">
			      <p><h2>Global Tab</h2></p>
			      <p>
			      
			      	<div class="tabsform">
           				<div class="tab_icon">
           					<b>Tab Icon:</b><br />
           					<select class="select" style="font-family: 'FontAwesome', Helvetica;" name="icon_meta_box_select" id="gicon_meta_box" required>
					        	<option value="">Select Tab Icon</option>
					            <option value="#xf069;" <?php selected( $selected, '#xf069;' ); ?>>&#xf069; Astarik</option>
					            <option value="#xf1fe;" <?php selected( $selected, '#xf1fe;' ); ?>>&#xf1fe; Chart</option>
					            <option value="#xf0f3;" <?php selected( $selected, '#xf0f3;' ); ?>>&#xf0f3; Bell</option>
					            <option value="#xf02d;" <?php selected( $selected, '#xf02d;' ); ?>>&#xf02d; Book</option>
					            <option value="#xf02e;" <?php selected( $selected, '#xf02e;' ); ?>>&#xf02e; Bookmark</option>
					            <option value="#xf274;" <?php selected( $selected, '#xf274;' ); ?>>&#xf274; Calander</option>
					            <option value="#xf030;" <?php selected( $selected, '#xf030;' ); ?>>&#xf030; Camera</option>
					            <option value="#xf217;" <?php selected( $selected, '#xf217;' ); ?>>&#xf217; Cart</option>
					            <option value="#xf14a;" <?php selected( $selected, '#xf14a;' ); ?>>&#xf14a; Check</option>
					            <option value="#xf013;" <?php selected( $selected, '#xf013;' ); ?>>&#xf013; Cog</option>
					            <option value="#xf086;" <?php selected( $selected, '#xf086;' ); ?>>&#xf086; Comments</option>
					            <option value="#xf019;" <?php selected( $selected, '#xf019;' ); ?>>&#xf019; Download</option>
					            <option value="#xf0e0;" <?php selected( $selected, '#xf0e0;' ); ?>>&#xf0e0; Envelope</option>
					            <option value="#xf06a;" <?php selected( $selected, '#xf06a;' ); ?>>&#xf06a; Exclamation Circle</option>
					            <option value="#xf071;" <?php selected( $selected, '#xf071;' ); ?>>&#xf071; Exclamation Triangle</option>
					            <option value="#xf06e;" <?php selected( $selected, '#xf06e;' ); ?>>&#xf06e; Eye</option>
					            <option value="#xf1ac;" <?php selected( $selected, '#xf1ac;' ); ?>>&#xf1ac; Fax</option>
					            <option value="#xf008;" <?php selected( $selected, '#xf008;' ); ?>>&#xf008; Film / Video</option>
					            <option value="#xf024;" <?php selected( $selected, '#xf024;' ); ?>>&#xf024; Flag</option>
					            <option value="#xf004;" <?php selected( $selected, '#xf004;' ); ?>>&#xf004; Heart</option>
					            <option value="#xf015;" <?php selected( $selected, '#xf015;' ); ?>>&#xf015; Home</option>
					            <option value="#xf254;" <?php selected( $selected, '#xf254;' ); ?>>&#xf254; Hourglass</option>
					            <option value="#xf03e;" <?php selected( $selected, '#xf03e;' ); ?>>&#xf03e; Image</option>
					            <option value="#xf03c;" <?php selected( $selected, '#xf03c;' ); ?>>&#xf03c; Indent</option>
					            <option value="#xf05a;" <?php selected( $selected, '#xf05a;' ); ?>>&#xf05a; Info</option>
					            <option value="#xf084;" <?php selected( $selected, '#xf084;' ); ?>>&#xf084; Key</option>
					            <option value="#xf0e3;" <?php selected( $selected, '#xf0e3;' ); ?>>&#xf0e3; Legal</option>
					            <option value="#xf1cd;" <?php selected( $selected, '#xf1cd;' ); ?>>&#xf1cd; Life Saver</option>
					            <option value="#xf0eb;" <?php selected( $selected, '#xf0eb;' ); ?>>&#xf0eb; Light Bulb</option>
					            <option value="#xf03a;" <?php selected( $selected, '#xf03a;' ); ?>>&#xf03a; List</option>
					            <option value="#xf041;" <?php selected( $selected, '#xf041;' ); ?>>&#xf041; Map Marker</option>
					            <option value="#xf091;" <?php selected( $selected, '#xf091;' ); ?>>&#xf091; Trophy</option>
					            <option value="#xf0d1;" <?php selected( $selected, '#xf0d1;' ); ?>>&#xf0d1; Truck</option>
					            <option value="#xf02b;" <?php selected( $selected, '#xf02b;' ); ?>>&#xf02b; Tag</option>
					            <option value="#xf03d;" <?php selected( $selected, '#xf03d;' ); ?>>&#xf03d; Video Camera</option>
					            <option value="#xf0ad;" <?php selected( $selected, '#xf0ad;' ); ?>>&#xf0ad; Wrench</option>
					            <option value="#xf166;" <?php selected( $selected, '#xf166;' ); ?>>&#xf166; Youtube</option>
					        </select>

           				</div>
           				<div class="tab_input">
           					<b>Tab Title:</b><br />
           					<input type="text" name="fme_product_custom_tab" class="" style="width:100%;" id="gtabtitle" />
           				</div>
           				<div class="tab_content"> 
           				<b>Tab Description:</b><br />
           					<?php $editor_id = "gaddfmetabcontent"; if (!empty( $_POST["fme_custom_tab_content"] ) ) { $content = esc_attr( html_entity_decode(stripslashes( $_POST["fme_custom_tab_content"] )) ); } $settings = array(  "textarea_name" => "fme_custom_tab_content", "textarea_rows" => "20", 'editor_height' => 425 ); wp_editor( $content, $editor_id, $settings ); ?> 
           				</div>
           				<input type="hidden" name="gtab_id" id="gtab_id" value="">
           				<input type="hidden" name="gtabstatus" id="gtabstatus">
       				</div>

			      </p>
			      <p>
			      	<input id="gsubmit" class="button button-primary button-large" type="button" value="Save" name="save">
			      	&nbsp;
			      	<input id="gcancel" class="button button-primary button-large" type="button" value="Cancel" name="cancel">
			      	 	
			      	

			      </p>
			      
			      </form>
			      
			  </div>

	        </div>
	        <?php }


	        function tab_session() {
	        	
	        	$tab_id = $_POST['tab_id']; 
				$icon = $_POST['icon'];
				$title = $_POST['title'];
				$content = stripslashes($_POST['content']);
				$date = date('Y-m-d');
				$ip = $_SERVER['REMOTE_ADDR'];
				global $wpdb;

			if($icon!='' && $title!='') {
			if(isset($_POST['edit']) && $_POST['edit']=='edit')
    		{
    			if(isset($_POST['post']) && $_POST['post']!='')
    			{
    				$product_id = $_POST['post'];
    			} else $product_id = 0;

    			if($tab_id=='') {
				$wpdb->insert(
					$wpdb->prefix . 'fmet_temp_tabs',
					array(
						'tab_icon' => $icon,
						'tab_name' => $title,
						'tab_description' => $content,
						'date' => $date,
						'ip' => $ip
					),
					array(
						'%s','%s','%s','%s', '%s'
					)
				);
			}
			else
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "fmet_product_tabs
                                    SET 
                                    tab_name = '".$title."', 
                                    tab_icon = '".$icon."',
                                    tab_description = '".$content."'
                                    WHERE tab_id = ".$tab_id, $tab_id)); 
			}

    		}
    		
    		else {

			if($tab_id=='') {
				$wpdb->insert(
					$wpdb->prefix . 'fmet_temp_tabs',
					array(
						'tab_icon' => $icon,
						'tab_name' => $title,
						'tab_description' => $content,
						'date' => $date,
						'ip' => $ip
					),
					array(
						'%s','%s','%s','%s',' %s'
					)
				);
			}
			else
			{ 
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "fmet_temp_tabs
                                    SET 
                                    tab_name = '".$title."', 
                                    tab_icon = '".$icon."',
                                    tab_description = '".$content."',
                                    date = '".$date."',
                                    ip = '".$ip."'
                                    WHERE tab_id = ".$tab_id, $tab_id)); 
			}
		
		}
	}



				$this->tab_session_html();
				die();
				return true;
				
				
			
			}



			function gtab_session() {
	        	
	        	$tab_id = $_POST['tab_id']; 
				$icon = $_POST['icon'];
				$title = $_POST['title'];
				$content = stripslashes($_POST['content']);
				$status = $_POST['gtabstatus'];
				$date = date('Y-m-d');
				$ip = $_SERVER['REMOTE_ADDR'];
				global $wpdb;

			if($icon!='' && $title!='') {
			if(isset($_POST['edit']) && $_POST['edit']=='edit')
    		{
    			if(isset($_POST['post']) && $_POST['post']!='')
    			{
    				$product_id = $_POST['post'];
    			} else $product_id = 0;

    			if($tab_id=='') {
				$wpdb->insert(
					$wpdb->prefix . 'fmet_temp_gtabs',
					array(
						'tab_icon' => $icon,
						'tab_name' => $title,
						'tab_description' => $content,
						'date' => $date,
						'ip' => $ip,
						'status' => $status
					),
					array(
						'%s','%s','%s','%s', '%s', '%s'
					)
				);
			}
			else
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "fmet_product_gtabs
                                    SET 
                                    tab_name = '".$title."', 
                                    tab_icon = '".$icon."',
                                    tab_description = '".$content."',
                                    status = '".$status."'
                                    WHERE tab_id = ".$tab_id, $tab_id)); 

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "fmet_temp_gtabs
                                    SET 
                                    tab_name = '".$title."', 
                                    tab_icon = '".$icon."',
                                    tab_description = '".$content."',
                                    date = '".$date."',
                                    ip = '".$ip."',
                                    status = '".$status."'
                                    WHERE tab_id = ".$tab_id, $tab_id));
			}

    		}


    		
    		else {

			if($tab_id=='') {
				$wpdb->insert(
					$wpdb->prefix . 'fmet_temp_gtabs',
					array(
						'tab_icon' => $icon,
						'tab_name' => $title,
						'tab_description' => $content,
						'date' => $date,
						'ip' => $ip,
						'status' => $status
					),
					array(
						'%s','%s','%s','%s',' %s', '%s'
					)
				);
			}
			else
			{ 
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "fmet_temp_gtabs
                                    SET 
                                    tab_name = '".$title."', 
                                    tab_icon = '".$icon."',
                                    tab_description = '".$content."',
                                    date = '".$date."',
                                    ip = '".$ip."',
                                    status = '".$status."'
                                    WHERE tab_id = ".$tab_id, $tab_id)); 
			}
		
		}
		}



				$this->gtab_session_html();
				die();
				return true;
				
				
			
			}

			function tab_session_del()
			{
				$tab_id = $_POST['tab_id'];
				global $wpdb;
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix . "fmet_temp_tabs WHERE tab_id = %d", $tab_id ) );
				die();
				return true;
			}

			function tab_session_edit()
			{
				$tab_id = $_POST['tab_id'];
				global $wpdb;

				if(isset($_POST['edit']) && $_POST['edit']=='edit' && $_POST['temptab']=='')
	        		{ 
	        			if(isset($_POST['post']) && $_POST['post']!='')
	        			{
	        				$product_id = $_POST['post'];
	        			} else $product_id = 0;
	        			
	        			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_tabs WHERE tab_id = '".$tab_id."' AND product_id = '".$product_id."'", ARRAY_A ) );
	        		}
	        		else
	        		{

						$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_tabs WHERE tab_id = %d", $tab_id ) );
					}
				echo json_encode($result);
				exit();
			}

			function gtab_session_edit()
			{
				$tab_id = $_POST['tab_id'];
				global $wpdb;

				if(isset($_POST['edit']) && $_POST['edit']=='edit' && $_POST['tempgtab']=='')
	        		{ 
	        			if(isset($_POST['post']) && $_POST['post']!='')
	        			{
	        				$product_id = $_POST['post'];
	        			} else $product_id = 0;
	        			
	        			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_gtabs WHERE tab_id = '".$tab_id."' AND product_id = '".$product_id."'", ARRAY_A ) );
	        		}
	        		else
	        		{

						$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_gtabs WHERE tab_id = %d", $tab_id ) );
					}
				echo json_encode($result);
				exit();
			}

			function delete_all_session_tabs()
			{ 
				global $wpdb;
				$date = date('Y-m-d');
				$ip = $_SERVER['REMOTE_ADDR'];
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix . "fmet_temp_tabs WHERE date = '".$date."' AND ip = '".$ip."'", ARRAY_A));
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix . "fmet_temp_gtabs WHERE date = '".$date."' AND ip = '".$ip."'", ARRAY_A));
			}


			function get_all_session_tabs()
			{
				global $wpdb;
				$date = date('Y-m-d');
				$ip = $_SERVER['REMOTE_ADDR'];
	           	$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_tabs WHERE date = '".$date."' AND ip = '".$ip."'", ARRAY_A ) ); 

	           	return $result;
			}

			function gtab_submit()
			{
				
				global $wpdb;
				$last = $wpdb->get_row("SHOW TABLE STATUS LIKE 'wp_posts'");
        		$productid = ($last->Auto_increment)-1;

        		if(isset($_POST['edit']) && $_POST['edit']=='edit')
	        		{
	        			if(isset($_POST['post']) && $_POST['post']!='')
	        			{
	        				$product_id = $_POST['post'];
	        			} else $product_id = 0;

        				$wpdb->query( $wpdb->prepare( "DELETE FROM " .$wpdb->prefix . "fmet_product_gtabs WHERE product_id = %d", $product_id ) );
        				if($_POST['globaltabs']!='')
						{
							
	                        foreach ($_POST['globaltabs'] as $gtabs) {
                         	
                                
                                $chk = explode('-_-',$gtabs);
                                	if($chk[1]!='') {
	                                $result = $wpdb->query( 
	                                    $wpdb->prepare( 
	                                            "
	                                            INSERT INTO ".$wpdb->prefix . "fmet_product_gtabs
	                                            (product_id, postid, tab_name, tab_icon, tab_description, status, sort_order)
	                                            VALUES (%s, %s, %s, %s, %s, %s, %s)
	                                            ",
	                                            $product_id,
	                                            $chk[0],
	                                            $chk[1],
	                                            $chk[2],
	                                            stripslashes($chk[3]),
	                                            $chk[4],
	                                            $chk[5]
	                                            
	                                            )
	                                       );
	                            }
                                
                                
                                }
	                            
						}

        			} else 
        			{
				if($_POST['globaltabs']!='')
					{
						
                        foreach ($_POST['globaltabs'] as $gtabs) {
                         	
                                
                                $chk = explode('-_-',$gtabs);
                                	if($chk[1]!='') {
	                                $result = $wpdb->query( 
	                                    $wpdb->prepare( 
	                                            "
	                                            INSERT INTO ".$wpdb->prefix . "fmet_product_gtabs
	                                            (product_id, postid, tab_name, tab_icon, tab_description, status, sort_order)
	                                            VALUES (%s, %s, %s, %s, %s, %s, %s)
	                                            ",
	                                            $productid,
	                                            $chk[0],
	                                            $chk[1],
	                                            $chk[2],
	                                            stripslashes($chk[3]),
	                                            $chk[4],
	                                            $chk[5]
	                                            
	                                            )
	                                       );
	                            }
                                
                                
                                }
                            
					}
				}

				$date = date('Y-m-d');
				$ip = $_SERVER['REMOTE_ADDR'];
				$wpdb->query( $wpdb->prepare( "DELETE FROM " .$wpdb->prefix . "fmet_temp_gtabs WHERE date = '".$date."' AND ip = '".$ip."'", ARRAY_A ) );

				die();
				return true;
			}


			function dtab_submit()
			{
				
				global $wpdb;
				$last = $wpdb->get_row("SHOW TABLE STATUS LIKE 'wp_posts'");
        		$productid = ($last->Auto_increment)-1;

        		if(isset($_POST['edit']) && $_POST['edit']=='edit')
	        		{
	        			if(isset($_POST['post']) && $_POST['post']!='')
	        			{
	        				$product_id = $_POST['post'];
	        			} else $product_id = 0;

        				$wpdb->query( $wpdb->prepare( "DELETE FROM " .$wpdb->prefix . "fmet_product_dtabs WHERE product_id = %d", $product_id ) );
        				if($_POST['dicon1']!='' && $_POST['dicon2']!='' && $_POST['dicon3']!='')
						{
							
	                        $result = $wpdb->query( 
	                        $wpdb->prepare( 
	                                "
	                                INSERT INTO ".$wpdb->prefix . "fmet_product_dtabs
	                                (tab_id, product_id, tab_name, tab_icon)
	                                VALUES (%s, %s, %s, %s)
	                                ",
	                                '1',
	                                $product_id,
	                                $_POST['dtitle1'],
	                                $_POST['dicon1']
	                                
	                                )
	                           );
	                       $result = $wpdb->query( 
	                        $wpdb->prepare( 
	                                "
	                                INSERT INTO ".$wpdb->prefix . "fmet_product_dtabs
	                                (tab_id, product_id, tab_name, tab_icon)
	                                VALUES (%s, %s, %s, %s)
	                                ",
	                                '2',
	                                $product_id,
	                                $_POST['dtitle2'],
	                                $_POST['dicon2']
	                                
	                                )
	                           );
	                       $result = $wpdb->query( 
	                        $wpdb->prepare( 
	                                "
	                                INSERT INTO ".$wpdb->prefix . "fmet_product_dtabs
	                                (tab_id, product_id, tab_name, tab_icon)
	                                VALUES (%s, %s, %s, %s)
	                                ",
	                                '3',
	                                $product_id,
	                                $_POST['dtitle3'],
	                                $_POST['dicon3']
	                                
	                                )
	                           );
	                            
						}

        			} else 
        			{
				if($_POST['dicon1']!='' && $_POST['dicon2']!='' && $_POST['dicon3']!='')
					{
						
                       $result = $wpdb->query( 
                        $wpdb->prepare( 
                                "
                                INSERT INTO ".$wpdb->prefix . "fmet_product_dtabs
                                (tab_id, product_id, tab_name, tab_icon)
                                VALUES (%s, %s, %s, %s)
                                ",
                                '1',
                                $productid,
                                $_POST['dtitle1'],
                                $_POST['dicon1']
                                
                                )
                           );
                       $result = $wpdb->query( 
                        $wpdb->prepare( 
                                "
                                INSERT INTO ".$wpdb->prefix . "fmet_product_dtabs
                                (tab_id, product_id, tab_name, tab_icon)
                                VALUES (%s, %s, %s, %s)
                                ",
                                '2',
                                $productid,
                                $_POST['dtitle2'],
                                $_POST['dicon2']
                                
                                )
                           );
                       $result = $wpdb->query( 
                        $wpdb->prepare( 
                                "
                                INSERT INTO ".$wpdb->prefix . "fmet_product_dtabs
                                (tab_id, product_id, tab_name, tab_icon)
                                VALUES (%s, %s, %s, %s)
                                ",
                                '3',
                                $productid,
                                $_POST['dtitle3'],
                                $_POST['dicon3']
                                
                                )
                           );
                            
					}
				}


				die();
				return true;
			}

			function ptab_submit()
			{
				global $wpdb;
				$last = $wpdb->get_row("SHOW TABLE STATUS LIKE 'wp_posts'");
        		$productid = ($last->Auto_increment)-1;

        		if(isset($_POST['edit']) && $_POST['edit']=='edit')
	        		{
	        			if(isset($_POST['post']) && $_POST['post']!='')
	        			{
	        				$product_id = $_POST['post'];
	        			} else $product_id = 0;

        				$wpdb->query( $wpdb->prepare( "DELETE FROM " .$wpdb->prefix . "fmet_product_tabs WHERE product_id = %d", $product_id ) );
        				if($_POST['producttabs']!='')
						{
							
	                        foreach ($_POST['producttabs'] as $ptabs) {
	                         	
	                                $chk = explode('-_-',$ptabs);
	                                $result = $wpdb->query( 
	                                    $wpdb->prepare( 
	                                            "
	                                            INSERT INTO ".$wpdb->prefix . "fmet_product_tabs
	                                            (product_id, tab_name, tab_icon, tab_description)
	                                            VALUES (%s, %s, %s, %s)
	                                            ",
	                                            $product_id,
	                                            $chk[0],
	                                            $chk[1],
	                                            stripslashes($chk[2])
	                                            
	                                            )
	                                       );
	                                
	                                
	                                
	                                }
	                            
						}

        			} else 
        			{
				if($_POST['producttabs']!='')
					{
						
                        foreach ($_POST['producttabs'] as $ptabs) {
                         	
                                
                                $chk = explode('-_-',$ptabs);
	                                $result = $wpdb->query( 
	                                    $wpdb->prepare( 
	                                            "
	                                            INSERT INTO ".$wpdb->prefix . "fmet_product_tabs
	                                            (product_id, tab_name, tab_icon, tab_description)
	                                            VALUES (%s, %s, %s, %s)
	                                            ",
	                                            $productid,
	                                            $chk[0],
	                                            $chk[1],
	                                            stripslashes($chk[2])
	                                            
	                                            )
	                                       );
                                
                                
                                }
                            
					}
				}
				$date = date('Y-m-d');
				$ip = $_SERVER['REMOTE_ADDR'];
				$wpdb->query( $wpdb->prepare( "DELETE FROM " .$wpdb->prefix . "fmet_temp_tabs WHERE date = '".$date."' AND ip = '".$ip."'", ARRAY_A ) );
				die();
				return true;
			}


			function usegtab_submit()
			{
				global $wpdb;
				$last = $wpdb->get_row("SHOW TABLE STATUS LIKE 'wp_posts'");
        		$productid = ($last->Auto_increment)-1;

        		if(isset($_POST['edit']) && $_POST['edit']=='edit')
	        		{
	        			if(isset($_POST['post']) && $_POST['post']!='')
	        			{
	        				$product_id = $_POST['post'];
	        			} else $product_id = 0;
	        			//$wpdb->query( $wpdb->prepare( "DELETE FROM " .$wpdb->prefix . "fmet_use_gtabs WHERE product_id = '".$product_id."'", ARRAY_A ) );
	        			
	        			$res = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_use_gtabs WHERE product_id = '".$product_id."'", ARRAY_A ) ); 

	        			if(count($res) == 0)
	        			{
	        				$result = $wpdb->query( 
	                        $wpdb->prepare( 
	                                "
	                                INSERT INTO ".$wpdb->prefix . "fmet_use_gtabs
	                                (product_id, use_gtabs)
	                                VALUES (%s, %s)
	                                ",
	                                $product_id,
	                                $_POST['usegtab']
	                                
	                                )
	                           );

	        			} else {

	        			$result = $wpdb->query(
	        					 $wpdb->prepare("UPDATE ".$wpdb->prefix . "fmet_use_gtabs
                                    SET 
                                    use_gtabs = '".$_POST['usegtab']."'
                                    WHERE product_id = ".$product_id, $product_id)); 
	        			}


        				

        			} else 
        			{
						$result = $wpdb->query( 
                        $wpdb->prepare( 
                                "
                                INSERT INTO ".$wpdb->prefix . "fmet_use_gtabs
                                (product_id, use_gtabs)
                                VALUES (%s, %s)
                                ",
                                $productid,
                                $_POST['usegtab']
                                
                                )
                           );
					}
				
				die();
				return true;
			}

			function getproductglobaltab($product_id, $postid)
			{
				global $wpdb;
	           	$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_gtabs WHERE product_id = '".$product_id."' AND postid = '".$postid."'", ARRAY_A ) ); 

	           	return $result;
			}

			function getProductTabs($product_id)
			{
				global $wpdb;
	           	$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_tabs WHERE product_id = '".$product_id."'", ARRAY_A ) ); 

	           	return $result;
			}

			function getProductGlobalTabs($product_id)
			{
				global $wpdb;
	           	$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_gtabs WHERE product_id = '".$product_id."' AND status = 'update'", ARRAY_A ) ); 

	           	return $result;
			}

			function module_gtabs()
			{
				global $wpdb;
				$gtabs = $this->GlobalTabs();
				$date = date('Y-m-d');
				$ip = $_SERVER['REMOTE_ADDR'];
				$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_gtabs WHERE date = '".$date."' AND ip = '".$ip."'", ARRAY_A ) ); 
				if(count($result) == 0) {
					foreach ($gtabs as $gtab) {
						
						$icon = get_post_meta($gtab->ID, 'fme_tabs_icon', true);
						$sort_order = get_post_meta($gtab->ID, 'fme_tab_sort_order', true);
						$wpdb->insert(
						$wpdb->prefix . 'fmet_temp_gtabs',
						array(
							'tab_icon' => $icon,
							'tab_name' => $gtab->post_title,
							'tab_description' => $gtab->post_content,
							'date' => $date,
							'ip' => $ip,
							'status' => $gtab->status,
							'postid' => $gtab->ID,
							'sort_order' => $sort_order
						),
						array(
							'%s','%s','%s','%s',' %s', '%s', '%s', '%s'
						)
						);
					} 
					
				}
				$result1 = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_dtabs", ARRAY_A ) ); 
				if(count($result1) == 0) {

					$wpdb->insert(
						$wpdb->prefix . 'fmet_temp_dtabs',
						array(
							'tab_id' => '1',
							'tab_icon' => '#xf069;',
							'tab_name' => 'Description'
						),
						array(
							'%s','%s','%s'
						)
						);

					$wpdb->insert(
						$wpdb->prefix . 'fmet_temp_dtabs',
						array(
							'tab_id' => '2',
							'tab_icon' => '#xf086;',
							'tab_name' => 'Reviews'
						),
						array(
							'%s','%s','%s'
						)
						);

					$wpdb->insert(
						$wpdb->prefix . 'fmet_temp_dtabs',
						array(
							'tab_id' => '3',
							'tab_icon' => '#xf05a;',
							'tab_name' => 'Additional Information'
						),
						array(
							'%s','%s' ,'%s'
						)
						);

				}
			}

			function GlobalTabs()
			{
				global $wpdb;
				$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "posts WHERE post_type = 'fme_tabs' AND post_status = 'publish'", ARRAY_A ) ); 

	           	return $result;
			}

			
			function tab_session_html()
			{ 
				
				if(isset($_GET['action']) && $_GET['action']=='edit')
	        		{
	        			if(isset($_GET['post']) && $_GET['post']!='')
	        			{
	        				$product_id = $_GET['post'];
	        			} else $product_id = 0;

	        			$protabs = $this->getProductTabs($product_id);
	        			foreach($protabs as $protab)
	        			{ ?>

	        		<div class="ftab" id="ftab<?php echo $protab->tab_id; ?>">
	           			<span class="fontas">&<?php echo $protab->tab_icon; ?></span>
	           			<span class="ftext"><?php echo $protab->tab_name; ?></span>
	           			<span class="preview button" onClick="javascript:del('<?php echo $protab->tab_id; ?>','<?php echo $protab->tab_name; ?>')">Remove</span>
	           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           			<span class="button preview my_modal_open" onClick="javascript:editprotab('<?php echo $protab->tab_id; ?>')">Edit</span>
	           			<input type="hidden" name="ptabid[]" value='<?php echo $protab->tab_name; ?>-_-<?php echo $protab->tab_icon; ?>-_-<?php echo stripslashes($protab->tab_description); ?>'>
	           		</div>

	        		<?php } }

	        		if(isset($_POST['edit']) && $_POST['edit']=='edit')
	        		{
	        			if(isset($_POST['post']) && $_POST['post']!='')
	        			{
	        				$product_id = $_POST['post'];
	        			} else $product_id = 0;

	        			$protabs = $this->getProductTabs($product_id);
	        			foreach($protabs as $protab)
	        			{ ?>

	        		<div class="ftab" id="ftab<?php echo $protab->tab_id; ?>">
	           			<span class="fontas">&<?php echo $protab->tab_icon; ?></span>
	           			<span class="ftext"><?php echo $protab->tab_name; ?></span>
	           			<span class="preview button" onClick="javascript:del('<?php echo $protab->tab_id; ?>','<?php echo $protab->tab_name; ?>')">Remove</span>
	           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           			<span class="button preview my_modal_open" onClick="javascript:editprotab('<?php echo $protab->tab_id; ?>')">Edit</span>
	           			<input type="hidden" name="ptabid[]" value='<?php echo $protab->tab_name; ?>-_-<?php echo $protab->tab_icon; ?>-_-<?php echo stripslashes($protab->tab_description); ?>'>
	           		</div>

	        		<?php } }

				$all_tabs = $this->get_all_session_tabs();
	           		 if($all_tabs!='') {
	           		 foreach ($all_tabs as $tab) { ?>

	           		 <div class="ftab" id="ftab<?php echo $tab->tab_id; ?>">
	           			<span class="fontas">&<?php echo $tab->tab_icon; ?></span>
	           			<span class="ftext"><?php echo $tab->tab_name; ?></span>
	           			<span class="preview button" onClick="javascript:del('<?php echo $tab->tab_id; ?>','<?php echo $tab->tab_name; ?>')">Remove</span>
	           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           			<span class="button preview my_modal_open" onClick="javascript:edittab('<?php echo $tab->tab_id; ?>')">Edit</span>
	           			<input type="hidden" name="ptabid[]" value='<?php echo $tab->tab_name; ?>-_-<?php echo $tab->tab_icon; ?>-_-<?php echo stripslashes($tab->tab_description); ?>'>
	           			
	           		</div>
	           		 
	           	<?php } }


			} 

			function gtab_session_html()
			{
				global $wpdb;
	    		$date = date('Y-m-d');
				$ip = $_SERVER['REMOTE_ADDR'];
				$res = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_gtabs WHERE `date` = '".$date."' AND `ip` = '".$ip."' ORDER BY sort_order", ARRAY_A ) );
				if (count($res)!=0) { 

	        	foreach ($res as $post) : 
	        	if((isset($_GET['action']) && $_GET['action']=='edit'))
	        		{
	        			if(isset($_GET['post']) && $_GET['post']!='')
	        			{
	        				$product_id = $_GET['post'];
	        			} else $product_id = 0;

	        			$pgtab = $this->getproductglobaltab($product_id, $post->postid);

	        			if($pgtab->postid == $post->postid && $pgtab->status == 'update') {
	        			?>

	        			<div class="ftab" id="gtab<?php echo $pgtab->tab_id; ?>">
			           		<form action="#" method="post" id="globalform">
			           			<span class="fontas">&<?php echo $pgtab->tab_icon; ?></span>
			           			<span class="ftext"><?php echo $pgtab->tab_name; ?></span>
			           			<span class="fsort"><?php echo $post->sort_order; ?></span>
			           			<span class="preview">
			           				<input <?php checked($post->postid, $pgtab->postid); ?> type="checkbox" name="gtabid[]" value='<?php echo $pgtab->postid; ?>-_-<?php echo $pgtab->tab_name; ?>-_-<?php echo $pgtab->tab_icon; ?>-_-<?php echo $pgtab->tab_description; ?>-_-<?php echo $pgtab->status; ?>-_-<?php echo $post->sort_order; ?>'>
			           			</span>
			           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           					<span class="button preview my_gmodal_open" onClick="javascript:editprogtab('<?php echo $pgtab->tab_id; ?>')">Edit</span>

			           		</form>
			            </div>

	        			<?php } elseif($pgtab->postid == $post->postid) { ?>
		        		
		        		<div class="ftab" id="gtab<?php echo $pgtab->tab_id; ?>">
			           		<form action="#" method="post" id="globalform">
			           			<span class="fontas">&<?php echo $pgtab->tab_icon; ?></span>
			           			<span class="ftext"><?php echo $pgtab->tab_name; ?></span>
			           			<span class="fsort"><?php echo $post->sort_order; ?></span>
			           			<span class="preview">
			           				<input <?php checked($post->postid, $pgtab->postid); ?> type="checkbox" name="gtabid[]" value='<?php echo $pgtab->postid; ?>-_-<?php echo $pgtab->tab_name; ?>-_-<?php echo $pgtab->tab_icon; ?>-_-<?php echo $pgtab->tab_description; ?>-_-<?php echo $pgtab->status; ?>-_-<?php echo $post->sort_order; ?>'>
			           			</span>
			           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           					<span class="button preview my_gmodal_open" onClick="javascript:editprogtab('<?php echo $pgtab->tab_id; ?>')">Edit</span>

			           		</form>
			            </div>

	        			<?php } else { ?>

	        			<div class="ftab" id="gtab<?php echo $post->tab_id; ?>">
			           		<form action="#" method="post" id="globalform">
			           			<span class="fontas">&<?php echo $post->tab_icon; ?></span>
			           			<span class="ftext"><?php echo $post->tab_name; ?></span>
			           			<span class="fsort"><?php echo $post->sort_order; ?></span>
			           			<span class="preview">
			           				<input <?php checked($post->postid, $pgtab->postid); ?> type="checkbox" name="gtabid[]" value='<?php echo $post->postid; ?>-_-<?php echo $post->tab_name; ?>-_-<?php echo $post->tab_icon; ?>-_-<?php echo $post->tab_description; ?>-_-<?php echo $post->status; ?>-_-<?php echo $post->sort_order; ?>'>
			           			</span>
			           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           					<span class="button preview my_gmodal_open" onClick="javascript:editgtab('<?php echo $post->tab_id; ?>')">Edit</span>

			           		</form>
			            </div>


	        		 <?php } }

	        		 elseif((isset($_POST['edit']) && $_POST['edit']=='edit'))
	        		{
	        			if(isset($_POST['post']) && $_POST['post']!='')
	        			{
	        				$product_id = $_POST['post'];
	        			} else $product_id = 0;

	        			$pgtab = $this->getproductglobaltab($product_id, $post->postid);

	        			if($pgtab->postid == $post->postid && $pgtab->status == 'update') {
	        			?>

	        			<div class="ftab" id="gtab<?php echo $pgtab->tab_id; ?>">
			           		<form action="#" method="post" id="globalform">
			           			<span class="fontas">&<?php echo $pgtab->tab_icon; ?></span>
			           			<span class="ftext"><?php echo $pgtab->tab_name; ?></span>
			           			<span class="fsort"><?php echo $post->sort_order; ?></span>
			           			<span class="preview">
			           				<input <?php checked($post->postid, $pgtab->postid); ?> type="checkbox" name="gtabid[]" value='<?php echo $pgtab->postid; ?>-_-<?php echo $pgtab->tab_name; ?>-_-<?php echo $pgtab->tab_icon; ?>-_-<?php echo $pgtab->tab_description; ?>-_-<?php echo $pgtab->status; ?>-_-<?php echo $post->sort_order; ?>'>
			           			</span>
			           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           					<span class="button preview my_gmodal_open" onClick="javascript:editprogtab('<?php echo $pgtab->tab_id; ?>')">Edit</span>

			           		</form>
			            </div>

	        			<?php } elseif($pgtab->postid == $post->postid) { ?>
		        		
		        		<div class="ftab" id="gtab<?php echo $pgtab->tab_id; ?>">
			           		<form action="#" method="post" id="globalform">
			           			<span class="fontas">&<?php echo $pgtab->tab_icon; ?></span>
			           			<span class="ftext"><?php echo $pgtab->tab_name; ?></span>
			           			<span class="fsort"><?php echo $post->sort_order; ?></span>
			           			<span class="preview">
			           				<input <?php checked($post->postid, $pgtab->postid); ?> type="checkbox" name="gtabid[]" value='<?php echo $pgtab->postid; ?>-_-<?php echo $pgtab->tab_name; ?>-_-<?php echo $pgtab->tab_icon; ?>-_-<?php echo $pgtab->tab_description; ?>-_-<?php echo $pgtab->status; ?>-_-<?php echo $post->sort_order; ?>'>
			           			</span>
			           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           					<span class="button preview my_gmodal_open" onClick="javascript:editprogtab('<?php echo $pgtab->tab_id; ?>')">Edit</span>

			           		</form>
			            </div>

	        			<?php } else { ?>

	        			<div class="ftab" id="gtab<?php echo $post->tab_id; ?>">
			           		<form action="#" method="post" id="globalform">
			           			<span class="fontas">&<?php echo $post->tab_icon; ?></span>
			           			<span class="ftext"><?php echo $post->tab_name; ?></span>
			           			<span class="fsort"><?php echo $post->sort_order; ?></span>
			           			<span class="preview">
			           				<input <?php checked($post->postid, $pgtab->postid); ?> type="checkbox" name="gtabid[]" value='<?php echo $post->postid; ?>-_-<?php echo $post->tab_name; ?>-_-<?php echo $post->tab_icon; ?>-_-<?php echo $post->tab_description; ?>-_-<?php echo $post->status; ?>-_-<?php echo $post->sort_order; ?>'>
			           			</span>
			           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           					<span class="button preview my_gmodal_open" onClick="javascript:editgtab('<?php echo $post->tab_id; ?>')">Edit</span>

			           		</form>
			            </div>


	        		 <?php } }

	        		  else {
	        	?>
	           <div class="ftab" id="gtab<?php echo $post->tab_id; ?>">
	           		<form action="#" method="post" id="globalform">
	           			<span class="fontas">&<?php echo $post->tab_icon; ?></span>
	           			<span class="ftext"><?php echo $post->tab_name; ?></span>
	           			<span class="fsort"><?php echo $post->sort_order; ?></span>
	           			<span class="preview">
	           				<input checked="checked" type="checkbox" name="gtabid[]" value='<?php echo $post->postid; ?>-_-<?php echo $post->tab_name; ?>-_-<?php echo $post->tab_icon; ?>-_-<?php echo $post->tab_description; ?>-_-<?php echo $post->status; ?>-_-<?php echo $post->sort_order; ?>'>
	           			</span>
	           			<span class="preview">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
	           			<span class="button preview my_gmodal_open" onClick="javascript:editgtab('<?php echo $post->tab_id; ?>')">Edit</span>

	           		</form>
	           </div>
	           <?php } ?>
	           <?php endforeach ?>
	           <?php } else { ?>
					<?php _e('No Global Tab Found!', 'FMET'); ?>
			   <?php } 
			}

			function dtab_session_html()
			{
				global $wpdb;
				if((isset($_GET['action']) && $_GET['action']=='edit'))
	        		{
	        			if(isset($_GET['post']) && $_GET['post']!='')
	        			{
	        				$product_id = $_GET['post'];
	        			} else $product_id = 0;

	        		$res2 = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_product_dtabs WHERE product_id = %d", $product_id ) );	
	        		
	        		if(count($res2)!=0)
	        		{
	        			$res = $res2;
	        		}
	        		else 
	        		{
	        			$res = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_dtabs", ARRAY_A ) );
	        		}
	        		} else {

					$res = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix . "fmet_temp_dtabs", ARRAY_A ) );
					}
				foreach ($res as $post) { ?>

				<div class="ftab" id="dtab<?php echo $post->tab_id; ?>">
	           			<span class="fontas">&<?php echo $post->tab_icon; ?></span>
	           			<span class="ftext"><?php echo $post->tab_name; ?></span>
	           			<span class="button preview" onClick="dtabopen('<?php echo $post->tab_id; ?>')">Edit</span>
	            </div>

	            <div class="dtab" id="dform<?php echo $post->tab_id ?>">

	            	<p>
			      
			      	<div class="tabsform">
           				<div class="tab_icon">
           					<b>Tab Icon:</b><br />
           					<select class="select" style="font-family: 'FontAwesome', Helvetica;" name="dicon" id="dicon<?php echo $post->tab_id ?>" required>
					        	<option value="">Select Tab Icon</option>
					            <option value="#xf069;" <?php selected($post->tab_icon, '#xf069;' ); ?>>&#xf069; Astarik</option>
					            <option value="#xf1fe;" <?php selected($post->tab_icon, '#xf1fe;' ); ?>>&#xf1fe; Chart</option>
					            <option value="#xf0f3;" <?php selected($post->tab_icon, '#xf0f3;' ); ?>>&#xf0f3; Bell</option>
					            <option value="#xf02d;" <?php selected($post->tab_icon, '#xf02d;' ); ?>>&#xf02d; Book</option>
					            <option value="#xf02e;" <?php selected($post->tab_icon, '#xf02e;' ); ?>>&#xf02e; Bookmark</option>
					            <option value="#xf274;" <?php selected($post->tab_icon, '#xf274;' ); ?>>&#xf274; Calander</option>
					            <option value="#xf030;" <?php selected($post->tab_icon, '#xf030;' ); ?>>&#xf030; Camera</option>
					            <option value="#xf217;" <?php selected($post->tab_icon, '#xf217;' ); ?>>&#xf217; Cart</option>
					            <option value="#xf14a;" <?php selected($post->tab_icon, '#xf14a;' ); ?>>&#xf14a; Check</option>
					            <option value="#xf013;" <?php selected($post->tab_icon, '#xf013;' ); ?>>&#xf013; Cog</option>
					            <option value="#xf086;" <?php selected($post->tab_icon, '#xf086;' ); ?>>&#xf086; Comments</option>
					            <option value="#xf019;" <?php selected($post->tab_icon, '#xf019;' ); ?>>&#xf019; Download</option>
					            <option value="#xf0e0;" <?php selected($post->tab_icon, '#xf0e0;' ); ?>>&#xf0e0; Envelope</option>
					            <option value="#xf06a;" <?php selected($post->tab_icon, '#xf06a;' ); ?>>&#xf06a; Exclamation Circle</option>
					            <option value="#xf071;" <?php selected($post->tab_icon, '#xf071;' ); ?>>&#xf071; Exclamation Triangle</option>
					            <option value="#xf06e;" <?php selected($post->tab_icon, '#xf06e;' ); ?>>&#xf06e; Eye</option>
					            <option value="#xf1ac;" <?php selected($post->tab_icon, '#xf1ac;' ); ?>>&#xf1ac; Fax</option>
					            <option value="#xf008;" <?php selected($post->tab_icon, '#xf008;' ); ?>>&#xf008; Film / Video</option>
					            <option value="#xf024;" <?php selected($post->tab_icon, '#xf024;' ); ?>>&#xf024; Flag</option>
					            <option value="#xf004;" <?php selected($post->tab_icon, '#xf004;' ); ?>>&#xf004; Heart</option>
					            <option value="#xf015;" <?php selected($post->tab_icon, '#xf015;' ); ?>>&#xf015; Home</option>
					            <option value="#xf254;" <?php selected($post->tab_icon, '#xf254;' ); ?>>&#xf254; Hourglass</option>
					            <option value="#xf03e;" <?php selected($post->tab_icon, '#xf03e;' ); ?>>&#xf03e; Image</option>
					            <option value="#xf03c;" <?php selected($post->tab_icon, '#xf03c;' ); ?>>&#xf03c; Indent</option>
					            <option value="#xf05a;" <?php selected($post->tab_icon, '#xf05a;' ); ?>>&#xf05a; Info</option>
					            <option value="#xf084;" <?php selected($post->tab_icon, '#xf084;' ); ?>>&#xf084; Key</option>
					            <option value="#xf0e3;" <?php selected($post->tab_icon, '#xf0e3;' ); ?>>&#xf0e3; Legal</option>
					            <option value="#xf1cd;" <?php selected($post->tab_icon, '#xf1cd;' ); ?>>&#xf1cd; Life Saver</option>
					            <option value="#xf0eb;" <?php selected($post->tab_icon, '#xf0eb;' ); ?>>&#xf0eb; Light Bulb</option>
					            <option value="#xf03a;" <?php selected($post->tab_icon, '#xf03a;' ); ?>>&#xf03a; List</option>
					            <option value="#xf041;" <?php selected($post->tab_icon, '#xf041;' ); ?>>&#xf041; Map Marker</option>
					            <option value="#xf091;" <?php selected($post->tab_icon, '#xf091;' ); ?>>&#xf091; Trophy</option>
					            <option value="#xf0d1;" <?php selected($post->tab_icon, '#xf0d1;' ); ?>>&#xf0d1; Truck</option>
					            <option value="#xf02b;" <?php selected($post->tab_icon, '#xf02b;' ); ?>>&#xf02b; Tag</option>
					            <option value="#xf03d;" <?php selected($post->tab_icon, '#xf03d;' ); ?>>&#xf03d; Video Camera</option>
					            <option value="#xf0ad;" <?php selected($post->tab_icon, '#xf0ad;' ); ?>>&#xf0ad; Wrench</option>
					            <option value="#xf166;" <?php selected($post->tab_icon, '#xf166;' ); ?>>&#xf166; Youtube</option>
					        </select>

           				</div>
           				<div class="tab_input" style="width:47.5%">
           					<b>Tab Title:</b><br />
           					<input type="text" name="dname" value="<?php echo $post->tab_name; ?>" class="" style="width:100%;" id="dtabtitle<?php echo $post->tab_id ?>" />
           				</div>
       				</div>

			      </p>
			      

	            </div>

				<?php }
			}

	        function add_more_js() {
        ?>

        <script>
		    jQuery(function() {

		      	jQuery('#my_modal').popup();
			   	jQuery('.my_modal_close').click(function() {
			   		jQuery('#tabtitle').val('');
					jQuery('#icon_meta_box').val(' ');
					jQuery('#tab_id').val('');
					tinymce.get('addfmetabcontent').setContent('');
			   		jQuery("#my_modal_background").trigger('click');
			   	});
		    });

		    jQuery(function() {

		      	jQuery('#my_gmodal').popup();
			   	jQuery('.my_gmodal_close').click(function() {
			   		jQuery('#gtabtitle').val('');
					jQuery('#gicon_meta_box').val(' ');
					jQuery('#gtab_id').val('');
					tinymce.get('gaddfmetabcontent').setContent('');
			   		jQuery("#my_modal_background").trigger('click');
			   	});
		    });

		    jQuery(document).ready(function(){
			jQuery("#submit").click(function(){ 
			var icon = jQuery("#icon_meta_box").val();
			var title = jQuery("#tabtitle").val();
			var tab_id = jQuery('#tab_id').val();
			var desc = tinymce.get('addfmetabcontent').getContent(); 
			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
			var edit = '<?php echo $_GET["action"] ?>';
			var post = '<?php echo $_GET["post"] ?>';
			jQuery.ajax({
			type: 'POST',   // Adding Post method
			url: ajaxurl, // Including ajax file
			data: {"action": "tab_session", "icon":icon, "title":title, "content":desc, "tab_id":tab_id, "edit":edit, "post":post}, // Sending data dname to post_word_count function.
			success: function(data){ // Show returned data using the function.
				jQuery('#p_tabs').html(data);
				jQuery('#tabtitle').val('');
				jQuery('#icon_meta_box').val('');
				jQuery('#tab_id').val('');
				tinymce.get('addfmetabcontent').setContent(' ');
				jQuery("#my_modal_background").trigger('click');
				jQuery('.my_modal_close').trigger('click');

			}
			});
			});


			jQuery("#gsubmit").click(function(){ 
			var gicon = jQuery("#gicon_meta_box").val();
			var gtitle = jQuery("#gtabtitle").val();
			var gtab_id = jQuery('#gtab_id').val();
			var gtabstatus = jQuery('#gtabstatus').val();
			var gdesc = tinymce.get('gaddfmetabcontent').getContent();
			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
			var edit = '<?php echo $_GET["action"] ?>';
			var post = '<?php echo $_GET["post"] ?>';
			jQuery.ajax({
			type: 'POST',   // Adding Post method
			url: ajaxurl, // Including ajax file
			data: {"action": "gtab_session", "icon":gicon, "title":gtitle, "content":gdesc, "tab_id":gtab_id, "edit":edit, "post":post, "gtabstatus":gtabstatus}, // Sending data dname to post_word_count function.
			success: function(data){ 
				jQuery('#p_gtabs').html(data);
				jQuery('#gtabtitle').val('');
				jQuery('#gicon_meta_box').val('');
				jQuery('#gtab_id').val('');
				jQuery('#gtabstatus').val('');
				tinymce.get('gaddfmetabcontent').setContent(' ');
				jQuery("#my_modal_background").trigger('click');
				jQuery('.my_gmodal_close').trigger('click');

			}
			});
			});

			jQuery("#gcancel").click(function(){ 

				jQuery('#gtabtitle').val('');
				jQuery('#gicon_meta_box').val('');
				jQuery('#gtab_id').val('');
				jQuery('#gtabstatus').val('');
				tinymce.get('gaddfmetabcontent').setContent(' ');
				jQuery("#my_modal_background").trigger('click');
				jQuery('.my_gmodal_close').trigger('click');
			});


			jQuery("#cancel").click(function(){ 

				jQuery('#tabtitle').val('');
				jQuery('#icon_meta_box').val('');
				jQuery('#tab_id').val('');
				jQuery('#tabstatus').val('');
				tinymce.get('addfmetabcontent').setContent(' ');
				jQuery("#my_modal_background").trigger('click');
				jQuery('.my_modal_close').trigger('click');
			});

			});
		</script>

		<script type="text/javascript">
			

				function del(tab_id,tab_name) { 
				var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
				if(confirm("Are you sure to delete "+tab_name+" tab?"))
				{
				jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {"action": "tab_session_del", "tab_id":tab_id},
				success: function() {

					jQuery('#ftab'+tab_id).fadeOut('slow');
					jQuery('#ftab'+tab_id).remove();

				}
				});
				
				}
				return false;
				}

				function edittab(tab_id)
				{ 
					var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
					var edit = '<?php echo $_GET["action"] ?>';
					var post = '<?php echo $_GET["post"] ?>';
					var temptab = 'yes';
					jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: {"action": "tab_session_edit", "tab_id":tab_id, "edit":edit, "post":post, "temptab":temptab},
					dataType: 'json',
					success: function(json) {
						jQuery('#tabtitle').val(json['tab_name']);
						jQuery('#icon_meta_box').val(json['tab_icon']);
						jQuery('#tab_id').val(json['tab_id']);
						tinymce.get('addfmetabcontent').setContent(json['tab_description']);
					}
					});
				}

				function editprotab(tab_id)
				{ 
					var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
					var edit = '<?php echo $_GET["action"] ?>';
					var post = '<?php echo $_GET["post"] ?>';
					var temptab = '';
					jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: {"action": "tab_session_edit", "tab_id":tab_id, "edit":edit, "post":post, "temptab":temptab},
					dataType: 'json',
					success: function(json) {
						jQuery('#tabtitle').val(json['tab_name']);
						jQuery('#icon_meta_box').val(json['tab_icon']);
						jQuery('#tab_id').val(json['tab_id']);
						tinymce.get('addfmetabcontent').setContent(json['tab_description']);
					}
					});
				}

				function editgtab(tab_id)
				{ 
					var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
					var edit = '<?php echo $_GET["action"] ?>';
					var post = '<?php echo $_GET["post"] ?>';
					var tempgtab = 'yes';

					jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: {"action": "gtab_session_edit", "tab_id":tab_id, "edit":edit, "post":post, "tempgtab":tempgtab},
					dataType: 'json',
					success: function(json) {
						jQuery('#gtabtitle').val(json['tab_name']);
						jQuery('#gicon_meta_box').val(json['tab_icon']);
						jQuery('#gtab_id').val(json['tab_id']);
						jQuery('#gtabstatus').val('update');
						tinymce.get('gaddfmetabcontent').setContent(json['tab_description']);
					}
					});
				}

				function editprogtab(tab_id)
				{ 
					var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
					var edit = '<?php echo $_GET["action"] ?>';
					var post = '<?php echo $_GET["post"] ?>';
					var temptab = '';
					jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: {"action": "gtab_session_edit", "tab_id":tab_id, "edit":edit, "post":post, "temptab":temptab},
					dataType: 'json',
					success: function(json) {
						jQuery('#gtabtitle').val(json['tab_name']);
						jQuery('#gicon_meta_box').val(json['tab_icon']);
						jQuery('#gtab_id').val(json['tab_id']);
						jQuery('#gtabstatus').val('update');
						tinymce.get('gaddfmetabcontent').setContent(json['tab_description']);
					}
					});
				}

			
					jQuery('#publish').click(function() { 
						var globaltabs = [];
						var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
						var edit = '<?php echo $_GET["action"] ?>';
						var post = '<?php echo $_GET["post"] ?>';
						jQuery('#pgtabs :checked').each(function() {
						    globaltabs.push(jQuery(this).val());
						});
						jQuery.ajax({
						type: "POST",
						url: ajaxurl,
						data: {"action": "gtab_submit", "globaltabs":globaltabs, "edit":edit, "post":post},
						success: function(data) {
						}
						});

						var producttabs = [];
						jQuery('input[name^="ptabid"]').each(function() {
						    producttabs.push(jQuery(this).val());
						});
						
						jQuery.ajax({
						type: "POST",
						url: ajaxurl,
						data: {"action": "ptab_submit", "producttabs":producttabs, "edit":edit, "post":post},
						success: function(data) {
						}
						});

						
						var dicon1 = jQuery('#dicon1').val();
						var dicon2 = jQuery('#dicon2').val();
						var dicon3 = jQuery('#dicon3').val();

						var dtitle1 = jQuery('#dtabtitle1').val();
						var dtitle2 = jQuery('#dtabtitle2').val();
						var dtitle3 = jQuery('#dtabtitle3').val();

						jQuery.ajax({
						type: "POST",
						url: ajaxurl,
						data: {"action": "dtab_submit", "dicon1":dicon1, "dicon2":dicon2, "dicon3":dicon3, "dtitle1":dtitle1, "dtitle2":dtitle2, "dtitle3":dtitle3, "edit":edit, "post":post},
						success: function(data) {
						}
						});

						if(jQuery("#useglobaltabs").prop('checked') == true){
						    var usegtab = 'yes';
						}
						else
						{
							var usegtab = 'no';
						}
						
						
						jQuery.ajax({
						type: "POST",
						url: ajaxurl,
						data: {"action": "usegtab_submit", "usegtab":usegtab, "edit":edit, "post":post},
						success: function(data) {
						}
						});

					});

					function dtabopen(id)
					{	
						jQuery('.dtab').slideUp('slow');
						jQuery('#dform'+id).slideUp('slow');
						jQuery('#dform'+id).slideDown('slow');
					}
			
		</script>

		


        <?php  }



        
	}

	$fmet = new FME_Tabs();
}
?>