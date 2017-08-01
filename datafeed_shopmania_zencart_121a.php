<?php
ini_set('memory_limit', '256M');//
ini_set('zend.enable_gc', '1');//steve vain hope to reduce memory useage

$debug = false;//steve, true enables various echos scattered about and applies a limit to work with a smaller set of results
//steve changes : compare to 1.21 from Shopmania to see the differences
//removal of obsolete mysql_functions, local configure, master category in sql (allow cat id 730!), boilerplate text, product description filters (remove embedded youtube, images and my shop-specific elements)

##########################################################################################
# In order to be able to use this script you need to join the merchant program depending on the country where your store is selling the products
#
# AUSTRALIA - http://shopmania.com.au/ (only supporting AUD, NZD datafeeds)
# ARGENTINA - http://www.shopmania.com.ar/ (only supporting ARS, EUR, USD)       *NEW
# BRASIL - http://www.shopmania.com.br/ (only supporting BRL, USD) 
# BULGARY - http://www.shopmania.bg/ (only supporting BGN, EUR, USD)
# CZECH REPUBLIC - http://www.shop-mania.cz/ (only supporting CZK, EUR, USD)		*NEW
# CHILE - http://www.shopmania.cl/ (only supporting CLP, USD, EUR)       *NEW
# CHINA - http://www.shopmania.cn/ (only supporting CNY, USD)       
# DEUTSCHLAND - http://www.shopmania.de/ (only supporting EUR, USD) 
# FRANCE - http://www.shopmania.fr/ (only supporting EUR, USD datafeeds)
# HUNGARY - http://www.shopmania.hu/ (only supporting HUF, EUR, USD datafeeds)
# INDIA - http://www.shopmania.in/ (only supporting INR, USD datafeeds)
# IRELAND - http://www.shopmania.ie/ (only supporting EUR, GBP datafeeds)
# ITALY - http://www.shopmania.it/ (only supporting EUR, USD datafeeds)
# JAPAN - http://www.shopmania.jp/  (only supporting JPY, USD datafeeds)       
# MEXICO - http://www.shopmania.com.mx/ (only supporting MXN (Mexican peso), USD, EUR datafeeds)
# NETHERLANDS - http://www.shopmania.nl/ (only supporting EUR datafeeds)		*NEW
# POLSKA - http://www.shopmania.pl/ (only supporting PLN, EUR, USD) 
# PORTUGAL - http://www.shopmania.pt/ (only supporting EUR, USD) 
# ROMANIA - http://www.shopmania.ro/ (only supporting RON, EUR, USD datafeeds)
# RUSSIA - http://www.shopmania.ru/ (only supporting RUB, EUR, USD)       
# SERBIA - http://www.shopmania.rs/ (only supporting RSD, EUR)		*NEW	
# SLOVAKIA - http://www.shop-mania.sk/ (only supporting EUR, USD)
# SOUTH AFRICA - http://www.shopmania.co.za/ (only supporting ZAR, USD, EUR)       *NEW
# SPAIN - http://www.shopmania.es/ (only supporting EUR datafeeds) 
# SWEDEN - http://www.shopmania.se/ (only supporting SEK, EUR, USD datafeeds)		*NEW
# TURKEY - http://www.shopmania.com.tr/ (only supporting TRY, EUR, USD)
# US - http://www.shopmania.com/ (only supporting USD, CAD datafeeds)
# UK - http://www.shopmania.co.uk/ (only supporting GBP, EUR, USD datafeeds)
#
# Once you join the program and your application is approved you need to place the file on your server and set up the path to the file on the Merchant Interface
# Files will be  retrieved daily from your server having the products listed automatically on ShopMania
# 
# 
# Options
# @url_param get=options - shows options available
#
# @url_param taxes=on (on,off) 
# @url_param storetaxes=on (on,off) 
# @url_param discount=on (on,off) 
# @url_param add_vat=off (on,off) 
# @url_param vat_value=24 (VAT_VALUE) 
# @url_param shipping=off (on,off) 
# @url_param add_tagging=on (on,off) 
# @url_param tagging_params=utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link (TAGGING_PARAMS) 
# @url_param description=on (on,off) 
# @url_param image=on (on,off) 
# @url_param specialprice=on (on,off) 
# @url_param sef=off (on,off) 
# @url_param on_stock=off (on,off) 
# @url_param forcepath=off (on,off) 
# @url_param forcefolder= (FORCEFOLDER) 
# @url_param language= (LANGUAGE_CODE) 
# @url_param language_id= (LANGUAGE_ID) 
# @url_param currency= (CURRENCY_CODE) 
#
#  
#############################################################################################

// Current datafeed script version
$script_version = "1.21";

// Print current Script version
if (@$_GET['get'] == "version") {
	echo "<b>Datafeed Zen Cart</b><br />";
	echo "script version <b>" . $script_version . "</b><br />";
	exit;
}

// Set no time limit only if php is not running in Safe Mode
if (!ini_get("safe_mode")) {
	if (function_exists('set_time_limit')) {
		@set_time_limit(0);
	}
}

ignore_user_abort();
error_reporting(E_ALL^E_NOTICE);
$_SVR = array();


##### Include configuration files ################################################

// Path to configure.php file
$site_base_path = "./";

//define('IS_ADMIN_FLAG', true);//steve added in 1.21 WHY?

// Include configuration file
if(!file_exists($site_base_path . "includes/configure.php")) {
	exit('<HTML><HEAD><TITLE>404 Not Found</TITLE></HEAD><BODY><H1>Not Found</H1>Please ensure that this file is in the root directory, or make sure the path to the directory where the configure.php file is located is defined corectly above in $site_base_path variable</BODY></HTML>');
}
elseif (file_exists($site_base_path . "includes/local/configure.php")) {
	//include_once($site_base_path . "includes/local/configure.php"); //steve not needed, application top includes the normal/local configure
	include_once($site_base_path . "includes/application_top.php");
} else {
    //include_once($site_base_path . "includes/configure.php");
	include_once($site_base_path . "includes/application_top.php");
}

####################################################################

// Datafeed specific settings
$datafeed_separator = "|"; // Possible options are \t or |
// You can also leave this field empty. Please contact us if you are planning to submit the feeds in a different currency

##### Extract params from url ################################################

