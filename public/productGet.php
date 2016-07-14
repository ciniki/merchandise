<?php
//
// Description
// ===========
// This method will return all the information about an merchandise product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the merchandise product is attached to.
// product_id:          The ID of the merchandise product to get the details for.
//
// Returns
// -------
//
function ciniki_merchandise_productGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Merchandise Product'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'merchandise', 'private', 'checkAccess');
    $rc = ciniki_merchandise_checkAccess($ciniki, $args['business_id'], 'ciniki.merchandise.productGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Merchandise Product
    //
    if( $args['product_id'] == 0 ) {
        $product = array('id'=>0,
            'code'=>'',
            'name'=>'',
            'permalink'=>'',
            'status'=>'10',
            'sequence'=>'1',
            'flags'=>'0',
            'unit_amount'=>'',
            'unit_discount_amount'=>'',
            'unit_discount_percentage'=>'',
            'taxtype_id'=>'0',
            'inventory'=>'',
            'shipping_other'=>'',
            'shipping_CA'=>'',
            'shipping_US'=>'',
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Merchandise Product
    //
    else {
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
            . "AND ciniki_merchandise.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'product');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3063', 'msg'=>'Merchandise Product not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['product']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3064', 'msg'=>'Unable to find Merchandise Product'));
        }
        $product = $rc['product'];
        $product['unit_amount'] = $product['unit_amount'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['unit_amount'], $intl_currency);
        $product['unit_discount_amount'] = $product['unit_discount_amount'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['unit_discount_amount'], $intl_currency);
        $product['shipping_other'] = $product['shipping_other'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['shipping_other'], $intl_currency);
        $product['shipping_CA'] = $product['shipping_CA'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['shipping_CA'], $intl_currency);
        $product['shipping_US'] = $product['shipping_US'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['shipping_US'], $intl_currency);
    }

    return array('stat'=>'ok', 'product'=>$product);
}
?>
