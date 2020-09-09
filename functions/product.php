<?php

function doliproduct($object, $value) {

if ( function_exists('pll_the_languages') ) { 
$lang = pll_current_language('locale');
return !empty($object->multilangs->$lang->$value) ? $object->multilangs->$lang->$value : $object->$value;
} else {
return $object->$value;
}

}

function doliprice($object, $mode = "ttc", $currency = null) {
global $current_user; 

if ( is_object($object) ) {
$total='multicurrency_total_'.$mode;
if ( isset($object->$mode) ) { $montant=$object->$mode;
} else {
$total = 'total_'.$mode;
$montant = $object->$total;
} } elseif (!empty($object)) {
$montant = $object;
} else {
$montant = 0;
}

//$$objet->multicurrency_code
if ( is_null($currency) ) { $currency = strtoupper(doliconst("MAIN_MONNAIE", dolidelay('constante'))); }
if ( function_exists('pll_the_languages') ) { 
$locale = pll_current_language('locale');
} else { if ( $current_user->locale == null ) { $locale = get_locale(); } else { $locale = $current_user->locale; } }
$fmt = numfmt_create( $locale, NumberFormatter::CURRENCY );
return numfmt_format_currency($fmt, $montant, $currency);//.$decimal
}

function doliproductstock($product, $refresh = false) {

$stock = "<script>";
$stock .= "(function ($) {
$(document).ready(function(){
$('#popover-".$product->id."').popover({
placement : 'auto',
delay: { 'show': 150, 'hide': 150 },
trigger : 'focus',
html : true
})
});
})(jQuery);";
$stock .= "</script>";

if ( ! is_object($product) || empty(doliconst('MAIN_MODULE_STOCK', $refresh)) || ($product->type != '0' && empty(doliconst('STOCK_SUPPORTS_SERVICES', $refresh)) )) {
$stock .= "<a tabindex='0' id='popover-".$product->id."' class='badge badge-pill badge-success text-white' data-container='body' data-toggle='popover' data-trigger='focus' title='".__( 'Available', 'doliconnect')."' data-content='".sprintf( __( 'This item is in stock and can be send immediately. %s', 'doliconnect'), '')."'><i class='fas fa-warehouse'></i> ".__( 'Available immediately', 'doliconnect').'</a>';
} else {
$warehouse = doliconst('DOLICONNECT_ID_WAREHOUSE', $refresh);
if (isset($product->stock_warehouse) && !empty($product->stock_warehouse) && !empty($warehouse)) {
if ( isset($product->stock_warehouse->$warehouse) ) {
$realstock = min(array($product->stock_reel,$product->stock_warehouse->$warehouse->real));
} else {
$realstock = 0;
}
} else {
$realstock = $product->stock_reel;
} 
$minstock = min(array($product->stock_theorique,$realstock));
$maxstock = max(array($product->stock_theorique,$realstock));

if (!empty(doliconnectid('dolishipping'))) {
$shipping = '<a href="'.doliconnecturl('dolishipping').'" class="btn btn-link btn-block btn-sm">'.__( 'Shipping', 'doliconnect').'</a>';
} else {
$shipping = null;
}

if ( $realstock <= 0 || (isset($product->array_options->options_packaging) && $maxstock < $product->array_options->options_packaging ) ) { $stock .= "<a tabindex='0' id='popover-".$product->id."' class='badge badge-pill badge-dark text-white' data-container='body' data-toggle='popover' data-trigger='focus' title='".__( 'Not available', 'doliconnect')."' data-content='".sprintf( __( 'This item is out of stock and can not be ordered or shipped. %s', 'doliconnect'), $shipping)."'><i class='fas fa-warehouse'></i> ".__( 'Not available', 'doliconnect')."</a>"; }  
elseif ( ($minstock <= 0 || (isset($product->array_options->options_packaging) && $realstock < $product->array_options->options_packaging)) && $maxstock >= 0 && $product->stock_theorique > $realstock ) { 
$delay =  callDoliApi("GET", "/products/".$product->id."/purchase_prices", null, dolidelay('product', $refresh));
if (empty($delay[0]->delivery_time_days)) { $delay = esc_html__( 'few', 'doliconnect'); } else { $delay = $delay[0]->delivery_time_days;}
if (doliversion('12.0.0')) {
$datelivraison =  callDoliApi("GET", "/supplierorders?sortfield=t.date_livraison&sortorder=ASC&limit=1&product_ids=".$product->id."&sqlfilters=(t.fk_statut%3A%3D%3A'2')", null, dolidelay('order', $refresh));
if (!empty($datelivraison) && is_array($datelivraison) && isset($datelivraison[0]->date_livraison) && !empty($datelivraison[0]->date_livraison)) {
$next = sprintf( "<br>".esc_html__( 'Reception scheduled on %s.', 'doliconnect'), wp_date('d/m/Y', $datelivraison[0]->date_livraison));
} else {
$next = null;
}
} else {
$next = null;
}
$stock .= "<a tabindex='0' id='popover-".$product->id."' class='badge badge-pill badge-danger text-white' title='".__( 'Available soon', 'doliconnect')."' data-container='body' data-toggle='popover' data-trigger='focus' data-content='".sprintf( __( 'This item is not in stock but should be available soon within %s days. %s %s', 'doliconnect'), $delay, $next, $shipping)."'><i class='fas fa-warehouse'></i> ".__( 'Available soon', 'doliconnect')."</a>"; 
} elseif ( $minstock >= 0 && $maxstock <= $product->seuil_stock_alerte ) { $stock .= "<a tabindex='0' id='popover-".$product->id."' class='badge badge-pill badge-warning text-white' data-container='body' data-toggle='popover' data-trigger='focus' title='".__( 'Limited availability', 'doliconnect')."' data-content='".sprintf( __( 'This item is in stock and can be shipped immediately but only in limited quantities. %s', 'doliconnect'), $shipping)."'><i class='fas fa-warehouse'></i> ".__( 'Available', 'doliconnect')."</a>";
} else {
$stock .= "<a tabindex='0' id='popover-".$product->id."' class='badge badge-pill badge-success text-white' data-container='body' data-toggle='popover' data-trigger='focus' title='".__( 'Available immediately', 'doliconnect')."' data-content='".sprintf( __( 'This item is in stock and can be shipped immediately. %s', 'doliconnect'), $shipping)."'><i class='fas fa-warehouse'></i> ".__( 'Available', 'doliconnect').'</a>';
}
} 