$apply_taxes = (@$_GET['taxes'] == "off") ? "off" : "on";
$apply_storetaxes = (@$_GET['storetaxes'] == "off") ? "off" : "on";
$apply_discount = (@$_GET['discount'] == "off") ? "off" : "on";
$add_vat = (@$_GET['add_vat'] == "on") ? "on" : "off";
$vat_value = (@$_GET['vat_value'] > 0) ? ((100 + $_GET['vat_value']) / 100) : 1.24; // default value
$add_shipping = (@$_GET['shipping'] == "off") ? "off" : "on";
$add_availability = (@$_GET['availability'] == "off") ? "off" : "on";
$add_gtin = (@$_GET['gtin'] == "off") ? "off" : "on";
$add_tagging = (@$_GET['add_tagging'] == "off") ? "off" : "on";
$tagging_params = (@$_GET['tagging_params'] != "") ? urldecode($_GET['tagging_params']) : "utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link";
$show_description = (@$_GET['description'] == "off") ? "off" : ((@$_GET['description'] == "limited") ? "limited" : "on");
$show_image = (@$_GET['image'] == "off") ? "off" : "on";
$show_specialprice = (@$_GET['specialprice'] == "off") ? "off" : "on";
$sef = (@$_GET['sef'] == "on") ? "on" : "off";
$on_stock_only = (@$_GET['on_stock'] == "on") ? "on" : "off";
$force_path = (@$_GET['forcepath'] == "on") ? "on" : "off";
$force_folder = (@$_GET['forcefolder'] != "") ? $_GET['forcefolder'] : "";
$language_code = (@$_GET['language'] != "") ? $_GET['language'] : "";
$language_id = (@$_GET['language_id'] != "") ? $_GET['language_id'] : "";
$currency = (@$_GET['currency_code'] != "") ? $_GET['currency_code'] : "";
$use_compression = (@$_GET['compression'] == "on") ? "on" : "off";
$limit = (@$_GET['limit'] != "") ? $_GET['limit'] : "";
$show_combinations = (@$_GET['combinations'] == "on") ? "on" : "off";
$show_attribute = (@$_GET['attribute'] == "on") ? "on" : "off";
####################################################################

if ($use_compression == "on") {
	// Start compressing
	smfeed_compression_start();
}

// Print URL options
if (@$_GET['get'] == "options") {
	$script_basepath = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
	
	echo "<b>Datafeed Zen Cart</b><br />";
	echo "script version <b>" . $script_version . "</b><br /><br /><br />";
		
	echo "<b>Taxes options</b> - possible values on, off default value on<br />";
	echo "taxes=on (on,off) <a href=\"" . $script_basepath . "?taxes=off" . "\" >" . $script_basepath . "?taxes=off" . "</a><br /><br />";
	
	//echo "Store taxes options - possible values on, off default value on<br />";
	//echo "storetaxes = on (on,off) <a href=\"" . $script_basepath . "?storetaxes=off" . "\" >" . $script_basepath . "?storetaxes=off" . "</a><br /><br />";
	
	//echo "Discount options - possible values on, off default value on<br />";
	//echo "discount=on (on,off) <a href=\"" . $script_basepath . "?discount=off" . "\" >" . $script_basepath . "?discount=off" . "</a><br /><br />";
	
	echo "<b>Add VAT to prices</b> - possible values on, off default value off<br />";
	echo "add_vat=off (on,off) <a href=\"" . $script_basepath . "?add_vat=on" . "\" >" . $script_basepath . "?add_vat=on" . "</a><br /><br />";
	
	echo "<b>VAT value</b> - possible values percent value default value 24  - interger or float number ex 19 or 19.5<br />";
	echo "vat_value=24 (VAT_VALUE) <a href=\"" . $script_basepath . "?add_vat=on&vat_value=19" . "\" >" . $script_basepath . "?add_vat=on&vat_value=19" . "</a><br /><br />";
	
	echo "<b>Add shipping to datafeed</b> - possible values on, off default value on<br />";
	echo "shipping=on (on,off) <a href=\"" . $script_basepath . "?shipping=off" . "\" >" . $script_basepath . "?shipping=off" . "</a><br /><br />";
	
	echo "<b>Add availability to datafeed</b> - possible values on, off default value on<br />";
	echo "availability=on (on,off) <a href=\"" . $script_basepath . "?availability=off" . "\" >" . $script_basepath . "?availability=off" . "</a><br /><br />";
	
	//echo "<b>Add GTIN to datafeed</b> - possible values on, off default value on<br />";
	//echo "gtin=on (on,off) <a href=\"" . $script_basepath . "?gtin=off" . "\" >" . $script_basepath . "?gtin=off" . "</a><br /><br />";
		
	echo "<b>Add GA Tagging to product URL</b> - possible values on, off default value on<br />";
	echo "add_tagging=on (on,off) <a href=\"" . $script_basepath . "?add_tagging=off" . "\" >" . $script_basepath . "?add_tagging=off" . "</a><br /><br />";
	
	echo "<b>Add custom tagging to product URL</b> - possible values url_encode(TAGGING_PARAMS) default value tagging_params=&utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link<br />";
	echo "tagging_params=&utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link (TAGGING_PARAMS) <a href=\"" . $script_basepath . "?tagging_params=%26from%3Dshopmania" . "\" >" . $script_basepath . "?tagging_params=%26from%3Dshopmania" . "</a><br /><br />";
	
	echo "<b>Display Description options</b> - possible values on, off, limited default value on<br />";
	echo "description=on (on,off) <ul><li><a href=\"" . $script_basepath . "?description=off" . "\" >" . $script_basepath . "?description=off" . "</a></li>";
	echo "<li><a href=\"" . $script_basepath . "?description=limited" . "\" >" . $script_basepath . "?description=limited" . "</a></li></ul>";

	echo "<b>Display image options</b> - possible values on, off default value on<br />";
	echo "image=on (on,off) <a href=\"" . $script_basepath . "?image=off" . "\" >" . $script_basepath . "?image=off" . "</a><br /><br />";
	
	//echo "Special price options - possible values on, off default value on<br />";
	//echo "specialprice=on (on,off) <a href=\"" . $script_basepath . "?specialprice=off" . "\" >" . $script_basepath . "?specialprice=off" . "</a><br /><br />";
	
	echo "<b>Show only on stock products</b> - possible values on, off default value off<br />";
	echo "on_stock=off (on,off) <a href=\"" . $script_basepath . "?on_stock=on" . "\" >" . $script_basepath . "?on_stock=on" . "</a><br /><br />";
	
	echo "<b>Show SEO friendly url</b> - possible values on, off default value off<br />";
	echo "sef=off (on,off) <a href=\"" . $script_basepath . "?sef=on" . "\" >" . $script_basepath . "?sef=on" . "</a><br /><br />";
	
	echo "<b>Get prices in specified currency</b> - possible values USD,EUR etc. <br />";
	echo "currency_code=DEFAULT_CURRENCY <a href=\"" . $script_basepath . "?currency_code=EUR" . "\" >" . $script_basepath . "?currency_code=EUR" . "</a><br /><br />";
	
	echo "<b>Get texts in specified language code</b> - possible values en,ro etc. <br />";
	echo "language=DEFAULT_LANGUAGE_CODE <a href=\"" . $script_basepath . "?language=en" . "\" >" . $script_basepath . "?language=en" . "</a><br /><br />";
	
	echo "<b>Get texts in specified language id</b> - possible values 1,2 etc. <br />";
	echo "language_id=DEFAULT_LANGUAGE_ID <a href=\"" . $script_basepath . "?language_id=1" . "\" >" . $script_basepath . "?language_id=1" . "</a><br /><br />";
	
	echo "<b>Limit displayed products</b> - possible values integer (start,step)<br />";
	echo "limit=no_limit <a href=\"" . $script_basepath . "?limit=0,10" . "\" >" . $script_basepath . "?limit=0,10" . "</a><br /><br />";
		
	echo "<b>Use compression</b> - possible values on, off default value off<br />";
	echo "compression=off (on,off) <a href=\"" . $script_basepath . "?compression=on" . "\" >" . $script_basepath . "?compression=on" . "</a><br /><br />";
	
	echo "<b>Get feed paginated</b> - possible values 1,2,..  etc. <br />";
	echo "pg=PAGE <a href=\"" . $script_basepath . "?pg=1" . "\" >" . $script_basepath . "?pg=1" . "</a><br />";
	echo "pg=PAGE&limit=PAGE_SIZE <a href=\"" . $script_basepath . "?pg=1&limit=100" . "\" >" . $script_basepath . "?pg=1&limit=100" . "</a><br /><br />";
	
	echo "<b>Display product combinations</b> - possible values on, off default value on<br />";
	echo "combinations=off (on,off) <a href=\"" . $script_basepath . "?combinations=on" . "\" >" . $script_basepath . "?combinations=on" . "</a><br /><br />";
	
	echo "<b>Display product attributess</b> - possible values on, off default value off<br />";
	echo "attribute=off (on,off) <a href=\"" . $script_basepath . "?attribute=on" . "\" >" . $script_basepath . "?attribute=on" . "</a><br /><br />";
		
	echo "<br />";
	
	exit;
	
}

