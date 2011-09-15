<?php
/*
Plugin Name: Custom Page Order
Plugin URI: http://drewgourley.com
Description: Allows for the ordering of pages through a simple drag-and-drop interface.
Version: 2.0
Author: Drew Gourley
*/
function custompageorder_menu() {    
	add_menu_page(__('Page Order'),  __('Page Order'), 'edit_pages', 'custompageorder', 'custompageorder', plugins_url('images/page_order.png', __FILE__), 121); 
	add_submenu_page('custompageorder', __('Order Pages'), __('Order Pages'), 'edit_pages', 'custompageorder', 'custompageorder'); 
	add_pages_page(__('Order Pages', 'custompageorder'), __('Order Pages', 'custompageorder'), 'edit_pages', 'custompageorder', 'custompageorder');
}
function custompageorder_css() {
	if ( $_GET['page'] == "custompageorder" ) {
		wp_enqueue_style('custompage', plugins_url('css/custompageorder.css', __FILE__), 'screen');
	}
}
function custompageorder_js_libs() {
	if ( $_GET['page'] == "custompageorder" ) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
	}
}
add_action('admin_menu', 'custompageorder_menu');
add_action('admin_print_styles', 'custompageorder_css');
add_action('admin_print_scripts', 'custompageorder_js_libs');

function custompageorder() {
	global $wpdb;
	$parent_ID = 0;
	if (isset($_POST['go-sub-pages'])) { 
		$parent_ID = $_POST['sub-pages'];
	}
	elseif (isset($_POST['hidden-parent-id'])) { 
		$parent_ID = $_POST['hidden-parent-id'];
	}
	if (isset($_POST['return-sub-pages'])) { 
		$parent_post = get_post($_POST['hidden-parent-id']);
		$parent_ID = $parent_post->post_parent;
	}
	$message = "";
	if (isset($_POST['order-submit'])) { 
		$message = custompageorder_update_order();
	}
?>
<div class='wrap'>
	<form name="custom-order-form" method="post" action="">
		<?php screen_icon('custompageorder'); ?>
		<h2><?php _e('Order Pages', 'custompageorder'); ?></h2>
		<?php $paged = (get_query_var('paged')) ? get_query_var('paged') : 1; ?>
		<?php $args = array( 
			'post_parent' => $parent_ID, 
			'post_type' => 'page',
			'posts_per_page' => get_option('posts_per_page'),
			'paged' => max( 1, $_GET['paged'] )
			);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) { ?>
		<div id="poststuff" class="metabox-holder">
			<div class="widget order-widget">
				<h3 class="widget-top"><?php _e('Pages', 'custompageorder') ?> | <small><?php _e('Order the pages by dragging and dropping them into the desired order.', 'custompageorder') ?></small></h3>
				<div class="misc-pub-section">
					<ul id="custom-order-list">
						<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<li id="id_<?php the_ID(); ?>" class="lineitem"><?php the_title(); ?></li>
						<?php endwhile; ?>
					</ul>
				</div>
				<?php $big = 32768;
				$args = array(
					'base' => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
					'format' => '?paged=%#%',
					'prev_next' => false,
					'current' => max( 1, $_GET['paged'] ),
					'total' => $query->max_num_pages
					); 
				$pagination = paginate_links($args); 
				if ( !empty($pagination) ) { ?>
				<div class="misc-pub-section">
					<div class="tablenav" style="margin:0">
						<div class="tablenav-pages">
							<span class="pagination-links"><?php echo $pagination; ?></span>
						</div>
					</div>
				</div>
				<?php } ?>
				<div class="misc-pub-section misc-pub-section-last">
					<?php if ($parent_ID != 0) { ?>
						<input type="submit" class="button" style="float:left" id="return-sub-pages" name="return-sub-pages" value="<?php _e('Return to Parent Page', 'custompageorder'); ?>" />
					<?php } ?>
					<div id="publishing-action">
						<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" id="custom-loading" style="display:none" alt="" />
						<input type="submit" name="order-submit" id="order-submit" class="button-primary" value="<?php _e('Update Page Order', 'custompageorder') ?>" />
					</div>
					<div class="clear"></div>
					</div>
				<input type="hidden" id="hidden-custom-order" name="hidden-custom-order" />
				<input type="hidden" id="hidden-parent-id" name="hidden-parent-id" value="<?php echo $parent_ID; ?>" />
			</div>
			<?php $options = custompageorder_sub_query($query); if( !empty($options) ) { ?>
			<div class="widget order-widget">
				<h3 class="widget-top"><?php _e('Subpages', 'custompageorder'); ?> | <small><?php _e('Choose a page from the drop down to order its subpages.', 'custompageorder'); ?></small></h3>
				<div class="misc-pub-section misc-pub-section-last">
					<select id="sub-pages" name="sub-pages">
						<?php echo $options; ?>
					</select>
					<input type="submit" name="go-sub-pages" class="button" id="go-sub-pages" value="<?php _e('Order Subpages', 'custompageorder') ?>" />
				</div>
			</div>		
			<?php } ?>
		</div>
		<?php } else { ?>
		<p><?php _e('No pages found', 'customtaxorder'); ?></p>
		<?php } ?>
	</form>
