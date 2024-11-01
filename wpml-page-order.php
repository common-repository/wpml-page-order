<?php
/*
Plugin Name: WPML Page Order
Plugin URI: http://web-profile.com.ua/wordpress/plugins/wpml-page-order/
Description: WPML Page Order allows you to set the order of pages through a drag and drop interface. Order of translated pages updates automatically with the main pages.
Version: 2.1.0
Author: web-dev
Author Email: vitaly.mylo(a)gmail.com
Author URI: http://web-profile.com.ua/wordpress/
*/

function wpmlpageorder_menu()
{    
	add_pages_page(__('WPML Page Order', 'wpmlpageorder'), __('WPML Page Order', 'wpmlpageorder'), 'edit_pages', 'wpmlpageorder', 'wpmlpageorder');
}

function wpmlpageorder_js_libs() {
	if ( isset($_GET['page']) && $_GET['page'] == "wpmlpageorder" ) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
	}
}

function wpmlpageorder_set_plugin_meta($links, $file) {
	$plugin = plugin_basename(__FILE__);
	// create link
	if ($file == $plugin) {
		return array_merge( $links, array( 
			'<a href="' . wpmlpageorder_getTarget() . '">' . __('Order Pages', 'wpmlpageorder') . '</a>',
			'<a href="http://wordpress.org/tags/my-page-order?forum_id=10">' . __('Support Forum', 'wpmlpageorder') . '</a>',
			'<a href="http://geekyweekly.com/gifts-and-donations">' . __('Donate', 'wpmlpageorder') . '</a>' 
		));
	}
	return $links;
}

add_filter('plugin_row_meta', 'wpmlpageorder_set_plugin_meta', 10, 2 );
add_action('admin_menu', 'wpmlpageorder_menu');
add_action('admin_print_scripts', 'wpmlpageorder_js_libs');

function wpmlpageorder()
{
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
	$success = wpmlpageorder_updateOrder();
}

$subPageStr = wpmlpageorder_getSubPages($parentID);
?>

<div class='wrap'>
<form name="frmMyPageOrder" method="post" action="">
	<h2><?php _e('My Page Order', 'wpmlpageorder') ?></h2>
	<?php 
	echo $success;

	?>
	
	<p><?php _e('Choose a page from the drop down to order its subpages or order the pages on this level by dragging and dropping them into the desired order.', 'wpmlpageorder') ?></p>
	
	<?php
 	if($subPageStr != "") 
	{ ?>
	
	<h3><?php _e('Order Subpages', 'wpmlpageorder') ?></h3>
	<select id="pages" name="pages">
		<?php echo $subPageStr; ?>
	</select>
	&nbsp;<input type="submit" name="btnSubPages" class="button" id="btnSubPages" value="<?php _e('Order Subpages', 'wpmlpageorder') ?>" />
	<?php 
	} 
	?>

	<h3><?php _e('Order Pages', 'wpmlpageorder') ?></h3>
	
	<ul id="order">
	<?php
	$results = wpmlpageorder_pageQuery($parentID);
	foreach($results as $row)
		echo "<li id='id_$row->ID' class='lineitem'>".__($row->post_title)."</li>";
	?>
	</ul>

	<input type="submit" name="btnOrderPages" id="btnOrderPages" class="button-primary" value="<?php _e('Click to Order Pages', 'wpmlpageorder') ?>" onclick="javascript:orderPages(); return true;" />
	<?php echo wpmlpageorder_getParentLink($parentID); ?>
	&nbsp;&nbsp;<strong id="updateText"></strong>

	<input type="hidden" id="hdnMyPageOrder" name="hdnMyPageOrder" />
	<input type="hidden" id="hdnParentID" name="hdnParentID" value="<?php echo $parentID; ?>" />
</form>
</div>

<style type="text/css">
	ul#order {
		width: 90%; margin:20px 20px 20px 0; padding:20px 20px 10px 20px; 
		border:1px dashed #B2B2B2; border-radius:3px; -moz-border-radius:3px; list-style:none;
	}
	li.lineitem {
		margin:0 0 10px 0; padding:2px 5px; background-color:#F1F1F1; 
		border:1px dashed #B2B2B2; border-radius:3px; -moz-border-radius:3px; cursor:move;
	}
	li.ui-sortable-helper {
		opacity: 0.5;
	}
	option.active {
		font-weight:bold;
	}
	

	
	.sortable-placeholder{ 
		border:1px dashed #B2B2B2;
		margin-top:5px;
		margin-bottom:5px; 
		padding: 2px 5px 2px 5px;
		height:1.5em;
		line-height:1.5em;	
	}
</style>

