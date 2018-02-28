<?php
/**
 * Plugin Name: Rave gateway for premiumpress themes
 * Plugin URI: https://rave.flutterwave.com
 * Description: Plugin to add Rave payment gateway into Premiumpress
 * Version: 1.0
 * Author: Atobajaiye Boluwaji
 * Author URI: https://github.com/Kingi_n
 * License: GPLv2 or later
 */
    /**
     * Build the admin settings
     * 1 => enable gateway
     * 2 => mode
     * 3 => country
     * 4 => secret test key
     * 5 => public test key
     * 6 => secret live key
     * 7 => public live key
     * 8 => display name
     * 9 => title
     */
function kkd_ppp_gateway_rave_admin($gateways){
    $nId = count($gateways)+1;
    $admin_settings = [
        '1' => array('name' => 'Enable Gateway', 'type' => 'listbox','fieldname' => $gateways[$nId]['function'],'list' => array('yes'=>'Enable','no'=>'Disable',) ),
     '2' => array('name' => 'Country', 'type' => 'listbox','fieldname' => 'rave_country','list' => array( 1 =>'NG',2=>'GH',3=>'KE') ),
    '3' => array('name' => 'Enable Test mode', 'type' => 'listbox','fieldname' => 'rave_test','list' => array( 1 =>'Yes',2=>'No',) ),
    
    
 	
	'4' => array('name' => 'Test Secret Key', 'type' => 'text', 'fieldname' => 'rave_tsk'),
 
	'5' => array('name' => 'Test Public Key', 'type' => 'text', 'fieldname' => 'rave_tpk'),
 	
	'6' => array('name' => 'Live Secret Key', 'type' => 'text', 'fieldname' => 'rave_lsk'),
 
	'7' => array('name' => 'Live Public Key', 'type' => 'text', 'fieldname' => 'rave_lpk'),
 	
    '8' => array('name' => 'Display Name', 'type' => 'text', 'fieldname' => 'rave_displayname', 'default' => 'Pay with Rave(Master,Visa and Verve)'),
    '9' => array('name' => 'Title', 'type' => 'text', 'fieldname' => 'rave_title', 'default' => 'Shopping Cart'),
	 
    ];
        

	$gateways[$nId]['name'] 		= "Rave";
	$gateways[$nId]['logo'] 		= plugins_url( 'images/logo.png' , __FILE__ );
	$gateways[$nId]['function'] 	= "kkd_ppp_gateway_rave_form";
	$gateways[$nId]['website'] 		= "https://rave.flutterwave.com";
	$gateways[$nId]['callback'] 	= "yes";
	$gateways[$nId]['ownform'] 		= "yes";
	$gateways[$nId]['fields'] 		= $admin_settings;

	$gateways[$nId]['notes'] 	= "You can get your API keys <a href='https://dashboard.paystack.co/#/settings/developer' target='_blank' style='text-decoration:underline;'>here</a>
 
	";
	return $gateways;
}
add_action('hook_payments_gateways','kkd_ppp_gateway_rave_admin');
    /**
     * Build the form 
     */
