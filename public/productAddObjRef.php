<?php
//
// Description
// -----------
// This method will add a new object reference to an existing product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to add the Merchandise Product to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_merchandise_productAddObjRef(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'object'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object'),
        'object_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'merchandise', 'private', 'checkAccess');
    $rc = ciniki_merchandise_checkAccess($ciniki, $args['business_id'], 'ciniki.merchandise.productAddObjRef');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check to make sure the product exists
    //
    $strsql = "SELECT id, code, permalink "
        . "FROM ciniki_merchandise "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3589', 'msg'=>'Unable to find product.'));
    }

    //
    // Check to make sure the object ref does not already exist
    //
    $strsql = "SELECT id "
        . "FROM ciniki_merchandise_objrefs "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
        . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3590', 'msg'=>'This product already exists.'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.merchandise');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // If the object is specified
    //
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.merchandise.objref', array(
        'product_id'=>$args['product_id'],
        'object'=>$args['object'],
        'object_id'=>$args['object_id'],
        'sequence'=>1), 0x04);
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

    return array('stat'=>'ok');
}
?>