return $stock;
}

function doliconnect_countitems($object){
$qty=0;
if ( is_object($object) && isset($object->lines) && $object->lines != null ) {
foreach ($object->lines as $line) {
$qty+=$line->qty;
}
}
return $qty;
}

function doliaddtocart($productid, $quantity = null, $price = null, $remise_percent = null, $timestart = null, $timeend = null, $url = null) {
global $current_user;

if (!is_null($timestart) && $timestart > 0 ) {
$date_start=strftime('%Y-%m-%d 00:00:00', $timestart);
} else {
$date_start=null;
}

if ( !is_null($timeend) && $timeend > 0 ) {
$date_end=strftime('%Y-%m-%d 00:00:00', $timeend);
} else {
$date_end=null;
}

if ( empty(doliconnector($current_user, 'fk_order', true)) ) {
$thirdparty = callDoliApi("GET", "/thirdparties/".doliconnector($current_user, 'fk_soc'), null, dolidelay('thirdparty'));
$rdr = [
    'socid' => doliconnector($current_user, 'fk_soc'),
    'date' => time(),
    'demand_reason_id' => 1,
    'cond_reglement_id' => $thirdparty->cond_reglement_id,
    'module_source' => 'doliconnect',
    'modelpdf' =>  doliconst("COMMANDE_ADDON_PDF"),
    'pos_source' => get_current_blog_id(),
	];                  
$order = callDoliApi("POST", "/orders", $rdr, 0);
}

$order = callDoliApi("GET", "/orders/".doliconnector($current_user, 'fk_order', true)."?contact_list=0", null, dolidelay('order', true));

if ( $order->lines != null ) {
foreach ( $order->lines as $ln ) {
if ( $ln->fk_product == $productid ) {
//$deleteline = callDoliApi("DELETE", "/orders/".$orderid."/lines/".$ln[id], null, 0);
//$qty=$ln[qty];
$line=$ln->id;
}
}}

if (isset($line) && !$line > 0) { $line = null; }
if (! isset($line)) { $line = null; }

$prdt = callDoliApi("GET", "/products/".$productid."?includestockdata=1&includesubproducts=true", null, dolidelay('product', true));

if ( doliconnector($current_user, 'fk_order') > 0 && $quantity > 0 && empty($line) && (empty(doliconst('MAIN_MODULE_STOCK')) || $prdt->stock_reel >= $quantity || (is_null($line) && empty(doliconst('STOCK_SUPPORTS_SERVICES')) ))) {
                                                                                     
$adln = [
    'fk_product' => $prdt->id,
    'desc' => $prdt->description,
    'date_start' => $date_start,
    'date_end' => $date_end,
    'qty' => $quantity,
    'tva_tx' => $prdt->tva_tx, 
    'remise_percent' => isset($remise_percent) ? $remise_percent : doliconnector($current_user, 'remise_percent'),
    'subprice' => $price
	];                 
$addline = callDoliApi("POST", "/orders/".doliconnector($current_user, 'fk_order')."/lines", $adln, 0);
$order = callDoliApi("GET", "/orders/".doliconnector($current_user, 'fk_order', true)."?contact_list=0", null, dolidelay('order', true));
$dolibarr = callDoliApi("GET", "/doliconnector/".$current_user->ID, null, dolidelay('doliconnector', true));
if ( !empty($url) ) {
//set_transient( 'doliconnect_cartlinelink_'.$addline, esc_url($url), dolidelay(MONTH_IN_SECONDS, true));
}
return doliconnect_countitems($order);

} elseif ( doliconnector($current_user, 'fk_order') > 0 && ($prdt->stock_reel >= $quantity || (is_object($line) && $line->type != '0' && empty(doliconst('STOCK_SUPPORTS_SERVICES')) )) && $line > 0 ) {

if ( $quantity < 1 ) {

$deleteline = callDoliApi("DELETE", "/orders/".doliconnector($current_user, 'fk_order')."/lines/".$line, null, 0);
$order = callDoliApi("GET", "/orders/".doliconnector($current_user, 'fk_order', true)."?contact_list=0", null, dolidelay('order', true));
$dolibarr = callDoliApi("GET", "/doliconnector/".$current_user->ID, null, dolidelay('doliconnector', true));
//delete_transient( 'doliconnect_cartlinelink_'.$line );

return doliconnect_countitems($order);
 
} else {

$uln = [
    'desc' => $prdt->description,
    'date_start' => $date_start,
    'date_end' => $date_end,
    'qty' => $quantity,
    'tva_tx' => $prdt->tva_tx, 
    'remise_percent' => isset($remise_percent) ? $remise_percent : doliconnector($current_user, 'remise_percent'),
    'subprice' => $price
	];                  
$updateline = callDoliApi("PUT", "/orders/".doliconnector($current_user, 'fk_order')."/lines/".$line, $uln, 0);
$order = callDoliApi("GET", "/orders/".doliconnector($current_user, 'fk_order', true)."?contact_list=0", null, dolidelay('order', true));
$dolibarr = callDoliApi("GET", "/doliconnector/".$current_user->ID, null, dolidelay('doliconnector', true));
if ( !empty($url) ) {
//set_transient( 'doliconnect_cartlinelink_'.$line, esc_url($url), dolidelay(MONTH_IN_SECONDS, true));
} else {
//delete_transient( 'doliconnect_cartlinelink_'.$line );

}
return doliconnect_countitems($order);

}
} elseif ( doliconnector($current_user, 'fk_order') > 0 && is_null($line) ) {

return doliconnect_countitems($order);

} else {

return -1;//doliconnect_countitems($order);

}
}

