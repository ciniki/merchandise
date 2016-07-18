<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_merchandise_hooks_productList($ciniki, $business_id, $args) {

    //
    // Load INTL settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the list of products for an object
    //
    if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) && $args['object_id'] != '' ) {
        $strsql = "SELECT ciniki_merchandise_objrefs.id AS ref_id, "
            . "ciniki_merchandise.id, "
            . "ciniki_merchandise.code, "
            . "ciniki_merchandise.name, "
            . "ciniki_merchandise.inventory, "
            . "ciniki_merchandise.unit_amount "
            . "FROM ciniki_merchandise_objrefs "
            . "LEFT JOIN ciniki_merchandise ON ("
                . "ciniki_merchandise_objrefs.product_id = ciniki_merchandise.id "
                . "AND ciniki_merchandise.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE ciniki_merchandise_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_merchandise_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND ciniki_merchandise_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "ORDER BY ciniki_merchandise_objrefs.sequence, ciniki_merchandise.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.merchandise', array(
            array('container'=>'products', 'fname'=>'id', 'fields'=>array('ref_id', 'product_id'=>'id', 'code', 'name', 'inventory', 'unit_amount')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) ) {
            foreach($rc['products'] as $pid => $product) {
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x01) && $product['code'] != '' ) {
                    $rc['products'][$pid]['display_name'] = $product['code'] . ' - ' . $product['name'];
                } else {
                    $rc['products'][$pid]['display_name'] = $product['name'];
                }
                $rc['products'][$pid]['unit_amount_display'] = numfmt_format_currency($intl_currency_fmt, $product['unit_amount'], $intl_currency);
            }
            return array('stat'=>'ok', 'products'=>$rc['products']);    
        } else {
            return array('stat'=>'ok', 'products'=>array());
        }
    }

    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3574', 'msg'=>'Unable to get the product list'));
}
?>
