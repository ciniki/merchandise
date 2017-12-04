<?php
//
// Description
// -----------
// This method will delete an merchandise product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the merchandise product is attached to.
// product_id:            The ID of the merchandise product to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_merchandise_productDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'product_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Merchandise Product'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'merchandise', 'private', 'checkAccess');
    $rc = ciniki_merchandise_checkAccess($ciniki, $args['tnid'], 'ciniki.merchandise.productDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the merchandise product
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_merchandise "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'product');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.16', 'msg'=>'Merchandise Product does not exist.'));
    }
    $product = $rc['product'];

    //
    // Be default, delete everything
    //
    $delete_ref = 'no';
    $delete_refs = 'yes';
    $delete_product = 'yes';

    //
    // If the delete was done via an object, then check if that was the only object referencing
    // the product. If multiple objects are referencing the product, only delete the objref, leave the product.
    //
    if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) ) {
        $delete_refs = 'no';
        $strsql = "SELECT id, uuid, object, object_id "
            . "FROM ciniki_merchandise_objrefs "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'product');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                if( $row['object'] == $args['object'] && $row['object_id'] == $args['object_id'] ) {
                    $delete_ref = 'yes';
                    $ref_row = $row;
                } else {
                    // Multiple obj refs, leave the product
                    $delete_product = 'no';
                }
            }
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.merchandise');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the object reference
    //
    if( $delete_ref == 'yes' && isset($ref_row) ) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.merchandise.objref', $ref_row['id'], $ref_row['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.merchandise');
            return $rc;
        }
    }

    if( $delete_refs == 'yes' ) {
        //
        // Remove the objrefs
        //
        $strsql = "SELECT id, uuid "    
            . "FROM ciniki_merchandise_objrefs "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'obj');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.merchandise.objref', $row['id'], $row['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.merchandise');
                    return $rc;
                }
            }
        }
    }

    if( $delete_product == 'yes' ) {
        //
        // Remove the images
        //
        $strsql = "SELECT id, uuid "    
            . "FROM ciniki_merchandise_images "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'product');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.merchandise.image', $row['id'], $row['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.merchandise');
                    return $rc;
                }
            }
        }

        //
        // Remove the product
        //
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.merchandise.product', $args['product_id'], $product['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.merchandise');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.merchandise');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'merchandise');

    return array('stat'=>'ok');
}
?>