function doliconnect_addtocart($product, $category = 0, $quantity = 0, $add = 0, $time = 0, $refresh = null) {
global $current_user;

$button = "<form id='form-product-".$product->id."' class='form-product-".$product->id."' method='post' action='".admin_url('admin-ajax.php')."'>";
$button .= "<input type='hidden' name='action' value='doliaddproduct_request'>";
$button .= "<input type='hidden' name='product-add-nonce' value='".wp_create_nonce( 'product-add-nonce-'.$product->id)."'>";
$button .= "<input type='hidden' name='product-add-id' value='".$product->id."'>";

$button .= "<script>";
$button .= 'jQuery(document).ready(function($) {
//jQuery(".dolisavewish'.$product->id.'").click(function(){
//alert("test");
//}
	
	jQuery(".form-product-'.$product->id.'").on("submit", function(e){
  jQuery("#DoliconnectLoadingModal").modal("show");
	e.preventDefault();
	var $form = $(this);
    
jQuery("#DoliconnectLoadingModal").on("shown.bs.modal", function(e){ 
		$.post($form.attr("action"), $form.serialize(), function(response){
      document.getElementById("success-product-'.$product->id.'").innerHTML = "";
      document.getElementById("error-product-'.$product->id.'").innerHTML = "";
      
      if (response.success) {
      if (document.getElementById("DoliHeaderCarItems")) {
      document.getElementById("DoliHeaderCarItems").innerHTML = response.data.items;
      }
      if (document.getElementById("DoliFooterCarItems")) {  
      document.getElementById("DoliFooterCarItems").innerHTML = response.data.items;
      }
      if (document.getElementById("DoliWidgetCarItems")) {
      document.getElementById("DoliWidgetCarItems").innerHTML = response.data.items;      
      }
      document.getElementById("success-product-'.$product->id.'").innerHTML = response.data.message;    
      } else {
      document.getElementById("error-product-'.$product->id.'").innerHTML = response.data.message;      
      }

jQuery("#DoliconnectLoadingModal").modal("hide");
		}, "json");  
  });
});
});';
$button .= "</script>";

if (doliconnector($current_user, 'fk_order') > 0) {
$orderfo = callDoliApi("GET", "/orders/".doliconnector($current_user, 'fk_order'), null, $refresh);
//$button .=$orderfo;
}

if ( isset($orderfo->lines) && $orderfo->lines != null ) {
foreach ($orderfo->lines as $line) {
if  ($line->fk_product == $product->id) {
//$button = var_dump($line);
$qty = $line->qty;
$ln = $line->id;
}
}}
if (!isset($qty) ) {
$qty = null;
$ln = null;
}