<script type="text/javascript">
// <![CDATA[

	function wpmlpageorderaddloadevent(){
		jQuery("#myPageOrderList").sortable({ 
			placeholder: "sortable-placeholder", 
			revert: false,
			tolerance: "pointer" 
		});
	};

	addLoadEvent(wpmlpageorderaddloadevent);
	
	function orderPages() {
		jQuery("#updateText").html("<?php _e('Updating Page Order...', 'wpmlpageorder') ?>");
		jQuery("#hdnMyPageOrder").val(jQuery("#myPageOrderList").sortable("toArray"));
	}

// ]]>
</script>
<?php
}

//Switch page target depending on version
function wpmlpageorder_getTarget() {
	global $wp_version;
	if (version_compare($wp_version, "2.99", ">"))
		return "edit.php?post_type=page&page=wpmlpageorder";
	else
		return "edit-pages.php?page=wpmlpageorder";
}

function wpmlpageorder_updateOrder(){
	if (isset($_POST['hdnMyPageOrder']) && $_POST['hdnMyPageOrder'] != "") {
		global $wpdb;

		$hdnMyPageOrder = $_POST['hdnMyPageOrder'];
		$IDs = explode(",", $hdnMyPageOrder);
		$result = count($IDs);

		for($i = 0; $i < $result; $i++)
		{
			$str = str_replace("id_", "", $IDs[$i]);
			$wpdb->query("UPDATE $wpdb->posts SET menu_order = '$i' WHERE id ='$str'");
			
			$trid_row = $wpdb->get_row("SELECT * FROM wp_icl_translations WHERE element_id = '$str' ", ARRAY_A);
			$trid = $trid_row['trid']; // get trid (id of the translation triad)
			$settings = get_option('icl_sitepress_settings');
			$wpml_page_order_def_lang = $settings['default_language'];
			$wpml_page_order_all_langs = $settings['default_categories'];
			
			// saving order to translated pages
			foreach ($wpml_page_order_all_langs as $lang => $value){
				if($lang != $wpml_page_order_def_lang){
					$page = $wpdb->get_row("SELECT * FROM wp_icl_translations WHERE trid = '$trid' AND element_type = 'post_page' AND language_code = '$lang' ", ARRAY_A);
					$page_id = $page['element_id'];
					$wpdb->query("UPDATE $wpdb->posts SET menu_order = '$i' WHERE id ='$page_id' ");
				}
			}
			
		}

		return '<div id="message" class="updated fade"><p>'. __('Page order updated successfully.', 'wpmlpageorder').'</p></div>';
	}else{
		return '<div id="message" class="updated fade"><p>'. __('An error occured, order has not been saved.', 'wpmlpageorder').'</p></div>';
	}
}

function wpmlpageorder_getSubPages($parentID){
	global $wpdb;
	
	$subPageStr = "";
	$results = wpmlpageorder_pageQuery($parentID);
	foreach($results as $row)
	{
		$postCount=$wpdb->get_row("SELECT count(*) as postsCount FROM $wpdb->posts WHERE post_parent = $row->ID and post_type = 'page' AND post_status != 'trash' AND post_status != 'auto-draft' ", ARRAY_N);
		if($postCount[0] > 0)
	    	$subPageStr = $subPageStr."<option value='$row->ID'>".__($row->post_title)."</option>";
	}
	return $subPageStr;
}

function wpmlpageorder_pageQuery($parentID){ // +++
	global $wpdb;
	//return $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_parent = $parentID and post_type = 'page' AND //post_status != 'trash' AND post_status != 'auto-draft' ORDER BY menu_order ASC");
	
	$settings = get_option('icl_sitepress_settings');
	$wpml_page_order_def_lang = $settings['default_language'];
	return $wpdb->get_results("SELECT * FROM $wpdb->posts LEFT JOIN wp_icl_translations ON (wp_posts.id = wp_icl_translations.element_id) WHERE (wp_icl_translations.language_code = '$wpml_page_order_def_lang') AND post_parent = $parentID AND post_type = 'page' AND post_status = 'publish' AND
	post_status != 'trash' AND post_status != 'auto-draft'
	GROUP BY wp_posts.id
	ORDER BY menu_order ASC");
	
}

function wpmlpageorder_getParentLink($parentID){
	if($parentID != 0)
		return "&nbsp;&nbsp;<input type='submit' class='button' id='btnReturnParent' name='btnReturnParent' value='" . __('Return to parent page', 'wpmlpageorder') ."' />";
	else
		return "";
}

add_action('init', 'wpmlpageorder_loadtranslation');

function wpmlpageorder_loadtranslation() {
	load_plugin_textdomain('wpmlpageorder', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
}

