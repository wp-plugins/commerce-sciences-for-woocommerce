<?php
/**
 * @package CommerceSciences_WP_WOO
 * @version 1.1
 */
/*
Plugin Name: Commerce Sciences For WooCommerce
Description: Smoothly integrate Commerce Sciences' powerful campaigns platform with your WooCommerce store.
Author: Commerce Sciences
Version: 1.1
Author URI: http://app.commercesciences.com?a=woo0
*/

	if ( ! defined( 'ABSPATH' ) ) { 
    	exit; // Exit if accessed directly
	}
	function cs_wp_woo_addTag() {
		$optionName = "cs_wp_woo_options";
		$option = get_option($optionName);
		if (!empty($option)) {
			$tag = $option["tag"];
			if (!empty($tag)) {
				echo $tag;
			}
		}
	}

	function cp_wp_woo_cartPage() {
		try {
			if (!is_ajax()) {
		        echo '<script>window._cshqCart = {};	';

		        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

		            $_cart = WC()->cart->get_cart();
		            $_product = $_cart[$cart_item_key];
		            $_product2 = wc_get_product($_product['product_id']);
		            $price = $_product2->get_price_including_tax();
		            $quantity = $_product['quantity'];
		            $currency = get_woocommerce_currency();
		            echo 'window._cshqCart["'.$_product2->id.'"] = {';
		            echo '								Quantity:'.$quantity.',';
		            echo '								UnitPrice:"'.$price.'",';
		            echo '								Denomination:"'.$currency.'"';
		            echo '};';

		        }
		        echo '</script>';
		    } 
	    }catch(Exception $e) {
	    }
    }

    function cp_wp_woo_thankyouPage($order_id) {
    	try {
    		if (!is_ajax()) {
				$order = wc_get_order($order_id);
				echo '<script type="text/javascript">';
			    echo '    var _cshq = _cshq || [];';
			    echo '    _cshq.push(["_reportConversion",';
			    echo '      "'.$order_id.'",';
			    echo '      "'.$order->get_total().'",';
			    echo '      "'.get_woocommerce_currency().'"]';
			    echo '   );';
			    echo '</script>';
			}
		}catch(Exception $e) {

		}
	}

	function cs_wp_woo_adminMenu() {
		add_options_page("Commerce Sciences", "Commerce Sciences", "manage_options", "cs_wp_woo_admin", "cs_wp_woo_adminContent");
	}

	function cs_wp_woo_adminContent() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<h2>Commerce Sciences Settings</h2>';
		$optionName = "cs_wp_woo_options";
		$option = get_option($optionName);
		if (empty($option)) {
			$option = array(
				"email" => get_option("admin_email"),
				"url" => get_option("siteurl"),
				"token" => null
			);
		} else {
			if (!empty($option["email"]) && !empty($option["url"]) && empty($option["token"])) {
				cs_wp_woo_performRegistration($option);
				return;
			} else {
				cs_wp_woo_gotoDashboard($option);
				return;
			}
		}
		cs_wp_woo_registrationForm($option, null);
	}

	function cs_wp_woo_performRegistration($option) {
		global $wp_version;
		$domain_option = "cs_wp_woo_csDomain";
		$csDomain = get_option($domain_option);
		if (empty($csDomain)) {
			$csDomain = "http://app.commercesciences.com";
			update_option($domain_option, $csDomain);
		}
		$url = $csDomain."/woocommerce/registerPost";
		$post_data = array(
			"email" => $option["email"],
			"platformVersion" => $wp_version,
			"storeURL" => $option["url"]
		);
		$_response = cs_wp_woo_request($url, $post_data, true);
		$jsonResponse = json_decode($_response);
		if ($jsonResponse->good=="true") {
			$option["token"] = $jsonResponse->data->securityToken;
			$option["userID"] = $jsonResponse->data->userID;
			$option["tag"] = $jsonResponse->data->tag;
			$optionName = "cs_wp_woo_options";
			update_option($optionName, $option);
			cs_wp_woo_gotoDashboard($option);
			return;
		} else {
			$errorString = "An unknown error has occured. Please contact support@commercesciences.com ->".$_response;
			if (!empty($jsonResponse)) {
				$errorString = "";
				foreach($jsonResponse->fieldErrors as $obj=>$val) {
					$errorString = $errorString.$val."\n";
				}
			} 
			cs_wp_woo_registrationForm($option, $errorString);
			return;
		}
		echo '</div>';
		echo $response;
	}

	function cs_wp_woo_gotoDashboard($option) {
		$domain_option = "cs_wp_woo_csDomain";
		$csDomain = get_option($domain_option);
		$configUrl = $csDomain."/woocommerce/admin?userID=".$option["userID"]."&securityToken=".$option["token"];
		echo '<p>Create, manage and analyze your campaigns by accessing the Commerce Sciences Admin Panel.</p>';
		echo '<div style="padding-top: 5px;">';
		echo '	<a style="font-size: 1.3em;text-decoration: underline;color: orange;font-weight: bold;" target="_blank" href="'.$configUrl.'">Go to my Admin Panel >></a>';
		echo '</div>';
	}

	function cs_wp_woo_registrationForm($option, $error) {

		$optionName = "cs_wp_woo_options";
		echo '<form method="post" action="options.php"> ';
		settings_fields( $optionName );
		do_settings_sections( $optionName );
		echo '<table class="form-table">';
		echo '	<tbody>';
		echo '		<tr valign="top">';
		echo '			<th scope="row">Email</th>';
		printf('		<td><input type="text" id="email" name="%s" value="%s" class="regular-text"/></td>',$optionName."[email]", $option["email"]);
		echo '		</tr>';
		echo '		<tr valign="top">';
		echo '			<th scope="row">URL</th>';
		printf('		<td><input type="text" id="url" name="%s" value="%s" class="regular-text"/></td>',$optionName."[url]",$option["url"]);
		echo '		</tr>';
		echo '	</tbody>';
		echo '</table>';
		submit_button( 'Register');
		if (!empty($error)) {
			echo '<div style="color:red;font-size:12px;"> Error: '.$error.'</div>';
		}
		echo '</form>';
		echo '</div>';
		echo '<h3>About Commerce Sciences</h3>';
		echo 'Increase Conversion Rate with Real-Time Offers. Improve your visitors\'s experience with customized Experience. <a target="_blank" style="text-decoration: underline; color: orange; font-weight: bold;"href="http://app.commercesciences.com/tour?a=woo1">Click here to learn more.</a>';
		echo '<div style="margin-top: 10px;">';
		echo '	<img src="'.plugins_url( 'images/about-cs.jpg', __FILE__ ).'">';
		echo '</div>';


	}

	function cs_wp_woo_adminMenuSettings() {
		register_setting( 'cs_wp_woo_options', 'cs_wp_woo_options' );
		;
	}

	function cs_wp_woo_request($url, $params, $isPost) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, $isPost);
		if ($isPost) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		$curl_response = curl_exec($curl);
		curl_close($curl);
		return $curl_response;
	}

	function cs_wp_woo_settings_link($links) { 
	  $settings_link = '<a href="options-general.php?page=cs_wp_woo_admin">Settings</a>'; 
	  array_unshift($links, $settings_link); 
	  return $links; 
	}

	add_action("admin_menu", "cs_wp_woo_adminMenu");
	add_action( 'admin_init', 'cs_wp_woo_adminMenuSettings' );
	$plugin = plugin_basename(__FILE__); 
	add_filter("plugin_action_links_$plugin", 'cs_wp_woo_settings_link' );
	add_action('wp_head', 'cs_wp_woo_addTag');
	add_action("woocommerce_cart_updated", "cp_wp_woo_cartPage");
	add_action("woocommerce_thankyou", "cp_wp_woo_thankyouPage")
?>