$currency=isset($orderfo->multicurrency_code)?$orderfo->multicurrency_code:strtoupper(doliconst("MAIN_MONNAIE", $refresh));

if ( $product->type == '1' && !is_null($product->duration_unit) && '0' < ($product->duration_value)) {
if ( $product->duration_unit == 'i' ) {
$altdurvalue=60/$product->duration_value; 
}
}
 $discount = !empty(doliconnector($current_user, 'remise_percent'))?doliconnector($current_user, 'remise_percent'):'0';

if ( !empty(doliconst("PRODUIT_MULTIPRICES", $refresh)) && !empty($product->multiprices_ttc) ) {
$lvl=doliconnector($current_user, 'price_level');
//$button .=$lvl;

if (!empty(doliconnector($current_user, 'price_level'))) {
$level=doliconnector($current_user, 'price_level');
} else {
$level=1;
}
 
$price_min_ttc = $product->multiprices_min->$level; 
$price_ttc = $product->multiprices_ttc->$level;
$price_ht = $product->multiprices->$level; 
$vat = $product->tva_tx;
$refprice=(empty(get_option('dolibarr_b2bmode'))?$price_ttc:$price_ht);

if (isset($add) && $add < 0) {
$button .= '<table class="table table-bordered table-sm"><tbody>'; 
$button .= '<tr><td class="text-right">'.doliprice( (empty(get_option('dolibarr_b2bmode'))?$price_ttc:$price_ht), null, $currency)."</td></tr>";
} else {
$button .= '<table class="table table-sm table-striped table-bordered"><tbody>';
$multiprix = (empty(get_option('dolibarr_b2bmode'))?$product->multiprices_ttc:$product->multiprices);
foreach ( $multiprix as $level => $price ) {
$button .= '<tr';
if ( (empty(doliconnector($current_user, 'price_level')) && $level == 1 ) || doliconnector($current_user, 'price_level') == $level ) {
$button .= ' class="table-primary"';  
}
$button .= '>';   
$button .= '<td><small>'.(!empty(doliconst('PRODUIT_MULTIPRICES_LABEL'.$level, $refresh))?doliconst('PRODUIT_MULTIPRICES_LABEL'.$level, $refresh):__( 'Price', 'doliconnect').' '.$level).'</small></td>';
$button .= '<td class="text-right"><small>'.doliprice( (empty(get_option('dolibarr_b2bmode'))?$price:$price_ht), null, $currency);
if ( empty($time) && !empty($product->duration_value) ) { $button .='/'.doliduration($product); }
$button .= '</small></td>';
if ( !empty($altdurvalue) ) { $button .= "<td class='text-right'>soit ".doliprice( $altdurvalue*(empty(get_option('dolibarr_b2bmode'))?$price:$price_ht), null, $currency)." par ".__( 'hour', 'doliconnect')."</td>"; } 
//$button .= '<small class="float-right">'.__( 'You benefit from the rate', 'doliconnect').' '.doliconst('PRODUIT_MULTIPRICES_LABEL'.$level).'</small>';
$button .= '</tr>'; 
}
}

$button .= '<tr><td colspan="';
if (!empty($product->net_measure)) { $button .= '2'; } else { $button .= '3'; };
$button .= '"><small><div class="float-left">'.(empty(get_option('dolibarr_b2bmode'))?__( 'Our prices are incl. VAT', 'doliconnect'):__( 'Our prices are excl. VAT', 'doliconnect'));
if (!empty($product->net_measure)) { $button .= '</div><div class="float-right">'.doliprice( $refprice/$product->net_measure, null, $currency);
$unit = callDoliApi("GET", "/setup/dictionary/units?sortfield=rowid&sortorder=ASC&limit=1&active=1&sqlfilters=(t.rowid%3Alike%3A'".$product->net_measure_units."')", null, dolidelay('constante'));
if (!empty($unit)) $button .= "/".$unit[0]->short_label; }
$button .= "</div></small></td></tr>";

$button .= '</tbody></table>';
} else {
$button .= '<table class="table table-bordered table-sm table-striped"><tbody>';
$button .= '<tr>'; 
$button .= '<td><div class="float-left">'.__( 'Selling Price', 'doliconnect').'</div>';
$button .= '<div class="float-right">'.doliprice( empty(get_option('dolibarr_b2bmode'))?$product->price_ttc:$product->price, null, $currency).'</div></td></tr>';
if ( empty($time) && !empty($product->duration_value) ) { $button .='/'.doliduration($product); } 
if ( !empty($altdurvalue) ) { $button .= "<tr><td class='text-right'>soit ".doliprice( $altdurvalue*$product->price_ttc, null, $currency)." par ".__( 'hour', 'doliconnect')."</td></tr>"; } 

$button .= ''; 

if ( !empty(doliconst("PRODUIT_CUSTOMER_PRICES", $refresh)) && doliconnector($current_user, 'fk_soc', $refresh) > 0 ) {
$product2 = callDoliApi("GET", "/products/".$product->id."/selling_multiprices/per_customer?thirdparty_id=".doliconnector($current_user, 'fk_soc'), null, dolidelay('product', $refresh));
}

if ( !empty(doliconst('MAIN_MODULE_DISCOUNTPRICE', $refresh)) ) {
$date = new DateTime(); 
$date->modify('NOW');
$lastdate = $date->format('Y-m-d');
$requestp = "/discountprice?productid=".$product->id."&sortfield=t.rowid&sortorder=ASC&sqlfilters=(t.date_begin%3A%3C%3D%3A'".$lastdate."')%20AND%20(t.date_end%3A%3E%3D%3A'".$lastdate."')";
$product3 = callDoliApi("GET", $requestp, null, dolidelay('product', $refresh));
}

if ( !empty(doliconst('MAIN_MODULE_DISCOUNTPRICE', $refresh)) && isset($product3) && !isset($product3->error) ) {
if (!empty($product3[0]->discount)) {
$price_ttc3=$product->price_ttc-($product->price_ttc*$product3[0]->discount/100);
$price_ht3=$product->price-($product->price*$product3[0]->discount/100);
$price_ttc=$product->price_ttc;
$price_ht=$product->price;
$vat = $product->tva_tx;
$discount = $product3[0]->discount;
} elseif (!empty($product3[0]->price)) {
$price_ht3=$product3[0]->price; 
$price_ht=$product->price; 
$discount = 100-(100*$price_ht3/$price_ht);
$price_ttc3=$product->price_ttc-($product->price_ttc*$discount/100);
$price_ttc=$product->price_ttc;
$vat = $product->tva_tx;
} elseif (!empty($product3[0]->price_ttc)) {
$price_ttc3=$product3[0]->price_ttc; 
$price_ttc=$product->price_ttc; 
$discount = 100-(100*$price_ttc3/$price_ttc);
$price_htc3=$product->price-($product->price*$discount/100);
$price_ht=$product->price;
$vat = $product->tva_tx;
}
$refprice=(empty(get_option('dolibarr_b2bmode'))?$price_ttc3:$price_ht3);

$button .= '<tr class="table-primary">'; 
$button .= '<td><div class="float-left">';
if (!empty($product3[0]->label)) {
$button .= $product3[0]->label;
} else {
$button .= __( 'Sales', 'doliconnect');
}
$button .= '</div>';
$button .= '<div class="float-right">'.doliprice( empty(get_option('dolibarr_b2bmode'))?$price_ttc3:$price_ht3, $currency).'</div></td></tr>';
} elseif ( !empty(doliconst("PRODUIT_CUSTOMER_PRICES", $refresh)) && isset($product2) && !isset($product2->error) ) {
foreach ( $product2 as $pdt2 ) {
$price_min_ttc=$pdt2->price_min;
$price_ttc=$pdt2->price_ttc;
$price_ht=$pdt2->price;
$vat = $pdt2->tva_tx;
$refprice = (empty(get_option('dolibarr_b2bmode'))?$price_ttc:$price_ht);

$button .= '<tr class="table-primary">'; 
$button .= '<td><div class="float-left">'.__( 'Your price', 'doliconnect').'</div>';
$button .= '<div class="float-right">'.doliprice( $refprice, $currency).'</div></td></tr>';
if ( empty($time) && !empty($product->duration_value) ) { $button .='/'.doliduration($product); } 
if ( !empty($altdurvalue) ) { $button .= "<td class='text-right'>soit ".doliprice( $altdurvalue*$refprice, null, $currency)." par ".__( 'hour', 'doliconnect')."</td>"; } 
}
} else {
$price_min_ttc=$product->price_min;
$price_ttc=$product->price_ttc;
$price_ht=$product->price;
$vat=$product->tva_tx;
$refprice = (empty(get_option('dolibarr_b2bmode'))?$price_ttc:$price_ht);
}

$button .= '<tr><td colspan="'.(!empty($altdurvalue)?'3':'2').'"><small><div class="float-left">'.(empty(get_option('dolibarr_b2bmode'))?__( 'Our prices are incl. VAT', 'doliconnect'):__( 'Our prices are excl. VAT', 'doliconnect'));
if (!empty($product->net_measure)) { $button .= '</div><div class="float-right">'.doliprice( $refprice/$product->net_measure, null, $currency);
$unit = callDoliApi("GET", "/setup/dictionary/units?sortfield=rowid&sortorder=ASC&limit=1&active=1&sqlfilters=(t.rowid%3Alike%3A'".$product->net_measure_units."')", null, dolidelay('constante'));
if (!empty($unit)) $button .= "/".$unit[0]->short_label; }
$button .= "</div></small></td></tr>";
$button .= '</tbody></table>';
}

