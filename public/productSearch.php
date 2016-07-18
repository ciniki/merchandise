<?php
//
// Description
// -----------
// This method searchs the products for a name or code that matches the start needle.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Merchandise Product for.
//
// Returns
// -------
//
function ciniki_merchandise_productSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'merchandise', 'private', 'checkAccess');
    $rc = ciniki_merchandise_checkAccess($ciniki, $args['business_id'], 'ciniki.merchandise.productList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of products
    //
    $strsql = "SELECT ciniki_merchandise.id, "
        . "ciniki_merchandise.code, "
        . "ciniki_merchandise.name, "
        . "ciniki_merchandise.permalink, "
        . "ciniki_merchandise.status, "
        . "ciniki_merchandise.sequence, "
        . "ciniki_merchandise.flags, "
        . "ciniki_merchandise.unit_amount, "
        . "ciniki_merchandise.unit_discount_amount, "
        . "ciniki_merchandise.unit_discount_percentage, "
        . "ciniki_merchandise.taxtype_id, "
        . "ciniki_merchandise.inventory, "
        . "ciniki_merchandise.shipping_other, "
        . "ciniki_merchandise.shipping_CA, "
        . "ciniki_merchandise.shipping_US, "
        . "ciniki_merchandise.primary_image_id, "
        . "ciniki_merchandise.synopsis, "
        . "ciniki_merchandise.description "
        . "FROM ciniki_merchandise "
        . "WHERE ciniki_merchandise.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x01) ) {
        $strsql .= "AND (code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR code LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") ";
    } else {
        $strsql .= "AND (name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") ";
    }
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.merchandise', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'code', 'name', 'permalink', 'status', 'sequence', 'flags', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id', 'inventory', 'shipping_other', 'shipping_CA', 'shipping_US', 'primary_image_id', 'synopsis', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $products = $rc['products'];
        foreach($products as $pid => $product) {
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x01) && $product['code'] != '' ) {
                $products[$pid]['display_name'] = $product['code'] . ' - ' . $product['name'];
            } else {
                $products[$pid]['display_name'] = $product['name'];
            }
        }
    } else {
        $products = array();
    }

    return array('stat'=>'ok', 'products'=>$products);
}
?>
