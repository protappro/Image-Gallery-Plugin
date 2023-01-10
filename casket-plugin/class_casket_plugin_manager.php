<?php
/**
 * A plugin for Caskets Management
 *
 * @package   CasketsManagementPlugin
 * @author    Protap Mondal <protap.capsquery@gmail.com>
 * @license   GPL-2.0+
 * @link      https://github.com/protappro/
 *
 * @wordpress-plugin
 * Plugin Name: Caskets Management Plugin
 * Plugin URI:  http://ngwebservices.com/
 * Description: This plugin allows to manage caskets.
 * Version:     1.0.0.0
 * Author:      Protap Mondal
 * Author URI:  https://github.com/protappro/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

Class CasketsManagementPlugin
{
	public function __construct()
	{
		add_action('init', [$this, 'register_casket_custom_post_type'], 2);

        add_action('wp_head', array($this, 'define_ajax_url'));
		add_action('wp_enqueue_scripts', [$this, 'inject_scripts_and_styles']);
		add_action('admin_enqueue_scripts', [$this, 'inject_admin_scripts_styles']);

		add_filter('manage_caskets_posts_columns', [$this, 'modify_caskets_columns_head']);
		add_action('manage_caskets_posts_custom_column', [$this, 'caskets_columns_content'], 10, 2);

        add_action( 'admin_menu', [$this, 'casket_menu_options']);

		add_shortcode('casket_shortcode', [$this, "shortcode_for_casket"]);
		
	}	

	public function define_ajax_url(){         
        echo "<script>\n\t var casket_ajax_url = \"" . admin_url("admin-ajax.php") . "\"\n</script>";        
    }

	public function inject_scripts_and_styles(){	
		//wp_enqueue_script("fancybox-mousewheel", 'https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js', array('jquery'), microtime(), false);

		wp_enqueue_style("fancybox-style", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.css');	
		wp_enqueue_script("fancybox-script", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.pack.js', array('jquery'), microtime(), false);	

		//wp_enqueue_style("fancybox-buttons-style", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/helpers/jquery.fancybox-buttons.css');	
		//wp_enqueue_script("fancybox-buttons-script", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/helpers/jquery.fancybox-buttons.js', array('jquery'), microtime(), false);	
		//wp_enqueue_script("fancybox-media-script", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/helpers/jquery.fancybox-media.js', array('jquery'), microtime(), false);
		
		//wp_enqueue_style("fancybox-buttons-style", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/helpers/jquery.fancybox-thumbs.css');	
		//wp_enqueue_script("fancybox-buttons-script", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/helpers/jquery.fancybox-thumbs.js', array('jquery'), microtime(), false);


		//wp_enqueue_style("fancybox-style", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css');
		//wp_enqueue_script("fancybox-script", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js', array('jquery'), microtime(), false);		
	}

	public function inject_admin_scripts_styles(){
		wp_enqueue_style("fontawesome-style", 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');	
		wp_enqueue_style("casket-admin-style", plugin_dir_url(__FILE__) . 'css/admin.css');
		wp_enqueue_script('jquery-ui-sortable');		
        wp_enqueue_script('jquery-ui-tabs');        
        wp_enqueue_style('casket-jquery-ui-css', "https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css?ver=5.5.3");
		wp_enqueue_script("casket-admin-script", plugin_dir_url(__FILE__) . 'js/admin-script.js');
	}

	public function shortcode_for_casket($atts){
		$list_of_terms = get_terms(array(
            'taxonomy' => 'casket-category',
            'hide_empty' => false,
            'orderby' => 'ID',
            'order' => 'ASC',
        ));
        
        $arr_casket_cateory = [];
        foreach($list_of_terms as $term){
            $arr_casket_cateory[$term->term_id] = $term->name;
        }
        
        $html ='';
        foreach ($arr_casket_cateory as $casket_cateory_key => $casket_cateory_value) {
		   	$arr_category_casket_order = [];
		   	$term_name = get_term( $casket_cateory_key )->slug;
		   	$term_key = $term_name."_casket-order";
		   	$arr_category_casket_order = get_term_meta( $casket_cateory_key, $term_key, true); 

		   	$arr_casket = get_posts([
			       'post_type' => 'caskets',
			       'posts_per_page' => -1,
			       'post_status' => 'publish',
			       'tax_query' => [
			        [
			            'taxonomy' => 'casket-category',
			            'terms' => $casket_cateory_key
			        ],
			    ],
			    'post__in' => $arr_category_casket_order,                
			    'orderby' => 'post__in',    
			]);

			
		   	if(!empty($arr_casket)){
			   	$html .= "<h2 class='page-casket-title'>".$casket_cateory_value."</h2>";
			   	$html .= "<h6 class='page-casket-title-sub'>(click on casket image to enlarge)</h6>";
				$html .= '<div class="row pt-3 pb-3 casket_list">';
				foreach ($arr_casket as $caskets) { 
					$casket_image = wp_get_attachment_image_src( get_post_thumbnail_id( $caskets->ID ), 'single-post-thumbnail' );			
					$html .= '<div class="col-12 col-md-4"> <div class="casket-box">';
					$html .= '<a href="'.$casket_image[0].'" rel="gallery1" title="'.$caskets->post_title.'">';				
					$html .= '<img src="'.$casket_image[0].'" class="card-img-top" alt="'.$caskets->post_title.'">';
					$html .= '<div class="card-body text-center">';
					$html .= '<h6 class="text-uppercase"><strong>'.$caskets->post_title.'</strong></h6>';				
					$html .= '</div>';				
					$html .= '</a>';
					$html .= '</div></div>';
				}
				$html .= '</div>';
			}

		}	
		echo $html;		
		
		wp_enqueue_style("casket-style", plugin_dir_url(__FILE__) . 'css/style.css');	
		wp_enqueue_script("casket-script", plugin_dir_url(__FILE__) . 'js/casket_plugin.js', array('jquery'), microtime(),true);
	}

	public function casket_menu_options(){
		add_submenu_page( 'edit.php?post_type=caskets', 'Ordering', 'Ordering', 'administrator', 'casket_order_arrange', array($this, 'set_casket_order_arrange'));
	}

	public function set_casket_order_arrange(){
		$list_of_terms = get_terms(array(
            'taxonomy' => 'casket-category',
            'hide_empty' => false,
            'orderby' => 'ID',
            'order' => 'ASC',
        ));
        
        $arr_casket_cateory = [];
        foreach($list_of_terms as $term){
            $arr_casket_cateory[$term->term_id] = $term->name;
        }

        foreach($_POST as $key => $data){
	        $arr = explode('-', $key);
            $term_name = get_term( $arr[1] )->slug;
            if(array_key_exists($arr[1], $arr_casket_cateory)){
                $term_id = $arr[1];
                $term_key = $term_name."_casket-order";
                update_term_meta( $term_id, $term_key, $data); 
            }  
	    }
	    
		$html = "<div id=\"poststuff\" style=\"margin-top: 20px; margin-right: 20px\">";  
        $html .= "<form name=\"casket_order_form\" method=\"post\" action=\"edit.php?post_type=caskets&page=casket_order_arrange\" class=\"casket_order_form\">";

        $html .= "<div class=\"postbox\">"; 
        $html .= "<h2 class=\"hndle\" style=\"font-size:20px; border-bottom: 1px solid #ccd0d4;\">Casket Ordering (Drag to Re-order)</h2>";  
        
        $html .= '<div class="casket_category_order_tab">';
        $html .= '<div class="casket-category-tabs" id="casket_category_tabs" style="margin-top: 10px;">';
        $html .= '<ul class="tab-links">';
        foreach ($arr_casket_cateory as $casket_cateory_key => $casket_cateory_value) {
            $html .= "<li><a href=\"#casket_category_order_tab-".$casket_cateory_key."\">".$casket_cateory_value."</a></li>";
        }
        $html .= '</ul>';
        $html .= '<div class="tab-content">';
        foreach ($arr_casket_cateory as $casket_cateory_key => $casket_cateory_value) {
        	$arr_category_casket_order = [];
            $term_name = get_term( $casket_cateory_key )->slug;
            $term_key = $term_name."_casket-order";
            $arr_category_casket_order = get_term_meta( $casket_cateory_key, $term_key, true);       
            
        	$casket_arr = get_posts([
	            'post_type' => 'caskets',
	            'posts_per_page' => -1,
	            'post_status' => 'publish',
                // 'orderby' => 'meta_value_num',
                // 'order' => 'DESC',
	            /*'tax_query' => [
                    [
                        'taxonomy' => 'casket-category',
                        'terms' => $casket_cateory_key
                    ],
                ],*/
                'post__not_in' => $arr_category_casket_order,
	        ]);
	        
	        foreach ($casket_arr as $casket_data) {
                array_push($arr_category_casket_order, $casket_data->ID);
            }            

            $arr_casket = new WP_Query([
                'post_type' => 'caskets','posts_per_page' => -1, 'post_status' => 'publish','post__in' => $arr_category_casket_order, 'orderby' => 'post__in','tax_query' => [
                    [
                        'taxonomy' => 'casket-category',
                        'terms' => $casket_cateory_key
                    ],
                ],
            ]);

        	$html .= "<div id=\"casket_category_order_tab-".$casket_cateory_key."\">";
	        $html .= '<div class="casket_list"><ul class="sortable_casket" id="sortable_casket-'.$casket_cateory_key.'">';
	        foreach($arr_casket->posts as $caskets) {           
				$casket_job_title = get_post_meta($caskets->ID, '_casket_job_title', true);   
	            $html .= '<li class="casket_id_'.$caskets->ID.'" style="cursor:move">'.$caskets->post_title;
	            $html .= '<span class="move_casket" data-post_id="'.$caskets->ID.'"><i class="fa fa-arrows" aria-hidden="true"></i></span>';                        
	            $html .= "<input type=\"hidden\" value=\"". $caskets->ID ."\" name=\"casket_ids_by_category-".$casket_cateory_key."[]\">";
	            $html .= '</li>';
	        }
	        $html .= '</ul></div>';
	        $html .= '</div>';
    	}
    	wp_reset_query(); 
         
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '
         <hr/>
         <button type="submit" name="order_submit_btn" id="order_submit_btn" class="button button-primary order_submit_btn">Save Order</button>';
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';

        $html .='<script>
            jQuery(document).on(\'ready\', function() {
            	jQuery("#casket_category_tabs").tabs();';
	         	foreach ($arr_casket_cateory as $casket_cateory_key => $casket_cateory_value) {
	            	$html .='    
		            	jQuery( "#sortable_casket-'.$casket_cateory_key.'" ).sortable();
		                jQuery( "#sortable_casket-'.$casket_cateory_key.'" ).disableSelection(); 
		            ';   
	            }
        $html .='});
        </script>';
        echo $html;
	}

	public function modify_caskets_columns_head($columns){       
		unset( $columns['date'] );
		$columns = array_merge ( $columns, array ( 
			'title' => __( 'Title', 'your_text_domain' ),
			'casket_photo' => __( 'Casket Photo', 'your_text_domain' ),
			'date' => __('Date')
		));
		return $columns;
	}

	public function caskets_columns_content($column, $post_id){
		switch ( $column ) {
			case 'casket_photo' :		
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'single-post-thumbnail' );
			echo '<img src="'.$image[0].'"/>';
			break;
		}
	}

	public function register_casket_custom_post_type() {
		$labels = [
			'name' 				 => __( 'Caskets'),
			'singular_name' 	 => __( 'Caskets'),
			'menu_name'          => _x( 'Caskets', 'admin menu', 'your-plugin-textdomain' ),
			'name_admin_bar'     => _x( 'Caskets', 'add new on admin bar', 'your-plugin-textdomain' ),
			'add_new'            => _x( 'Add New', 'Caskets', 'your-plugin-textdomain' ),
			'add_new_item'       => __( 'Add New Casket', 'your-plugin-textdomain' ),
			'new_item'           => __( 'New Casket', 'your-plugin-textdomain' ),
			'edit_item'          => __( 'Edit Casket', 'your-plugin-textdomain' ),
			'view_item'          => __( 'View Casket', 'your-plugin-textdomain' ),
			'all_items'          => __( 'All Caskets', 'your-plugin-textdomain' ),
			'search_items'       => __( 'Search Casket', 'your-plugin-textdomain' ),
			'parent_item_colon'  => __( 'Parent Casket:', 'your-plugin-textdomain' ),
			'not_found'          => __( 'No posts found.', 'your-plugin-textdomain' ),
			'not_found_in_trash' => __( 'No posts found in Trash.', 'your-plugin-textdomain' )
		];
		$args = [
			'label' 					=> __('Caskets'),
			'description' 				=> __('Manage Caskets'),
			'labels' 					=> $labels,
			'supports' 					=> array('title', 'thumbnail'),
            // You can associate this CPT with a taxonomy or custom taxonomy.
			'taxonomies' 				=> array(),
            /* A hierarchical CPT is like Pages and capackage_filen have
            * Parent and child items. A non-hierarchical CPT
            * is like Posts.
            */
            'featured_image' 			=> true,
            'hierarchical' 				=> false,
            'public' 					=> true,
            'show_ui' 					=> true,
            'show_in_menu' 				=> true,
            'show_in_nav_menus' 		=> true,
            'show_in_admin_bar'			=> true,
            'menu_icon' 				=> 'dashicons-align-wide',
            'can_export' 				=> true,
            'query_var'          	 	=> true,
            'rewrite'           	 	=> array( 'slug' => 'caskets' ),
            'has_archive' 				=> true,
            'exclude_from_search' 		=> false,
            'publicly_queryable'		=> true,
            'capability_type' 			=> 'post',
            'menu_position'      		=> 8,
        ];
        register_post_type('caskets', $args);

        /*Register Caskets category*/
	    $labels = array(
	        'name'              => _x( 'Categories', 'taxonomy general name', 'textdomain' ),
	        'singular_name'     => _x( 'Category', 'taxonomy singular name', 'textdomain' ),
	        'search_items'      => __( 'Search Categories', 'textdomain' ),
	        'all_items'         => __( 'All Categories', 'textdomain' ),
	        'parent_item'       => __( 'Parent Category', 'textdomain' ),
	        'parent_item_colon' => __( 'Parent Category:', 'textdomain' ),
	        'edit_item'         => __( 'Edit Category', 'textdomain' ),
	        'update_item'       => __( 'Update Category', 'textdomain' ),
	        'add_new_item'      => __( 'Add New Category', 'textdomain' ),
	        'new_item_name'     => __( 'New Category Name', 'textdomain' ),
	        'menu_name'         => __( 'Category', 'textdomain' ),
	    );

	    $args = array(
	        'hierarchical'      => true,
	        'labels'            => $labels,
	        'show_ui'           => true,
	        'show_admin_column' => true,
	        'query_var'         => true,
	        'rewrite'           => array( 'slug' => 'casket-category' ),
	    );

	    register_taxonomy( 'casket-category', 'caskets', $args );  
    }

}
$obj_casket_plugin_management = new CasketsManagementPlugin();

