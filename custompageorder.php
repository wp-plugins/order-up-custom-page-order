<?php
/*
Plugin Name: Custom Page Order
Plugin URI: http://www.leopard-inc.com
Description: Allows for the ordering of pages through a simple drag-and-drop interface.
Version: 1.0
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
add_action('admin_menu', 'custompageorder_css');
add_action('admin_print_scripts', 'custompageorder_js_libs');

function custompageorder() {
	global $wpdb;
	$parentID = 0;
	if (isset($_POST['btnSubPages'])) { 
		$parentID = $_POST['pages'];
	}
	elseif (isset($_POST['hdnParentID'])) { 
		$parentID = $_POST['hdnParentID'];
	}
	if (isset($_POST['btnReturnParent'])) { 
		$parentsParent = $wpdb->get_row("SELECT post_parent FROM $wpdb->posts WHERE ID = " . $_POST['hdnParentID'], ARRAY_N);
		$parentID = $parentsParent[0];
	}
	$success = "";
	if (isset($_POST['btnOrderPages'])) { 
		$success = custompageorder_updateOrder();
	}
	$subPageStr = custompageorder_getSubPages($parentID);
?>
<div class='wrap'>
	<form name="frmCustomPageOrder" method="post" action="">
		<?php screen_icon('custompageorder'); ?>
		<h2><?php _e('Order Pages', 'custompageorder') ?></h2>
		<?php echo $success; ?>
		<?php if( $subPageStr ) { ?>
		<h3><?php _e('Order Subpages', 'custompageorder') ?></h3>
		<p><?php _e('Choose a page from the drop down to order its subpages.', 'custompageorder') ?></p>
		<select id="pages" name="pages">
			<?php echo $subPageStr; ?>
		</select>
		<input type="submit" name="btnSubPages" class="button" id="btnSubPages" value="<?php _e('Order Subpages', 'custompageorder') ?>" />
		<?php } ?>
		<?php $results = custompageorder_pageQuery($parentID); 
		if ( $results ) { ?>
		<p><?php _e('Order the pages by dragging and dropping them into the desired order.', 'custompageorder') ?></p>
		<div class="metabox-holder">
			<div class="postbox-container" style="width:80%">
				<div class="stuffbox">
					<h3><?php _e('Pages', 'custompageorder') ?></h3>
					<div id="minor-publishing">
						<ul id="customPageOrderList">
							<?php foreach($results as $row) { echo '<li id="id_'.$row->ID.'" class="lineitem">'.__($row->post_title).'</li>'; } ?>
						</ul>
					</div>
					<div id="major-publishing-actions">
						<?php echo custompageorder_getParentLink($parentID); ?>
						<div id="publishing-action">
							<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" id="custom-loading" style="visibility:hidden;" alt="" />
							<input type="submit" name="btnOrderPages" id="btnOrderPages" class="button-primary" value="<?php _e('Update Page Order', 'custompageorder') ?>" onclick="javascript:orderPages(); return true;" />
						</div>
						<div class="clear"></div>
					</div>
					<input type="hidden" id="hdnCustomPageOrder" name="hdnCustomPageOrder" />
					<input type="hidden" id="hdnParentID" name="hdnParentID" value="<?php echo $parentID; ?>" />
				</div>
			</div>
		</div>
		<?php } else { ?>
		<p><?php _e('No pages found', 'customtaxorder'); ?></p>
		<?php } ?>
	</form>
</div>
<?php if ( $results ) { ?>
<script type="text/javascript">
// <![CDATA[
	function custompageorderaddloadevent(){
		jQuery("#customPageOrderList").sortable({ 
			placeholder: "sortable-placeholder", 
			revert: false,
			tolerance: "pointer" 
		});
	};
	addLoadEvent(custompageorderaddloadevent);
	function orderPages() {
		jQuery("#custom-loading").css( "visibility", "visible" );
		jQuery("#hdnCustomPageOrder").val(jQuery("#customPageOrderList").sortable("toArray"));
	}
// ]]>
</script>
<?php }
}

function custompageorder_updateOrder() {
	if (isset($_POST['hdnCustomPageOrder']) && $_POST['hdnCustomPageOrder'] != "") { 
		global $wpdb;
		$hdnCustomPageOrder = $_POST['hdnCustomPageOrder'];
		$IDs = explode(",", $hdnCustomPageOrder);
		$result = count($IDs);
		for($i = 0; $i < $result; $i++)	{
			$str = str_replace("id_", "", $IDs[$i]);
			$wpdb->query("UPDATE $wpdb->posts SET menu_order = '$i' WHERE id ='$str'");
		}
		return '<div id="message" class="updated fade"><p>'. __('Page order updated successfully.', 'custompageorder').'</p></div>';
	} else {
		return '<div id="message" class="error fade"><p>'. __('An error occured, order has not been saved.', 'custompageorder').'</p></div>';
	}
}

function custompageorder_getSubPages($parentID) {
	global $wpdb;
	$query_result = custompageorder_pageQuery($parentID);
	foreach($query_result as $row) {
		$post_count=$wpdb->get_row("SELECT count(*) as postsCount FROM $wpdb->posts WHERE post_parent = $row->ID and post_type = 'page' AND post_status != 'trash' AND post_status != 'auto-draft' ", ARRAY_N);
		if($post_count[0] > 0) {
	    	$subPageStr .= '<option value="'.$row->ID.'">'.__($row->post_title).'</option>';
		}
	}
	if ( $subPageStr ) {
		return $subPageStr;
	} else {
		return false;
	}
}

function custompageorder_pageQuery($parentID) {
	global $wpdb;
	$query_result = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_parent = $parentID and post_type = 'page' AND post_status != 'trash' AND post_status != 'auto-draft' ORDER BY menu_order ASC");
	if ( empty($query_result) ) {
		return false;
	} else {
		return $query_result;
	}
}

function custompageorder_getParentLink($parentID) {
	if($parentID != 0) {
		return '<input type="submit" class="button" style="float:left" id="btnReturnParent" name="btnReturnParent" value="'. __('Return to Parent Page', 'custompageorder') .'" />';
	} else {
		return "";
	}
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