if ( is_user_logged_in() && $add <= 0 && !empty(doliconst('MAIN_MODULE_COMMANDE', $refresh)) && doliconnectid('dolicart') > 0 ) {
$warehouse = doliconst('DOLICONNECT_ID_WAREHOUSE', $refresh);
if (isset($product->stock_warehouse) && !empty($product->stock_warehouse) && !empty($warehouse)) {
if ( isset($product->stock_warehouse->$warehouse) ) {
$realstock = min(array($product->stock_reel,$product->stock_warehouse->$warehouse->real));
} else {
$realstock = 0;
}
} else {
$realstock = $product->stock_reel;
} 
if ( $realstock-$qty > 0 && (empty($product->type) || (!empty($product->type) && doliconst('STOCK_SUPPORTS_SERVICES', $refresh)) ) ) {
if (isset($product->array_options->options_packaging) && !empty($product->array_options->options_packaging)) {
$m0 = 1*$product->array_options->options_packaging;
$m1 = get_option('dolicartlist')*$product->array_options->options_packaging;
} else {
$m0 = 1;
$m1 = get_option('dolicartlist');
}
if ( $realstock-$qty >= $m1 || empty(doliconst('MAIN_MODULE_STOCK')) ) {
$m2 = $m1;
} elseif ( $realstock > $qty ) {
$m2 = $realstock;
} else { $m2 = $qty; }
} else {
if ( isset($line) && $line->qty > 1 ) { $m2 = $qty; }
else { $m2 = 1; }
} 
if (isset($product->array_options->options_packaging) && !empty($product->array_options->options_packaging)) {
$step = $product->array_options->options_packaging;
} else {
$step = 1;
}
$button .= "<div class='input-group input-group-sm mb-3'><select class='form-control btn-light btn-outline-secondary' id='product-".$product->id."-add-qty' name='product-add-qty' ";
if ( ( empty($realstock) || $m2 < $step) && $product->type == '0' && !empty(doliconst('MAIN_MODULE_STOCK')) ) { $button .= " disabled"; }
$button .= ">";
if ((empty($realstock) && !empty(doliconst('MAIN_MODULE_STOCK', $refresh)) && (empty($product->type) || (!empty($product->type) && doliconst('STOCK_SUPPORTS_SERVICES', $refresh)) )) || $m2 < $step)  { $button .= "<OPTION value='0' selected>".__( 'Unavailable', 'doliconnect')."</OPTION>"; 
} elseif (!empty($m2) && $m2 >= $step) {
if ($step >1 && !empty($quantity)) $quantity = round($quantity/$step)*$step; 
if (empty($qty) && $quantity > $m2) $quantity = $m2; 
if ($m2 < $step)  { $button .= "<OPTION value='0' >".__( 'Delete', 'doliconnect')."</OPTION>"; } else {
foreach (range(0, $m2, $step) as $number) {
if ($number == 0) { $button .= "<OPTION value='0' >".__( 'Delete', 'doliconnect')."</OPTION>";
} elseif ( ($number == $step && empty($qty) && empty($quantity)) || $number == $qty || ($number == $quantity && empty($qty)) || ($number == $m0 && empty($qty) && empty($quantity))) {
$button .= "<option value='$number' selected='selected'>x ".$number."</option>";
} else {
$button .= "<option value='$number' >x ".$number."</option>";
}
	}
}}
$button .= "</select><div class='input-group-append'>";
if ( !empty(doliconst('MAIN_MODULE_WISHLIST', $refresh)) && !empty(get_option('doliconnectbeta')) ) {
$button .= "<button class='btn btn-sm btn-info' type='submit' name='cartaction' value='addtowish' title='".esc_html__( 'Save my wish', 'doliconnect')."'><i class='fas fa-save fa-fw'></i></button>";
}
$button .= "<button class='btn btn-sm btn-warning' type='submit' name='cartaction' value='addtocart' title='".esc_html__( 'Add to cart', 'doliconnect')."' ";
if ( ( empty($product->stock_reel) || $m2 < $step) && $product->type == '0' && !empty(doliconst('MAIN_MODULE_STOCK', $refresh)) ) { $button .= " disabled"; }
$button .= "><i class='fas fa-cart-plus fa-fw'></i></button></div></div>";

//if ( $qty > 0 ) {
//$button .= "<br /><div class='input-group'><a class='btn btn-block btn-warning' href='".doliconnecturl('dolicart')."' role='button' title='".__( 'Go to cart', 'doliconnect')."'>".__( 'Go to cart', 'doliconnect')."</a></div>";
//}
} elseif ( !empty($add) && doliconnectid('dolicart') > 0 ) {
$arr_params = array( 'redirect_to' => doliconnecturl('dolishop'));
$loginurl = esc_url( add_query_arg( $arr_params, wp_login_url( )) );

if ( get_option('doliloginmodal') == '1' ) {       
$button .= '<div class="input-group"><a href="#" data-toggle="modal" class="btn btn-block btn-outline-secondary" data-target="#DoliconnectLogin" data-dismiss="modal" title="'.__('Sign in', 'ptibogxivtheme').'" role="button">'.__( 'log in', 'doliconnect').'</a></div>';
} else {
$button .= "<div class='input-group'><a href='".wp_login_url( get_permalink() )."?redirect_to=".get_permalink()."' class='btn btn-block btn-outline-secondary' >".__( 'log in', 'doliconnect').'</a></div>';
}

//$button .= "<div class='input-group'><a class='btn btn-block btn-outline-secondary' href='".$loginurl."' role='button' title='".__( 'Login', 'doliconnect')."'>".__( 'Login', 'doliconnect')."</a></div>";
} else {
$button .= "<div class='input-group'><a class='btn btn-block btn-info' href='".doliconnecturl('dolicontact')."?type=COM' role='button' title='".__( 'Login', 'doliconnect')."'>".__( 'Contact us', 'doliconnect')."</a></div>";
}

