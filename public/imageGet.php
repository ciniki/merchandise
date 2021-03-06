<?php
//
// Description
// ===========
// This method will return all the information about an image.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the image is attached to.
// productimage_id:          The ID of the image to get the details for.
//
// Returns
// -------
//
function ciniki_merchandise_imageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'productimage_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Image'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'merchandise', 'private', 'checkAccess');
    $rc = ciniki_merchandise_checkAccess($ciniki, $args['tnid'], 'ciniki.merchandise.imageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Image
    //
    if( $args['productimage_id'] == 0 ) {
        $image = array('id'=>0,
            'product_id'=>'',
            'name'=>'',
            'permalink'=>'',
            'sequence'=>'',
            'flags'=>'1',
            'image_id'=>0,
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Image
    //
    else {
        $strsql = "SELECT ciniki_merchandise_images.id, "
            . "ciniki_merchandise_images.product_id, "
            . "ciniki_merchandise_images.name, "
            . "ciniki_merchandise_images.permalink, "
            . "ciniki_merchandise_images.sequence, "
            . "ciniki_merchandise_images.flags, "
            . "ciniki_merchandise_images.image_id, "
            . "ciniki_merchandise_images.description "
            . "FROM ciniki_merchandise_images "
            . "WHERE ciniki_merchandise_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_merchandise_images.id = '" . ciniki_core_dbQuote($ciniki, $args['productimage_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'image');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.7', 'msg'=>'Image not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['image']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.8', 'msg'=>'Unable to find Image'));
        }
        $image = $rc['image'];
    }

    return array('stat'=>'ok', 'image'=>$image);
}
?>