</div>
<?php if ( $query->have_posts() ) { ?>
<script type="text/javascript">
// <![CDATA[
	jQuery(document).ready(function($) {
		$("#custom-loading").hide();
		$("#order-submit").click(function() {
			orderSubmit();
		});
	});
	function custompageorderAddLoadEvent(){
		jQuery("#custom-order-list").sortable({ 
			placeholder: "sortable-placeholder", 
			revert: false,
			tolerance: "pointer" 
		});
	};
	addLoadEvent(custompageorderAddLoadEvent);
	function orderSubmit() {
		var newOrder = jQuery("#custom-order-list").sortable("toArray");
		jQuery("#custom-loading").show();
		jQuery("#hidden-custom-order").val(newOrder);
		return true;
	}
// ]]>
</script>
<?php }
}

function custompageorder_update_order() {
	if (isset($_POST['hidden-custom-order']) && $_POST['hidden-custom-order'] != "") { 
		global $wpdb;
		$offset = ( max( 1, $_GET['paged'] ) - 1 ) * get_option('posts_per_page');
		$new_order = $_POST['hidden-custom-order'];
		$IDs = explode(",", $new_order);
		$result = count($IDs);
		for($i = 0; $i < $result; $i++)	{
			$str = str_replace("id_", "", $IDs[$i]);
			$order = $i + $offset;
			$update = array('ID' => $str, 'menu_order' => $order);
			wp_update_post( $update );
		}
		return '<div id="message" class="updated fade"><p>'. __('Page order updated successfully.', 'custompageorder').'</p></div>';
	} else {
		return '<div id="message" class="error fade"><p>'. __('An error occured, order has not been saved.', 'custompageorder').'</p></div>';
	}
}
function custompageorder_sub_query( $query ) {
	$options = '';
	while ( $query->have_posts() ) : $query->the_post(); $page_ID = get_the_ID(); $args = array( 'post_parent' => $page_ID, 'post_type' => 'page' ); $subpages = new WP_Query( $args );
		if ( $subpages->have_posts() ) { 
			while ( $subpages->have_posts() ) : $subpages->the_post(); $options .= '<option value="' . $page_ID . '">' . get_the_title($page_ID) . '</option>'; endwhile; 
		} 
	endwhile;
	return $options;
}

function custompageorder_order_pages($orderby) {
	global $wpdb;
	$orderby = "$wpdb->posts.menu_order ASC";
	return $orderby;
}
function custompageorder_sort() {
	if (!is_admin() || is_admin() && !isset($_GET['orderby'])) {
		add_filter('posts_orderby', 'custompageorder_order_pages');
	}
}
add_action('init', 'custompageorder_sort');
?>