if ( !empty($discount) ) { $button .= "<small>".sprintf( esc_html__( 'you get %u %% discount', 'doliconnect'), $discount)."</small>"; }
$button .= "<input type='hidden' name='product-add-vat' value='".$product->tva_tx."'><input type='hidden' name='product-add-remise_percent' value='".$discount."'><input type='hidden' name='product-add-price' value='".$price_ht."'>";
//$button .= '<div id="product-add-loading-'.$product->id.'" style="display:none">'.doliprice($price_ttc).'<button class="btn btn-secondary btn-block" disabled><i class="fas fa-spinner fa-pulse fa-1x fa-fw"></i> '.__( 'Loading', 'doliconnect').'</button></div>';

$button .= "</form>";
$button .= "<div id='success-product-".$product->id."' class='text-success font-weight-bolder'></div>";
$button .= "<div id='error-product-".$product->id."' class='text-danger font-weight-bolder'></div>";

return $button;
}

function doliconnect_supplier($product){

$brands =  callDoliApi("GET", "/products/".$product->id."/purchase_prices", null, dolidelay('product'));

$supplier = "";

if ( !isset($brands->error) && $brands != null ) {
$supplier .= "<small><i class='fas fa-building fa-fw'></i> ".__( 'Brand:', 'doliconnect' )." ";
$i = 0;
foreach ($brands as $brand) {
if ($i > 0) $supplier .= ", ";
$thirdparty =  callDoliApi("GET", "/thirdparties/".$brand->fourn_id, null, dolidelay('product'));
if (!empty(doliconnectid('dolisupplier'))) {
$supplier .= "<a href='".doliconnecturl('dolisupplier')."?supplier=".$thirdparty->id."'>";
}
$supplier .= (!empty($thirdparty->name_alias)?$thirdparty->name_alias:$thirdparty->name);
if (!empty(doliconnectid('dolisupplier'))) {
$supplier .= "</a>";
}
$i++;
}
$supplier .= "</small>";
}

return $supplier;
}

