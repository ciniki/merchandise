<?php
//
// Description
// -----------
// This method will add a new merchandise product for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Merchandise Product to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_merchandise_productAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'code'=>array('required'=>'no', 'blank'=>'yes', 'trimblanks'=>'yes', 'name'=>'Product Code'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'trimblanks'=>'yes', 'name'=>'Product Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'unit_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Price'),
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
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Additional Image'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'merchandise', 'private', 'checkAccess');
    $rc = ciniki_merchandise_checkAccess($ciniki, $args['tnid'], 'ciniki.merchandise.productAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Make sure code is unique
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x01) ) {
        if( !isset($args['code']) || $args['code'] == '' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.10', 'msg'=>'You must specify a code.'));
        }
        
        $strsql = "SELECT id, code, permalink "
            . "FROM ciniki_merchandise "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND code = '" . ciniki_core_dbQuote($ciniki, $args['code']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.11', 'msg'=>'You already have a product with that code, please choose another.'));
        }
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x01) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['code'] . '-' . $args['name']);
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        }
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_merchandise "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.12', 'msg'=>'You already have a merchandise product with that name, please choose another.'));
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
    // Add the merchandise product to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.merchandise.product', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.merchandise');
        return $rc;
    }
    $product_id = $rc['id'];

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.merchandise', 'tag', $args['tnid'], 'ciniki_merchandise_tags', 'ciniki_merchandise_history', 'product_id', $product_id, 10, $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.merchandise');
            return $rc;
        }
    }

    //
    // Add additional image if supplied
    //
    if( isset($args['image_id']) && $args['image_id'] > 0 ) {
        //
        // Get a UUID for use in permalink
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.merchandise');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.13', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $args['uuid'] = $rc['uuid'];

        //
        // Setup permalink
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['uuid']);
        $args['name'] = '';

        //
        // Add the product image to the database
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.merchandise.productimage', $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.merchandise');
            return $rc;
        }
        $productimage_id = $rc['id'];
    }

    //
    // If the object is specified
    //
    if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) && $args['object_id'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.merchandise.objref', array(
            'product_id'=>$product_id,
            'object'=>$args['object'],
            'object_id'=>$args['object_id'],
            'sequence'=>1), 0x04);
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.merchandise.product', 'object_id'=>$product_id));

    return array('stat'=>'ok', 'id'=>$product_id);
}
?>
