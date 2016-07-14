<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_merchandise_productUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Merchandise Product'),
        'code'=>array('required'=>'no', 'blank'=>'yes', 'trimblanks'=>'yes', 'name'=>'Product Code'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'trimblanks'=>'yes', 'name'=>'Product Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'unit_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Price'),
        'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Discount Amount'),
        'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Discount Percentage'),
        'taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tax Type'),
        'inventory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Inventory'),
        'shipping_other'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Shipping Cost Other'),
        'shipping_CA'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Shipping Canada'),
        'shipping_US'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Shipping USA'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
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
    $rc = ciniki_merchandise_checkAccess($ciniki, $args['business_id'], 'ciniki.merchandise.productUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if product already exists
    //
    $strsql = "SELECT id, code, name "
        . "FROM ciniki_merchandise "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'product');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3492', 'msg'=>'That product does not exist.'));
    }
    $product = $rc['product'];

    //
    // Check to make sure code is unique
    //
    if( isset($args['code']) ) {
        $strsql = "SELECT id, code, permalink "
            . "FROM ciniki_merchandise "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND code = '" . ciniki_core_dbQuote($ciniki, $args['code']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3498', 'msg'=>'You already have a product with that code, please choose another.'));
        }
    }

    if( isset($args['name']) || isset($args['code']) ) {
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x01) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, (isset($args['code'])?$args['code']:$product['code']) . '-' . isset($args['name'])?$args['name']:$product['name']);
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, (isset($args['name'])?$args['name']:$product['name']));
        }
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, code, name, permalink "
            . "FROM ciniki_merchandise "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3065', 'msg'=>'You already have an merchandise product with this name, please choose another.'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.merchandise');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Merchandise Product in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.merchandise.product', $args['product_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.merchandise');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.merchandise');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'merchandise');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.merchandise.product', 'object_id'=>$args['product_id']));

    return array('stat'=>'ok');
}
?>