##### Connect to the database ################################################
//steve all these mysqli direct calls could be changed as connexion already established by the use of application top
$conn = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD);//steve mysql_connect was deprecated
if (mysqli_connect_errno()) {//steve mysql_error was deprecated
	print "<HTML><HEAD><TITLE>404 Not Found</TITLE></HEAD><BODY><H1>Not Found</H1>Connection error. Please check the connection settings. Bye bye...</BODY></HTML>";
	exit;
}
else {
	mysqli_select_db($conn, DB_DATABASE);//steve mysql_select_db was deprecated
	if (mysqli_connect_errno()) {//steve mysql_error was deprecated
		print "<HTML><HEAD><TITLE>404 Not Found</TITLE></HEAD><BODY><H1>Not Found</H1>Connection error. Please check the connection settings. Bye bye...</BODY></HTML>";
		exit;
	}
}

// Set db charset encoding
if (defined('DB_CHARSET') && version_compare(@mysqli_get_server_info($conn), '4.1.0', '>=')) {//steve mysql_get_server_info deprecated
	mysqli_query($conn, "SET NAMES '" . DB_CHARSET . "'");//steve mysql_query was deprecated
    mysqli_set_charset($conn, DB_CHARSET);//steve mysql_set_charset was deprecated
}

######################################################################


##### Extract options from database ################################################

if ($language_id > 0) {
	// Set the main language
	$main_language = $language_id;
}
elseif ($language_code != "") {
	// Get language ID
	$query_language_id = mysqli_query($conn, "SELECT languages_id FROM " . DB_PREFIX . "languages WHERE code = '" . addslashes($language_code) . "'");//steve removed deprecated
	$row_language_id = mysqli_fetch_array($query_language_id);//steve was deprecated
	
	// Set the main language
	$main_language = $row_language_id['languages_id'];
}
else {
	// Detect default language code
	$query_language_code = mysqli_query($conn, "SELECT configuration_value FROM " . DB_PREFIX . "configuration WHERE configuration_key = 'DEFAULT_LANGUAGE'");//steve was deprecated
	$row_language_code = mysqli_fetch_array($query_language_code);//steve was deprecated

	// Detect default language ID
	$query_language_id = mysqli_query($conn, "SELECT languages_id FROM " . DB_PREFIX . "languages WHERE code = '" . addslashes($row_language_code['configuration_value']) . "'");//steve was deprecated
	$row_language_id = mysqli_fetch_array($query_language_id);//steve was deprecated

	// Set the main language
	$main_language = $row_language_id['languages_id'];
}

if ($currency != "") {
	$row_currency['configuration_value'] = strtoupper($currency);
}
else {
	// Detect default currency
	$query_currency = mysqli_query($conn, "SELECT configuration_value FROM " . DB_PREFIX . "configuration WHERE configuration_key = 'DEFAULT_CURRENCY'");//steve was deprecated
	$row_currency = mysqli_fetch_array($query_currency);//steve was deprecated
}

// Get default currency value
$query_currency_rate = mysqli_query($conn, "SELECT * FROM " . DB_PREFIX . "currencies WHERE code = '" . $row_currency['configuration_value'] . "'");//steve was deprecated, and used ZC constant style for table
$row_currency_rate = mysqli_fetch_array( $query_currency_rate );//steve was deprecated

// Get currency code
if (preg_match("/ron/i", $row_currency_rate['symbol_right']) || preg_match("/lei/i", $row_currency_rate['symbol_right'])) {//steve eregi deprecated
	$datafeed_currency = "RON";
}
else {
	$datafeed_currency = $row_currency['configuration_value'];
}

######################################################################


##### Extract products from database ###############################################

$prod_table = DB_PREFIX . "products"; // products table
$prod_specials_prices = DB_PREFIX . "specials"; // special prices table
$prod_desc_table = DB_PREFIX . "products_description"; // products descriptions table
$cat_prod_table = DB_PREFIX . "products_to_categories"; // products to categories table
$cat_table = DB_PREFIX . "categories"; // categories table
$cat_desc_table = DB_PREFIX . "categories_description"; // categories descriptions table
$cat_manuf_table = DB_PREFIX . "manufacturers"; // manufacturers table
$prod_attributes_table = DB_PREFIX . "products_attributes"; // products attributes table