// list of products filter
function doliproductlist($product) {
global $current_user;

$includestock = 0;
if ( ! empty(doliconnectid('dolicart')) ) {
$includestock = 1;
}

$wish = 0;
if (!empty($product->qty)) {
$wish = $product->qty;
$product->id = $product->fk_product;
}
$product = callDoliApi("GET", "/products/".$product->id."?includestockdata=".$includestock."&includesubproducts=true", null, dolidelay('product', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
$list = "<li class='list-group-item' id='prod-li-".$product->id."'><table width='100%' style='border:0px'><tr><td width='20%' style='border:0px'><center>";
$list .= doliconnect_image('product', $product->id, array('limit'=>1, 'entity'=>$product->entity, 'size'=>'150x150'), esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
$list .= "</center></td>";

$list .= "<td width='80%' style='border:0px'><b>".doliproduct($product, 'label')."</b>";
$list .= "<div class='row'><div class='col'><p><small>";
if ( !doliconst('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ) { $list .= "<i class='fas fa-toolbox fa-fw'></i> ".(!empty($product->ref)?$product->ref:'-'); }
if ( !empty($product->barcode) ) { 
if ( !doliconst('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ) { $list .= " | "; }
$list .= "<i class='fas fa-barcode fa-fw'></i> ".$product->barcode; }
$list .= "</small>";
if ( ! empty(doliconnectid('dolicart')) ) { 
$list .= "<br>".doliproductstock($product);
}
if ( !empty($product->country_id) ) {  
if ( function_exists('pll_the_languages') ) { 
$lang = pll_current_language('locale');
} else {
$lang = $current_user->locale;
}
$country = callDoliApi("GET", "/setup/dictionary/countries/".$product->country_id."?lang=".$lang, null, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
$list .= "<br><small><span class='flag-icon flag-icon-".strtolower($product->country_code)."'></span> ".$country->label."</small>"; }

$arr_params = array( 'category' => isset($_GET['category'])?$_GET['category']:null, 'subcategory' => isset($_GET['subcategory'])?$_GET['subcategory']:null, 'product' => $product->id);  
$return = esc_url( add_query_arg( $arr_params, doliconnecturl('dolishop')) );
$list .= "<a href='".$return."' class='btn btn-link btn-block'>En savoir plus</a>";
 
$list .= "</p></div>";

if ( ! empty(doliconnectid('dolicart')) ) { 
$list .= "<div class='col-12 col-md-6'><center>";
$list .= doliconnect_addtocart($product, esc_attr(isset($_GET['category'])?$_GET['category']:null), $wish, -1, 0, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
$list .= "</center></div>";
}
$list .= "</div></td></tr></table></li>";
return $list;
}
add_filter( 'doliproductlist', 'doliproductlist', 10, 1);

// list of products filter
function doliproductcard($product, $attributes) {
global $current_user;

if (isset($product->id) && $product->id > 0) {

$documents = callDoliApi("GET", "/documents?modulepart=product&id=".$product->id, null, dolidelay('product', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//$card .= $documents;
$card = "<div class='row'>";
if (defined("DOLIBUG")) {
$card = dolibug();
} elseif ($product->id > 0 && $product->status == 1) {
$card .= "<div class='col-12 d-block d-sm-block d-xs-block d-md-none'><center>";
$card .= doliconnect_image('product', $product->id, array('limit'=>1, 'entity'=>$product->entity, 'size'=>'200x200'), esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
$card .= "</center>";
//$card .= wp_get_attachment_image( $attributes['mediaID'], "ptibogxiv_large", "", array( "class" => "img-fluid" ) );
$card .= "</div>";
$card .= '<div class="col-md-4 d-none d-md-block"><center>';
$card .= doliconnect_image('product', $product->id, array('limit'=>1, 'entity'=>$product->entity, 'size'=>'200x200'), esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
$card .= '</center>';
//$card .= wp_get_attachment_image( $attributes['mediaID'], "ptibogxiv_square", "", array( "class" => "img-fluid" ) );
$card .= "</div>";
$card .= "<div class='col-12 col-md-8'><h6><b>".doliproduct($product, 'label')."</b></h6><small>";
if ( !doliconst('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ) { $card .= "<i class='fas fa-toolbox fa-fw'></i> ".(!empty($product->ref)?$product->ref:'-'); }
if ( !empty($product->barcode) ) { 
if ( !doliconst('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ) { $card .= " | "; }
$card .= "<i class='fas fa-barcode fa-fw'></i> ".$product->barcode; }
$card .= "</small>";
if ( ! empty(doliconnectid('dolicart')) && !isset($attributes['hideStock']) ) { 
$card .= '<br>'.doliproductstock($product);
}
if (!empty(doliconnect_supplier($product))) $card .= '<br>'.doliconnect_supplier($product);
if (!empty(doliconnect_categories('product', $product, doliconnecturl('dolishop')))) $card .= '<br>'.doliconnect_categories('product', $product, doliconnecturl('dolishop'));
if ( !empty($product->country_id) ) {  
if ( function_exists('pll_the_languages') ) { 
$lang = pll_current_language('locale');
} else {
$lang = $current_user->locale;
}
$country = callDoliApi("GET", "/setup/dictionary/countries/".$product->country_id."?lang=".$lang, null, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
$card .= "<br><small><i class='fas fa-globe fa-fw'></i> ".__( 'Origin:', 'doliconnect')." <span class='flag-icon flag-icon-".strtolower($product->country_code)."'></span> ".$country->label."</small>"; }
if ( ! empty(doliconnectid('dolicart')) ) { 
$card .= "<br><br><div class='jumbotron'>";
$card .= doliconnect_addtocart($product, 0, 0, isset($attributes['hideButtonToCart']) ? $attributes['hideButtonToCart'] : 0, isset($attributes['hideDuration']) ? $attributes['hideDuration'] : 0);
$card .= "</div>";
}
$card .= "</div><div class='col-12'><h6>".__( 'Description', 'doliconnect' )."</h6><p>".doliproduct($product, 'description')."</p></div>";
} else {
$card .= "<div class='col-12'><p><center>".__( 'Item not in sale', 'doliconnect' )."</center></p></div>";
} 

if( has_filter('mydoliconnectproductcard') ) {
$card .= apply_filters('mydoliconnectproductcard', $product);
}

$card .= "</div>";
} else {
$card = "<center><br><br><br><br><div class='align-middle'><i class='fas fa-bomb fa-7x fa-fw'></i><h4>".__( 'Oops! This item does not appear to exist', 'doliconnect' )."</h4></div><br><br><br><br></center>";
}

return $card;
}
add_filter( 'doliproductcard', 'doliproductcard', 10, 2);

?>