function kkd_ppp_gateway_rave_form($data=""){
	global $wpdb,$userdata;
	
	$test_key = get_option('rave_tpk');
    $live_key = get_option('rave_lpk');
    $test_s_key = get_option('rave_tsk');
	$live_s_key = get_option('rave_lsk');
	$mode = get_option('rave_test');
	if ($mode == 1) {
		$key = $test_key;
		$s_key = $test_s_key;
	}else{
		
        $key = $live_key;
        $s_key = $live_s_key;
	}
	
    
	if($GLOBALS['description'] == ""){ $GLOBALS['description'] = $GLOBALS['orderid']; }
    // echo "<pre>";
    //Generate Integrity hash
    $pb_key = $key; 
    $amount =$GLOBALS['total']; 
    $customer_email =$userdata->user_email; 
    $txref = $GLOBALS['orderid']; 
    $pmethod = "both"; 
    $seckey = $s_key; 
    $country = get_options('country'); 
    $custom_description = $GLOBALS['description']; 
    $custom_logo = "http://logo.com"; 
    $custom_title = get_options('rave_title'); 

    $options = array( 
        "PBFPubKey" => $key, 
        "amount" => $amount, 
        "customer_email" => $customer_email, 
        "txref" => $txref, 
        "payment_method" => $pmethod, 
        "country" => $country, 
        "custom_description" => $custom_description, 
        "custom_logo" => $custom_logo, 
        "custom_title" => $custom_title, 
    );
	// The keys in $options above are sorted by their ASCII value
      ksort($options);
      // The payload is rearranged and the values concatenated in the order of the sorted keys.
         $hashedPayload = '';

        foreach($options as $key => $value){

            $hashedPayload .= $value;
        }
        $completeHash = $hashedPayload.$seckey;

	$metadata = [
		[
			'display_name' => 'Description',
			'variable_name' => 'description',
			'value' => $GLOBALS["description"]
		]
	];
	$gatewaycode = '
	<div class="row-old">
	<div class="col-md-12"><b>'.get_option('rave_displayname').'</b><br>
	<form action="'.$GLOBALS['CORE_THEME']['links']['callback'].'" method="POST" >
	<input type="hidden" name="orderid" value="'.$GLOBALS['orderid'] .'" />
	<input type="hidden" name="amount" value="'.$GLOBALS['total'] .'" />
	<input type="hidden" name="desc" value="'.$GLOBALS['description'] .'" />
	<input type="hidden" name="shipping" value="'.$GLOBALS['shipping'] .'" />
    <input type="hidden" name="tax" value="'.$GLOBALS['tax'] .'" />
    
      <a class="flwpug_getpaid" 
      data-PBFPubKey="'.$key.'" 
      data-txref="'.$GLOBALS['orderid'].'"
      data-integrity_hash="'.$completeHash.'"
      data-amount="'.$GLOBALS['total'].'"
      data-customer_email="'.$userdata->email.'" 
      data-pay_button_text = "Pay" 
      data-country="'.get_options('country').'" 
      data-custom_title = "'.get_options('rave_title').'" 
      data-custom_description = "'.$GLOBALS['description'].'"
      data-payment_method = "both" 
    
      data-meta-metadata=\'{ "custom_fields":'.json_encode($metadata).'}\'></a>	

    <script type="text/javascript" src="http://flw-pms-dev.eu-west-1.elasticbeanstalk.com/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
	</form>	   
	</div> <div class="clearfix"></div></div>'; 
	return $gatewaycode;
}
 
$requeryCount = 0;
function reqery($orderID){
    global $CORE;
    	$test_key = get_option('rave_tsk');
		$live_key = get_option('rave_lsk');
		$mode = get_option('rave_test');
		if ($mode == 1) {
            $key = $test_key;
            $rave_url = 'http://flw-pms-dev.eu-west-1.elasticbeanstalk.com/flwv3-pug/getpaidx/api/verify';
		}else{
            $key = $live_key;
            $rave_url = 'https://api.ravepay.co/';
		}
		//get th txref from the url query	
		$txref = $_GET['txref'];
		$body = array(
            'SECRET' => $key,
            'flw_ref' => $_GET['txref'],
            'last_attempt' => 1
        );
        $header = array(
            'Content-Type' => 'application\json'
        );
		$args = array(
            'method' => 'POST',
            'header' => $header,
            'timeout'	=> 60,
            'body' => $body,
		);
		$request = wp_remote_get( $rave_url. 'flwv3-pug/getpaidx/api/xrequery', $args );
        if ( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {
                $rave_response = json_decode( wp_remote_retrieve_body( $request ) );
                if ( 'success' == $rave_response->data->status ) {
                    verifyTransaction($orderID, $rave_response->data);
                } elseif($rave_response && $rave_response->data && $rave_response->data->status === "failed") {
                    return "error";
                }
                else {
                    if ($GLOBALS['requeryCount'] > 4) {
                        return failed($payment_return);
                    }
                else {
                    sleep(3);
                    return requery($payment_return, $data);
                }
            }
        }
        if ($GLOBALS['requeryCount'] > 4) {
            return 'error';
        } else {
            sleep(3);
            return requery($orderID);
        }
		
}
function verifyTransaction($orderID,$api){
                global $CORE, $userdata;
                if (($api->chargecode == "00" || $api->chargecode == "0") && ($api->amount >= $_POST['total'])) {
                    $order_data = array();
                    $order_data['orderid'] 		= $_POST['orderid'];
                    $order_data['description'] 	= $_POST['description'];
                    $order_data['shipping']		= $_POST['shipping'];
                    $order_data['tax']			= $_POST['tax'];
                    $order_data['total']		= $_POST['total'];
                    $order_data['status']		= 3;	 // 3 = COMPLETED / 0 = FAILED
                    $order_data['email']		= $userdata->user_email;
                  
                    // SAVE ORDER
                    $CORE->ORDER('add',$order_data);
        
		            return "success";
                }else  {
                 return 'error';
            }

}
function kkd_ppp_gateway_rave_callback($orderID){ global $CORE, $userdata;
	
 	 
    if($_POST['orderId'] && isset($_GET['txref'])) {
		return requery($payment_return, $data);
	} else {
        return 'error';
	} 	
}
add_action('hook_callback','kkd_ppp_gateway_rave_callback');
?>