// Get all categories
$res_cat = mysqli_query($conn, "SELECT * 
FROM $cat_table
LEFT JOIN $cat_desc_table ON ( $cat_table.categories_id = $cat_desc_table.categories_id AND $cat_desc_table.language_id = '" . addslashes($main_language) . "' )");//steve was deprecated
$CAT_ARR = array();//steve added declaration
if (!mysqli_connect_errno()) {//steve mysql_error deprecated
	while ($field = mysqli_fetch_assoc($res_cat)) {
		$CAT_ARR[$field['categories_id']] = $field;
	}
}
//echo __LINE__.' count $CAT_ARR='.count($CAT_ARR).'<br />';//steve debug

// Detect default shipping value
$query_shipping_flat = mysqli_query($conn, "SELECT configuration_value FROM " . DB_PREFIX . "configuration WHERE configuration_key = 'MODULE_SHIPPING_FLAT_STATUS'");//steve was deprecated
$shipping_flat = mysqli_fetch_array($query_shipping_flat);//steve was deprecated
$shipping_flat_value = '';//steve added declaration
if (strtolower($shipping_flat['configuration_value']) == "true") {
	$query_shipping_flat_value = mysqli_query($conn, "SELECT configuration_value FROM " . DB_PREFIX . "configuration WHERE configuration_key = 'MODULE_SHIPPING_FLAT_COST'");//steve was deprecated
	$shipping_flat_result = mysqli_fetch_array($query_shipping_flat_value);//steve was deprecated
	$shipping_flat_value = $shipping_flat_result['configuration_value'];
}

// Query database for extracting data. It might be needed to left join the categories table for extracting the category name
//$image_field = ($show_image == "on") ? "$prod_table.products_image" : "''";
//steve this should always be on as used in the while - list loop
$image_field = "$prod_table.products_image";

if ($show_description == "limited") {
	$description_field = "SUBSTRING($prod_desc_table.products_description, 1,600) AS products_description";
}
elseif($show_description == "on") {
	$description_field = "$prod_desc_table.products_description";
}
else {
	$description_field = "''";
}

//$on_stock_cond = ( ((STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true')) || ($on_stock_only == "on")) ? "AND $prod_table.products_quantity > 0" : "";
$on_stock_cond = ($on_stock_only == "on") ? "AND $prod_table.products_quantity > 0" : "";

$add_limit = "";
if (isset($_GET['pg']) && @$_GET['pg'] > 0) {
	$_step = ($limit > 0) ? $limit : 1000;
	$_start = ($_GET['pg'] - 1) * $_step;
	$_start = ($_start >= 0) ? $_start : 0;
	$add_limit = " LIMIT " . $_start . "," . $_step;
}

if ($debug) $add_limit = " LIMIT 0,100 ";//steve debug

//SM 1.21 gets first category id instead of the master category id
//also excluded category id 730 for no apparent reason
/*
$q = "SELECT $cat_table.categories_id, $cat_manuf_table.manufacturers_name, $prod_table.products_model, $prod_table.products_id, $prod_desc_table.products_name, " . $description_field . ", " . $image_field . ", $prod_table.products_price, $prod_table.products_tax_class_id, $prod_table.products_priced_by_attribute, $prod_table.products_quantity
FROM $prod_table
LEFT JOIN $prod_desc_table ON ( $prod_table.products_id = $prod_desc_table.products_id AND $prod_desc_table.language_id = '$main_language' )
LEFT JOIN $cat_prod_table ON $prod_table.products_id = $cat_prod_table.products_id
LEFT JOIN $cat_table ON $cat_prod_table.categories_id = $cat_table.categories_id
LEFT JOIN $cat_manuf_table ON $prod_table.manufacturers_id = $cat_manuf_table.manufacturers_id
WHERE $cat_table.categories_status > 0 AND $cat_table.categories_id != '730' AND $prod_table.products_status > 0 " . addslashes($on_stock_cond) . "
ORDER BY $prod_table.products_id ASC, $cat_table.parent_id DESC" . addslashes($add_limit);
*/

//steve Shopmania lists only does 2000 products for free, the rest are ignored
//modified to get only the master category id of the product, AND products with a price AND products with an image
$q = "SELECT $prod_table.master_categories_id, $cat_manuf_table.manufacturers_name, $prod_table.products_model, $prod_table.products_id, $prod_desc_table.products_name, " . $description_field . ", " . $image_field . ", $prod_table.products_price, $prod_table.products_tax_class_id, $prod_table.products_priced_by_attribute, $prod_table.products_quantity, $prod_table.product_is_call
FROM $prod_table
LEFT JOIN $prod_desc_table ON ( $prod_table.products_id = $prod_desc_table.products_id AND $prod_desc_table.language_id = '$main_language' )
LEFT JOIN $cat_table ON ($cat_table.categories_id = $prod_table.master_categories_id ) 
LEFT JOIN $cat_manuf_table ON $prod_table.manufacturers_id = $cat_manuf_table.manufacturers_id
WHERE $prod_table.products_status = 1 AND $prod_table.product_is_call = 0 AND $prod_table.products_price > 0 AND $prod_table.products_image > '' AND $prod_table.products_image <> 'no_picture_es.gif' " . addslashes($on_stock_cond) . "
ORDER BY $prod_table.products_model ASC" . addslashes($add_limit);

//echo __LINE__.' '.$q.'<br />';//steve debug

$r = mysqli_query($conn, $q);//steve mysql_query was deprecated

###################################################################


##### Print product data ####################################################

$current_id = 0;

// There are no errors so we can continue
if (!mysqli_connect_errno()) {//steve mysql_error deprecated
$entry = 0;$total_products = mysqli_num_rows($r);//steve debug
	while (list($cat_id, $prod_manufacturer, $prod_model, $prod_id, $prod_name, $prod_desc, $prod_image, $prod_price, $tax_class_id, $products_priced_by_attribute, $products_quantity, $product_is_call) = mysqli_fetch_row($r)) {//steve mysql_fetch_row deprecated
//steve debug
$entry ++;
        if ($debug) echo '<hr />'.__LINE__.': entry='.$entry.'/'.$total_products.', memory used='.round(memory_get_usage()/1048576,2).'MB<br />';//steve
        //steve to check "list": reversed for php7
//echo '<hr /><p>'.__LINE__.' '.$entry.' $cat_id='.$cat_id.', $prod_manufacturer='.$prod_manufacturer.', $prod_model='.$prod_model.', $prod_id='.$prod_id.', $prod_name='.$prod_name.'<br />$prod_desc='.mb_strimwidth(strip_tags($prod_desc), 0, 170, "...").'<br />$prod_image='.$prod_image.', $prod_price='.$prod_price.', $tax_class_id='.$tax_class_id.', $products_priced_by_attribute='.$products_priced_by_attribute.', $products_quantity='.$products_quantity.'</p>';//steve debug

		// If we've sent this one, skip the rest - this is to ensure that we do not get duplicate products
		if ($current_id == $prod_id) {
			continue;
		}
		else {
			$current_id = $prod_id;
		}
		//steve boilerplate text
        if ( function_exists('mv_get_boilerplate') ) {
            //remove generic info from product descriptions
            $boilerplate_constants_to_remove = array("ATEQ_TPMS_UPDATE","ATEQ_TPMS_COMPANY","GENERIC_PRODUCT_COMPATIBILITY_WARNING","HEALTECH_PA_LINK","HEALTECH_PA_FULL","HEXCODE_GS911_OFFICIAL","MV_ALL_INSTRUCTIONS_SPANISH","MV_NO_MODS_REQUIRED","RG_CHECK_COMPATIBILITY","RG_INSURANCE",);
            $prod_desc = str_replace($boilerplate_constants_to_remove, '', $prod_desc);
            //replace common product info
            $prod_desc = mv_get_boilerplate($prod_desc, $descr_stringlist);
        }
		//eof steve

		// Get the attributes name and values for each product
		if ( ($show_attribute == "on") || ($show_combinations == "on")) {
			$attr_query = " SELECT options_id, options_values_id, options_values_price, price_prefix, products_options_values.products_options_values_name,products_options.products_options_name
							FROM products_attributes
							LEFT JOIN products_options_values ON (products_attributes.options_values_id = products_options_values.products_options_values_id)
							LEFT JOIN products_options ON (products_attributes.options_id = products_options.products_options_id)
							WHERE products_attributes.products_id = " . $prod_id;
			
			$res_attr = mysqli_query($conn, $attr_query);//steve was deprecated
			$ATTR_NAME = array();
			$ATTR_VALUES = array();
			$ATTR_KEYS = array();
			$TEMP_PRICE_ADD = array();
			
			while ($field = mysqli_fetch_assoc($res_attr)) {//steve mysql_fetch_assoc deprecated
				$ATTR_NAME[$field['options_id']] = $field['products_options_name'];
				$ATTR_VALUES[$field['options_id']][$field['options_values_id']] = $field['products_options_values_name'];
				$ATTR_KEYS[$field['options_id']][$field['options_values_id']] = $field['options_values_id'];
				$TEMP_PRICE_ADD[$field['options_id']][$field['options_values_id']] = ($field['price_prefix'] == "" ? "+" : $field['price_prefix']) . " " . $field['options_values_price'];
			}
			if ($show_attribute == "on") {
				if (is_array($ATTR_VALUES) && sizeof($ATTR_VALUES) > 0 ) {
					$attr = "";
					$ATTR = array();
					$TEMP_VALS = array();
					foreach ($ATTR_VALUES as $i=>$v) {
						$TEMP_VALS[$i] = join(", ", array_values($v));
					}
					foreach($TEMP_VALS as $i=>$v) {
						$ATTR[] = $ATTR_NAME[$i] . ": " . $v;
					}	
					$attr = join("; ", array_values($ATTR));
				}
			}	
			if ($show_combinations == "on") {
				$COMB_ARR_VAL = array();
				$COMB_ARR = array();
				$COMB_ARR_KEYS = array();
				$ADD_PRICE = array();
                $COMB_ADD_PRICE = array();//steve added, declaration was missing
				// Build product combination name
				if (is_array($ATTR_VALUES) && sizeof($ATTR_VALUES) > 0 ) {
					$COMB_ARR_VAL = cartesian_product($ATTR_VALUES);	
					foreach ($COMB_ARR_VAL as $i=>$v) {
						$COMB_ARR[$i]['name'] = join(", ", array_values($v));
					}
				}
				// Build product combination id
				if (is_array($ATTR_KEYS) && sizeof($ATTR_KEYS) > 0 ) {
					$COMB_ARR_KEYS = cartesian_product($ATTR_KEYS);	
					foreach ($COMB_ARR_KEYS as $i=>$v) {
						$COMB_ARR[$i]['variation_id'] = join("_", array_values($v));
					}
				}
				// Build each combination add price
				if (is_array($TEMP_PRICE_ADD) && sizeof($TEMP_PRICE_ADD) > 0 ) {
					$ADD_PRICE = cartesian_product($TEMP_PRICE_ADD);	
					// print_r(array_values($ADD_PRICE));
					foreach ($ADD_PRICE as $i=>$v) {
						foreach ($v as $ii=>$vv) {
							$COMB_ADD_PRICE[$i] .= " " . $vv;
						}
					}
				}
			}
		}
		
		// Limit description size
		if ($show_description == "limited") {
			$prod_desc = smfeed_clean_description($prod_desc);
			$prod_desc = strip_tags($prod_desc);
			$prod_desc = substr(trim($prod_desc), 0, 300);
		}
		elseif ($show_description == "on") {
			$prod_desc = smfeed_clean_description($prod_desc);
		}
		else {
			$prod_desc = "";
		}
		
		// Get category name
		$category_name = smfeed_get_full_cat($cat_id, $CAT_ARR);
	
		// Clean product name (new lines)
		$prod_name = str_replace("\n", "", strip_tags($prod_name));
		$prod_name = str_replace("\r", "", strip_tags($prod_name));
		$prod_name = str_replace("\t", " ", strip_tags($prod_name));
	
		// Clean product description (Replace new line with <BR>). In order to make sure the code does not contains other HTML code it might be a good ideea to strip_tags()
		$prod_desc = smfeed_replace_not_in_tags("\n", "<BR />", $prod_desc);
		$prod_desc = str_replace("\n", " ", $prod_desc);		
		$prod_desc = str_replace("\r", "", $prod_desc);
		$prod_desc = str_replace("\t", " ", $prod_desc);
				
		if ($sef == "on") {
			$prod_url = smfeed_get_seo_url($prod_id, $prod_name, $cat_id, $CAT_ARR);
			
		}
		else {
			$prod_url = smfeed_get_product_url($prod_id, $cat_id);	
		}		
		
		// Add GA Tagging parameters to url
		if ($add_tagging == "on") {
			$and_param = (preg_match("/\?/", $prod_url)) ? "&" : "?";
			$prod_url = $prod_url . $and_param . $tagging_params;
		}
		
		$prod_image = smfeed_get_product_image($prod_image);
		
		// Get product price
		$display_normal_price = zen_get_products_base_price($prod_id);
	    $display_special_price = zen_get_products_special_price($prod_id, true);
	    $display_sale_price = zen_get_products_special_price($prod_id, false);
		
		if ($display_sale_price > 0) {
			$prod_price = $display_sale_price;
		}
		elseif ($display_special_price > 0) {
			$prod_price = $display_special_price;
		}
		else {
			$prod_price = $display_normal_price;
		}
		
		// Add VAT to prices
		if ($add_vat == "on") {
			$prod_price = $prod_price * $vat_value;
		}
		
		// Apply taxes to price
		if ($apply_taxes == "off") {
			// ok
		}
		else {
			$prod_price = zen_add_tax($prod_price, zen_get_tax_rate($tax_class_id));
		}

		// Apply currency exchange rates
		$prod_price = number_format($row_currency_rate['value'] * $prod_price, 2, ".", "");
		
		// Get product combinations prices
		if ($show_combinations == "on") {
			if (is_array($COMB_ADD_PRICE) && sizeof($COMB_ADD_PRICE) > 0 ) {
				foreach ($COMB_ADD_PRICE as $i=>$v) {
					$COMB_ARR[$i]['price_sum'] = $prod_price . $v;
					$COMB_ARR[$i]['price'] = eval('return '. $COMB_ARR[$i]['price_sum'].';');
				}
			}
		}

		// Clean product names and descriptions (separators)
		if ($datafeed_separator == "\t") {
			$category_name = str_replace("\t", " ", $category_name);
			// Continue... tabs were already removed
		}
		elseif ($datafeed_separator == "|") {
			$prod_name = str_replace("|", " ", strip_tags($prod_name));
			$prod_desc = str_replace("|", " ", $prod_desc);
			$category_name = str_replace("|", " ", $category_name);
		}
		else {
			print "Incorrect columns separator.";
			exit;
		}

		// Build stock conditions
		if (STOCK_CHECK == "true" && $add_availability == "on") {
			if ($products_quantity > 0) {
				$availability = "In stock";
			}
			else {
				if (STOCK_ALLOW_CHECKOUT == "true") {
						$availability = (STOCK_MARK_PRODUCT_OUT_OF_STOCK != "***") ? STOCK_MARK_PRODUCT_OUT_OF_STOCK : "Out of stock";//steve typo corrected
				}
				else {
					$availability = "Out of stock";
				}
			}
		}
		elseif ($add_availability == "on") {
			$availability = "In stock";
		}
		else {
			$availability = "";
		}
		
		//Add Shipping
		$shipping_flat_value = number_format($row_currency_rate['value'] * (float)$shipping_flat_value, 2);//steve added float for php7 warning
		$shipping_value = ($add_shipping == "on" && @$shipping_flat_value > 0) ? $shipping_flat_value : "";
		
		// Add gtin
		$gtin = "";
		
		// Output the datafeed content
		// Category, Manufacturer, Model, ProdCode, ProdName, ProdDescription, ProdURL, ImageURL, Price, Currency, Shipping value, Availability, GTIN (UPC/EAN/ISBN) 
		if ( ($show_combinations == "on") && (is_array($COMB_ARR)) && (sizeof($COMB_ARR) > 0) ) {
			foreach ($COMB_ARR AS $k => $combination) {
				print 
				$category_name . $datafeed_separator .
				$prod_manufacturer . $datafeed_separator .
				$prod_model . $datafeed_separator .
				$prod_id . "_" . $combination['variation_id'] . $datafeed_separator . 
				$prod_name . ", " . $combination['name'] . $datafeed_separator . 
				$prod_desc . $datafeed_separator .
				$prod_url . $datafeed_separator .
				$prod_image . $datafeed_separator .
				$combination['price'] . $datafeed_separator . 
				$datafeed_currency . $datafeed_separator .
				$shipping_value . $datafeed_separator .
				$availability . $datafeed_separator . (($show_attribute == "on") ? $attr . $datafeed_separator . $gtin : $gtin ) . 
				$gtin . "\n";
			}
		}
		else {
			print  
			$category_name . $datafeed_separator .
			$prod_manufacturer . $datafeed_separator .
			$prod_model . $datafeed_separator .
			$prod_id . $datafeed_separator .
			$prod_name . $datafeed_separator . 
			$prod_desc . $datafeed_separator .
			$prod_url . $datafeed_separator .
			$prod_image . $datafeed_separator .
			$prod_price . $datafeed_separator . 
			$datafeed_currency . $datafeed_separator .
			$shipping_value . $datafeed_separator .
			$availability . $datafeed_separator . (($show_attribute == "on") ? $attr . $datafeed_separator . $gtin : $gtin ) . 
			"\n";
		}
	} unset($cat_id, $prod_manufacturer, $prod_model, $prod_id, $prod_name, $prod_desc, $prod_image, $prod_price, $tax_class_id, $products_priced_by_attribute, $products_quantity);//steve vain hope to reduce memory useage
    gc_collect_cycles();//steve vain hope to reduce memory useage
}
else {
	print "<HTML><HEAD><TITLE>404 Not Found</TITLE></HEAD><BODY><H1>Not Found</H1>Query error: " . mysqli_connect_errno() . "</BODY></HTML>";//steve mysql_error deprecated
}

mysqli_close($conn);//steve added
if ($debug) echo __LINE__.'<h2>End</h2>';//steve debug
include_once($site_base_path . "includes/application_bottom.php");
######################################################################


##### Functions ############################################################

// Function to return the Product URL based on your product ID
function smfeed_get_seo_url($prod_id, $prod_name, $cat_id, $CATEGORY_ARR){
	
	$item_arr = $CATEGORY_ARR[$cat_id];
	$i = 0;
	$cat_str_arr[$i] = smfeed_build_str_key(smfeed_html_to_text($item_arr['categories_name']));
	$cat_keys = $item_arr['categories_id'];
	
	while (sizeof($CATEGORY_ARR[$item_arr['parent_id']]) > 0 && is_array($CATEGORY_ARR[$item_arr['parent_id']]) ) {
		
		$i = $i + 1;
		$cat_str_arr[] = smfeed_build_str_key(smfeed_html_to_text($CATEGORY_ARR[$item_arr['parent_id']]['categories_name']));		
		$cat_keys = $CATEGORY_ARR[$item_arr['parent_id']]['categories_id'] . "_" . $cat_keys;
		$item_arr = $CATEGORY_ARR[$item_arr['parent_id']];
	}
	
	$cat_str_key = $cat_str_arr[$i] . "-" . $cat_str_arr[$i - 1];
	
	$prod_str_key = smfeed_build_str_key($prod_name);
	
	if (preg_match("/http\:\/\//", DIR_WS_CATALOG)) {
		return DIR_WS_CATALOG . "product_info/c-" . $cat_str_key . "-" . $cat_keys . "/p-" . $prod_str_key . "-" . $prod_id;
	}
	else {
		return HTTP_SERVER . DIR_WS_CATALOG . "product_info/c-" . $cat_str_key . "-" . $cat_keys . "/p-" . $prod_str_key . "-" . $prod_id;
	}
	
}

// Function to return the Product URL based on your product ID
function smfeed_get_product_url($prod_id, $cat_id){
	
	if (preg_match("/http\:\/\//", DIR_WS_CATALOG)) {
		return DIR_WS_CATALOG . "index.php?main_page=product_info&cPath=" . $cat_id . "&products_id=" . $prod_id;
	}
	else {
		return HTTP_SERVER . DIR_WS_CATALOG . "index.php?main_page=product_info&cPath=" . $cat_id . "&products_id=" . $prod_id;
	}	
}

// Function to return the Product Image based on your product image or optionally Product ID
function smfeed_get_product_image($prod_image){
	
	if ($prod_image == "") {
		return false;
	}
	elseif (preg_match("/http\:\/\//", $prod_image)) {
		return $prod_image;
	}
	elseif (preg_match("/http\:\/\//", DIR_WS_IMAGES)) {
		return DIR_WS_IMAGES . $prod_image;
	}
	elseif (preg_match("/http\:\/\//", DIR_WS_CATALOG)) {
		return DIR_WS_CATALOG . DIR_WS_IMAGES . $prod_image;
	}
	else {
		return HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $prod_image;
	}	
}

// Function to get category with full path
function smfeed_get_full_cat($cat_id, $CATEGORY_ARR) {

	$item_arr = $CATEGORY_ARR[$cat_id];
	$cat_name = $item_arr['categories_name'];
	
	if (@$item_arr['parent_id'] > 0 && isset($CATEGORY_ARR[$item_arr['parent_id']])) {
		while (isset($CATEGORY_ARR[$item_arr['parent_id']]) && @$item_arr['parent_id'] > 0 && sizeof(@$CATEGORY_ARR[@$item_arr['parent_id']]) > 0 && is_array(@$CATEGORY_ARR[@$item_arr['parent_id']]) ) {
			
			$cat_name = $CATEGORY_ARR[$item_arr['parent_id']]['categories_name'] . " > " . $cat_name;		
			$item_arr = $CATEGORY_ARR[$item_arr['parent_id']];
		}
	}
	
	// Strip html from category name
	$cat_name = smfeed_html_to_text($cat_name);
	return $cat_name;
}

function smfeed_html_to_text($string){

	$search = array (
		"'<script[^>]*?>.*?</script>'si",  // Strip out javascript
		"'<[\/\!]*?[^<>]*?>'si",  // Strip out html tags
		"'([\r\n])[\s]+'",  // Strip out white space
		"'&(quot|#34);'i",  // Replace html entities
		"'&(amp|#38);'i",
		"'&(lt|#60);'i",
		"'&(gt|#62);'i",
		"'&(nbsp|#160);'i",
		"'&(iexcl|#161);'i",
		"'&(cent|#162);'i",
		"'&(pound|#163);'i",
		"'&(copy|#169);'i",
		"'&(reg|#174);'i",
		"'&#8482;'i",
		"'&#149;'i",
		"'&#151;'i"
		);  // evaluate as php
	
	$replace = array (
		" ",
		" ",
		"\\1",
		"\"",
		"&",
		"<",
		">",
		" ",
		"&iexcl;",
		"&cent;",
		"&pound;",
		"&copy;",
		"&reg;",
		"<sup><small>TM</small></sup>",
		"&bull;",
		"-",
		);
	
	$text = preg_replace ($search, $replace, $string);
	return $text;
	
}

function smfeed_clean_description($string){

	$search = array (
		"'<html>'i",
		"'</html>'i",
		"'<body>'i",
		"'</body>'i",
		"'<head>.*?</head>'si",
		"'<!DOCTYPE[^>]*?>'si"
		); 
		
	$replace = array (
		"",
		"",
		"",
		"",
		"",
		""
		); 
		
	$text = preg_replace ($search, $replace, $string);
//steve extra (not working in array)
    $text = preg_replace ('#<div id="ht_pa_block"(.|\n)*?<\/div>#i', '', $text);//embedded advisor contains an iframe, clean first
    $text = preg_replace ('#<iframe(.|\n)*?<\/iframe>#i', '', $text);//embedded youtube videos - standard method
    $text = preg_replace ('#<object(.|\n)*?<\/object>#i', '', $text);//embedded youtube videos - old flash method
    $text = preg_replace ('#<div class="youtube-container">(.|\n)*?<\/div>#i', '', $text);//embedded youtube videos - my method
    $text = preg_replace ('#<a class="jqlightbox inline_img(.|\n)*?<\/a>#i', '', $text);//embedded images
    $text = preg_replace ('#<img(.*?)>#i', '', $text);//embedded images
    //$text = preg_replace ('#<a(.*?)</a>#i', '', $text);//embedded links
	return $text;

}

function smfeed_build_str_key($text){
	
	$text = str_replace(" ", "-", trim(smfeed_string_clean_search($text)));
	$text = rawurlencode(strtolower($text));
	
	$text = strtolower(smfeed_clean_accents($text));
	
	return $text;
}

function smfeed_clean_accents($text){

	$search = array (
		
		"'%C4%99'", #e
		"'%C3%A8'", #e
		"'%C3%A9'", #e
		"'%C3%AA'", #e
		"'%C3%AB'", #e
		"'%C3%89'", #e
		"'%C3%88'", #E
		"'%C3%8A'", #E
		
		"'%C4%85'", #a
		"'%C3%A3'", #a
		"'%C3%A0'", #a
		"'%C3%A4'", #a
		"'%C3%A2'", #a	
		"'%C3%A1'", #a
		"'%C3%A5'", #a
		"'%C3%81'", #A
		"'%C3%82'", #A
		"'%C3%84'", #A
		"'%C3%85'", #A
		"'%C3%80'", #A
		
		"'%C4%87'", #c
		"'%C3%A7'", #c
		"'%C3%87'", #C
		
		"'%C5%9B'", #s
		"'%C5%A1'", #s
		"'%C5%A0'", #S
		"'%C5%9A'", #S
		
		"'%C3%B0'", #d
		"'%C3%B1'", #n
		"'%C3%BD'", #y
		"'%C4%9F'", #g
		
		"'%C4%B1'", #i
		"'%C3%AE'", #i
		"'%C3%AD'", #i
		"'%C3%AF'", #i
		"'%C3%AC'", #i
		"'%C3%8D'", #I
		"'%C3%8E'", #I
		"'%C4%B0'", #I
		
		"'%C5%82'", #l
		
		"'%C5%84'", #n
		
		"'%C3%BA'", #u
		"'%C3%BB'", #u
		"'%C3%B9'", #u
		"'%C3%BC'", #u
		"'%C5%B1'", #u
		
		"'%C3%9C'", #U
		
		"'%C3%B4'", #o
		"'%C3%B3'", #o
		"'%C3%91'", #o
		"'%C3%B6'", #o
		"'%C5%91'", #o
		"'%C3%B2'", #o
		"'%C3%B5'", #o
		"'%C3%93'", #O
		"'%C3%96'", #O
		
		"'%C3%9F'", #ss
		"'%C5%BC'", #z
		"'%C5%BA'", #z
		"'%C5%BB'", #Z
		
		"'%C3%A6'", #ae
		"'%C3%B8'", #oe
		
		"'%C2%AE'",
		"'%C2%B4'",
		"'%E2%84%A2'",
		
	);
	
	$replace = array (
		
		"e",
		"e",
		"e",
		"e",
		"e",
		"e",
		"E",
		"E",
		
		"a",
		"a",
		"a",
		"a",
		"a",
		"a",
		"a",
		"A",
		"A",
		"A",
		"A",
		"A",
		
		"c",
		"c",
		"C",
		
		"s",
		"s",
		"S",
		"S",
		
		"d",
		"n",
		"y",
		"g",
		
		"i",
		"i",
		"i",
		"i",
		"i",
		"I",
		"I",
		"I",
		
		"l",
		
		"n",
		
		"u",
		"u",
		"u",
		"u",
		"u",
		
		"U",
		
		"o",
		"o",
		"o",
		"o",
		"o",
		"o",
		"o",
		"O",
		"O",
		
		"ss",
		"z",
		"z",
		"Z",
		
		"ae",
		"oe",
		
		"",
		"",
		"",
		
	);
	
	$text = preg_replace($search, $replace, $text);
	
	return $text;

}

// CLEANS STRINGS
function smfeed_string_clean_search($string){
	
	$trans = get_html_translation_table(HTML_ENTITIES);
	$trans = array_flip ($trans);
	$string = strtr($string, $trans);
	
	$search = array (
		"'&quot;'",
		"'&lt;'",
		"'&gt;'",
		"'%&pound;'",
		"'%&euro;'",
		"'-'",
		"'~'",
		"'!'",
		"'\?'",
		"'@'",
		"'#'",
		"'\\$'",
		"'%'",
		"'\^'",
		"'&'",
		"'\*'",
		"'\('",
		"'\)'",
		"'_'",
		"'\+'",
		"'='",
		"'\.'",
		"','",
		"'\''",
		"'\['",
		"'\]'",
		"'{'",
		"'}'",
		"'\|'",
		"'\"'",
		"':'",
		"';'",
		"'/'",
		"'\\\'",
		"'>'",
		"'<'",		
		"'[\r]+'",
		"'[\n]+'",
		"'[\t]+'",
		"'[\s]+'",
	);
	
	$replace = array (
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
		" ",
	);
	
	$string = preg_replace($search, $replace, $string);
	
	return $string;
	
}

function smfeed_replace_not_in_tags($find_str, $replace_str, $string) {	
	$find = array($find_str);
	$replace = array($replace_str);	
	preg_match_all('#[^>]+(?=<)|[^>]+$#', $string, $matches, PREG_SET_ORDER);	
	foreach ($matches as $val) {	
		if (trim($val[0]) != "") {
			$string = str_replace($val[0], str_replace($find, $replace, $val[0]), $string);
		}
	}	
	return $string;
}

function smfeed_compression_start(){

	global $_SERVER, $_SVR;	
	$_SVR['NO_END_COMPRESSION'] = false;
	$_SVR['IDX_DO_GZIP_COMPRESS'] = false;
	
	// We have headers already sent so we cannot start the compression
	if (headers_sent()) {
		$_SVR['NO_END_COMPRESSION'] = true;
		return false;
	}
	
	$idx_phpver = phpversion();
	$useragent = !empty($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : $HTTP_USER_AGENT;//steve global defined in functions_general.php
	if ($idx_phpver >= "4.0.4pl1" && (strstr($useragent, "compatible") || strstr($useragent, "Gecko"))) {
		if (extension_loaded("zlib"))	{
			// SET COMPRESSION LEVEL
			ini_set("zlib.output_compression_level", 5);
			ob_start("ob_gzhandler");
		}
	}
	elseif ($idx_phpver > "4.0") {
	
		if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], "gzip")) {
		
			if (extension_loaded("zlib")) {
			
				// SET COMPRESSION LEVEL
				ini_set("zlib.output_compression_level", 5);
				$_SVR['IDX_DO_GZIP_COMPRESS'] = true;
				ob_start();
				ob_implicit_flush(0);
				header("Content-Encoding: gzip");
			}			
		}
	} return null;//steve added return
}

