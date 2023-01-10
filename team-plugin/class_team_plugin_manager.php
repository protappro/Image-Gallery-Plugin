<?php
/**
 * A plugin for Team Member Management
 *
 * @package   TeamMemberPlugin
 * @author    Protap Mondal <protap.capsquery@gmail.com>
 * @license   GPL-2.0+
 * @link      https://github.com/protappro/
 *
 * @wordpress-plugin
 * Plugin Name: Team Member Plugin Manager
 * Plugin URI:  http://ngwebservices.com/
 * Description: This plugin allows to manage individucal team members profile and bio.
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

Class TeamMemberPluginManager
{
	public function __construct()
	{
		add_action('init', [$this, 'register_team_member_custom_post_type'], 2);

        add_action('wp_head', array($this, 'define_ajax_url'));
		add_action('wp_enqueue_scripts', [$this, 'inject_scripts_and_styles']);
		add_action('admin_enqueue_scripts', [$this, 'inject_admin_scripts_styles']);

		add_action( 'add_meta_boxes', [$this, 'team_member_register_meta_boxes']);
		add_action( 'save_post', [$this, 'team_member_save_meta_settings']);

        add_action('wp_ajax_get_team_member_details', array($this, 'ajax_get_team_member_details'));
        add_action('wp_ajax_nopriv_get_team_member_details', array($this, 'ajax_get_team_member_details')); 

		add_filter('manage_team-member_posts_columns', [$this, 'modify_team_member_columns_head']);
		add_action('manage_team-member_posts_custom_column', [$this, 'team_member_columns_content'], 10, 2);

        add_action( 'admin_menu', [$this, 'team_member_menu_options']);

		add_shortcode('team_member_shortcode', [$this, "shortcode_for_team_member"]);
	}

	public function define_ajax_url(){        
        echo "<script>\n\tvar ajaxurl = \"" . admin_url("admin-ajax.php") . "\"\n</script>";
    }

	public function inject_admin_scripts_styles(){
		wp_enqueue_style("fontawesome-style", 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');	
		wp_enqueue_style("team-member-admin-style", plugin_dir_url(__FILE__) . 'css/admin.css');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script("team-member-admin-script", plugin_dir_url(__FILE__) . 'js/admin-script.js');
		wp_register_style('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
		wp_enqueue_style('jquery-ui');
	}

	public function inject_scripts_and_styles(){
		wp_enqueue_style("fancybox-style", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.css');	
		wp_enqueue_script("fancybox-script", 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.pack.js', array('jquery'), microtime(), false);	
		
		wp_enqueue_style("team-member-style", plugin_dir_url(__FILE__) . 'css/style.css');	
		wp_enqueue_script("team-member-script", plugin_dir_url(__FILE__) . 'js/team_plugin.js', array('jquery'), microtime(),true);	
	}

	public function shortcode_for_team_member($atts){
		$combined_ids = [];

        $arr_team_member_order_form_data = get_option( 'team_member_order_form_data' );        
		$team_sort_order = array_flip($arr_team_member_order_form_data);

		$arr_team_member = get_posts([
			'post_type' => 'team-member',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'date',
			'order' => 'ASC',
		]); 
		usort($arr_team_member, function($item1, $item2) use ($team_sort_order) {  
            if(array_key_exists($item1->ID, $team_sort_order) && array_key_exists($item2->ID, $team_sort_order)){
                if($team_sort_order[$item1->ID] == $team_sort_order[$item2->ID]){
                    return 0;
                }
                return ($team_sort_order[$item1->ID] < $team_sort_order[$item2->ID]) ? -1 : 1;
            } else if(array_key_exists($item1->ID, $team_sort_order)){
                return -1;
            } else if(array_key_exists($item2->ID, $team_sort_order)){
                return 1;
            } else {
                return 0;
            }

        }); 


		$html ='';
		$html .= '<div class="row team_member_list">';
		foreach ($arr_team_member as $team_members) { 
			$team_bio_contents_description = get_post_meta($team_members->ID, '_team_bio_contents_description', true);
			$team_member_job_title = get_post_meta($team_members->ID, '_team_member_job_title', true);
			$profile_image = wp_get_attachment_image_src( get_post_thumbnail_id( $team_members->ID ), 'single-post-thumbnail' );

			
			$html .= '<div class="col-12 col-md-4">';
			$html .= '<div class="h-100 rounded-0 team_member_card"  data-team_member_id="'.$team_members->ID.'">';
			$html .= '<img src="'.$profile_image[0].'" class="w-100" alt="">';
			$html .= '<div class="card-body text-center">';
			$html .= '<h3 class="text-uppercase name">'.$team_members->post_title.'</h3>';
			$html .= '<p class="card-text job-title">'.$team_member_job_title.'</p>';
			// $html .= '<button type="button" class="btn btn-sm btn-primary view_btn" data-team_member_id="'.$team_members->ID.'">View More</button>';
			$html .= '</div></div></div>';	
		}
		$html .= '</div>';

		$html .= '<div class="modal fade" id="team_member_modal" tabindex="-1" role="dialog" aria-labelledby="teamMemberModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
			   <div class="modal-dialog modal-dialog-centered" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <h5 class="modal-title" id="teamMemberModalLabel">Team Member</h5>
			        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
			          <span aria-hidden="true">&times;</span>
			        </button>
			      </div>
			      <div class="modal-body team_member_deatils_wrapper"></div>				      
			    </div>
			</div>
		</div>';
		
		echo $html;
	}

	public function ajax_get_team_member_details(){
        $html = '';
        if(isset($_POST["team_member_id"])){
        	$team_memeber_details = get_post( $_POST["team_member_id"], ARRAY_A );        	
        	$team_bio_contents_description = get_post_meta($team_memeber_details['ID'], '_team_bio_contents_description', true);
			$team_member_job_title = get_post_meta($team_memeber_details['ID'], '_team_member_job_title', true);
			$profile_photo = wp_get_attachment_image_src( get_post_thumbnail_id( $team_memeber_details['ID'] ), 'single-post-thumbnail' );

			$html .= '<div class="row">  
						<div class="col-12 col-md-4">
							<img class="rounded" src="'.$profile_photo[0].'" alt="team-profile-pic">
						</div>                    
						<div class="col-12 col-md-8">
							<h4 class="card-title">'.$team_memeber_details['post_title'].'</h4>
							<h6 class="card-title">'.$team_member_job_title.'</h6>
							<span>'.wpautop($team_bio_contents_description).'</span>
						</div>                       
	                </div>';			
        } 
        echo $html;
        exit;
	}

	public function team_member_menu_options(){
		add_submenu_page( 'edit.php?post_type=team-member', 'Ordering', 'Ordering', 'administrator', 'team_member_order_arrange', array($this, 'set_team_member_order_arrange'));
	}

	public function set_team_member_order_arrange(){

		if(!empty($_POST['team_member_ids'])){ 
			 update_option( 'team_member_order_form_data', $_POST['team_member_ids']); 
		}

        $arr_team_member_order_form_data = get_option( 'team_member_order_form_data' );
        $arr_team_id_flip_order = array_flip($arr_team_member_order_form_data);

		$arr_team_member = get_posts([
            'post_type' => 'team-member',
            'posts_per_page' => -1,
			'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'ASC',
        ]); 

        usort($arr_team_member, function($item1, $item2) use ($arr_team_id_flip_order) {  
            if(array_key_exists($item1->ID, $arr_team_id_flip_order) && array_key_exists($item2->ID, $arr_team_id_flip_order)){
                if($arr_team_id_flip_order[$item1->ID] == $arr_team_id_flip_order[$item2->ID]){
                    return 0;
                }
                return ($arr_team_id_flip_order[$item1->ID] < $arr_team_id_flip_order[$item2->ID]) ? -1 : 1;
            } else if(array_key_exists($item1->ID, $arr_team_id_flip_order)){
                return -1;
            } else if(array_key_exists($item2->ID, $arr_team_id_flip_order)){
                return 1;
            } else {
                return 0;
            }
        }); 


		$html = "<div id=\"poststuff\" style=\"margin-top: 20px; margin-right: 20px\">";  
        $html .= "<form name=\"team_member_order_form\" method=\"post\" action=\"edit.php?post_type=team-member&page=team_member_order_arrange\" class=\"team_member_order_form\">";

        $html .= "<div class=\"postbox\">"; 
        $html .= "<h2 class=\"hndle\" style=\"font-size:20px; border-bottom: 1px solid #ccd0d4;\">Team Member Ordering (Drag to Re-order)</h2>";  
        $html .= '<div class="inside team_member_order-wrapper">';

        $html .= '<div class="team_member_list"><ul class="sortable_team_member" id="sortable_team_member">';
        foreach($arr_team_member as $team_members) {           
			$team_member_job_title = get_post_meta($team_members->ID, '_team_member_job_title', true);     

            $html .= '<li class="team_id_'.$team_members->ID.'" style="cursor:move">'.$team_members->post_title.'<small> ('.$team_member_job_title.')</small>';
            $html .= '<span class="move_team_member" data-post_id="'.$team_members->ID.'"><i class="fa fa-arrows" aria-hidden="true"></i></span>';                        
            $html .= '<input type="hidden" value="'.$team_members->ID.'" name="team_member_ids[]">';
            $html .= '</li>';
        }
        $html .= '</ul></div>';
         
        $html .= '</div>';
         $html .= '
         <hr/>
         <button type="submit" name="order_submit_btn" id="order_submit_btn" class="button button-primary order_submit_btn">Save Order</button>';
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';

        $html .='<script>
            jQuery(document).on(\'ready\', function() {
                jQuery( "#sortable_team_member" ).sortable();
                jQuery( "#sortable_team_member" ).disableSelection();                
            });
        </script>';
        echo $html;
	}

	public function team_member_register_meta_boxes() {
		add_meta_box( 'team-member-template-content', __( 'Additional Details', 'textdomain' ), array($this,'team_member_bio_display_content_callback'), array('team-member') );
	}

	public function modify_team_member_columns_head($columns){       
		unset( $columns['date'] );
		$columns = array_merge ( $columns, array ( 
			'title' => __( 'Title', 'your_text_domain' ),
			'team_profile_photo' => __( 'Profile Photo', 'your_text_domain' ),
			'team_job_title'   =>  __( 'Job Title', 'your_text_domain' ),
			'date' => __('Date')
		));
		return $columns;
	}

	public function team_member_columns_content($column, $post_id){
		switch ( $column ) {
			case 'team_profile_photo' :		
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'single-post-thumbnail' );
			echo '<img src="'.$image[0].'"/>';
			break;
			case 'team_job_title' :
			echo get_post_meta($post_id, '_team_member_job_title', true);
			break;
		}
	}

	public function team_member_save_meta_settings($post_id){
		// Check if our nonce is set.
		if (!isset($_POST['team_member_detail_nonce'])) { 
			return $post_id;
		}
		$nonce = $_POST['team_member_detail_nonce'];

        // Verify that the nonce is valid.
		if (!wp_verify_nonce($nonce, 'team_member_details')) { 
			return $post_id;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { 
			return $post_id;
		}

		if ('team-member' != $_POST['post_type']) {
			return $post_id;
		}

		if (get_post_type($post_id) == "team-member") {
			$is_autosave = wp_is_post_autosave($post_id);
			$is_revision = wp_is_post_revision($post_id);
			$is_auto_draft = (get_post_status($post_id) == "auto-draft");
			if ($is_autosave || $is_revision || $is_auto_draft) { 
				return;
			}

			if(!empty($_POST['team_bio_contents_description'])){
				update_post_meta($post_id, '_team_bio_contents_description', $_POST['team_bio_contents_description']);
			}

			if(!empty($_POST['team_member_job_title'])){
				update_post_meta($post_id, '_team_member_job_title', $_POST['team_member_job_title']);
			}
		}
	}

	public function team_member_bio_display_content_callback($post) {		
		wp_nonce_field('team_member_details', 'team_member_detail_nonce');

		$team_bio_contents_description = get_post_meta($post->ID, '_team_bio_contents_description', true);
		$team_member_job_title = get_post_meta($post->ID, '_team_member_job_title', true);

		echo '<div class="team-member-form-field term-slug-wrap">	
		<label for="tag-slug"><strong>Job Tilte: </strong></label>
		<input type="text" name="team_member_job_title" id="team_member_job_title" class="team_member_job_title" value="'.$team_member_job_title.'" style="width:100%">
		<br/><br/>
		<label for="tag-slug"><strong>Bio Descriptions: </strong></label>';

		wp_editor($team_bio_contents_description, 'team_bio_contents_description', array(
			'wpautop'               =>  true,
			'media_buttons' 		=>  true,
			'textarea_name' 		=>  'team_bio_contents_description',
			'textarea_rows' 		=>  15,
			'teeny'                 =>  true
		));

		echo '
		</div>';
	}

	public function register_team_member_custom_post_type() {
		$labels = [
			'name' 				 => __( 'Teams'),
			'singular_name' 	 => __( 'Teams'),
			'menu_name'          => _x( 'Teams', 'admin menu', 'your-plugin-textdomain' ),
			'name_admin_bar'     => _x( 'Teams', 'add new on admin bar', 'your-plugin-textdomain' ),
			'add_new'            => _x( 'Add New', 'Teams', 'your-plugin-textdomain' ),
			'add_new_item'       => __( 'Add New Team', 'your-plugin-textdomain' ),
			'new_item'           => __( 'New Team', 'your-plugin-textdomain' ),
			'edit_item'          => __( 'Edit Team', 'your-plugin-textdomain' ),
			'view_item'          => __( 'View Team', 'your-plugin-textdomain' ),
			'all_items'          => __( 'All Teams', 'your-plugin-textdomain' ),
			'search_items'       => __( 'Search Team', 'your-plugin-textdomain' ),
			'parent_item_colon'  => __( 'Parent Team:', 'your-plugin-textdomain' ),
			'not_found'          => __( 'No posts found.', 'your-plugin-textdomain' ),
			'not_found_in_trash' => __( 'No posts found in Trash.', 'your-plugin-textdomain' )
		];
		$args = [
			'label' 					=> __('Teams'),
			'description' 				=> __('Manage Team Members and their Bio'),
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
            'menu_icon' 				=> 'dashicons-groups',
            'can_export' 				=> true,
            'query_var'          	 	=> true,
            'rewrite'           	 	=> array( 'slug' => 'team-member' ),
            'has_archive' 				=> true,
            'exclude_from_search' 		=> false,
            'publicly_queryable'		=> true,
            'capability_type' 			=> 'post',
            'menu_position'      		=> 6,
        ];
        register_post_type('team-member', $args);
    }

}
$obj_team_member_plugin_manager = new TeamMemberPluginManager();