function smfeed_compression_end(){

	global $_SERVER, $_SVR;
	
	// We have not started the compression as we have headers already sent
	if ($_SVR['NO_END_COMPRESSION']) {
		return false;
	}
	// COMPRESS BUFFERED OUTPUT IF REQUIRED AND SEND TO BROWSER
	if ($_SVR['IDX_DO_GZIP_COMPRESS']) {
		$gzip_contents = ob_get_contents();
		ob_end_clean();
		$gzip_size = strlen($gzip_contents);
		$gzip_crc = crc32($gzip_contents);
		$gzip_contents = gzcompress($gzip_contents, 9);
		$gzip_contents = substr($gzip_contents, 0, strlen($gzip_contents) - 4);
		print "\x1f\x8b\x08\x00\x00\x00\x00\x00";
		print $gzip_contents;
		print pack("V", $gzip_crc);
		print pack("V", $gzip_size);
	} return null;//steve added return
}

######################################################################

if ($use_compression == "on") {
	// End compressing
	smfeed_compression_end();
}

function cartesian_product($set) {
	
	if (!$set) {
		return array(array());
	}
	
	$subset = array_shift($set);
	
	$cartesianSubset = cartesian_product($set);

	$result = array();
	foreach ($subset as $value) {
		foreach ($cartesianSubset as $p) {
			
			array_unshift($p, $value);
			$result[] = $p;
		}
	}
	return $result;        
}

exit;
