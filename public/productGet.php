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
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
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
            'images'=>array(),
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.17', 'msg'=>'Merchandise Product not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['product']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.18', 'msg'=>'Unable to find Merchandise Product'));
        }
        $product = $rc['product'];
        $product['unit_amount'] = $product['unit_amount'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['unit_amount'], $intl_currency);
        $product['unit_discount_amount'] = $product['unit_discount_amount'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['unit_discount_amount'], $intl_currency);
        $product['shipping_other'] = $product['shipping_other'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['shipping_other'], $intl_currency);
        $product['shipping_CA'] = $product['shipping_CA'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['shipping_CA'], $intl_currency);
        $product['shipping_US'] = $product['shipping_US'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $product['shipping_US'], $intl_currency);

        //
        // Get any tags for this product
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x04) ) {
            $product['categories'] = array();
            $strsql = "SELECT tag_type, tag_name AS lists "
                . "FROM ciniki_merchandise_tags "
                . "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "ORDER BY tag_type, tag_name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.merchandise', array(
                array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags', 'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['tags']) ) {
                foreach($rc['tags'] as $tags) {
                    if( $tags['tags']['tag_type'] == 10 ) {
                        $product['categories'] = $tags['tags']['lists'];
                    }
                }
            }
        }

        //
        // Get the list of object references for this product
        //
        $strsql = "SELECT id, object, object_id, 'Unknown' as display_name "
            . "FROM ciniki_merchandise_objrefs "
            . "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.merchandise', array(
            array('container'=>'objrefs', 'fname'=>'id', 'fields'=>array('id', 'object', 'object_id', 'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['objrefs']) ) {
            $product['objrefs'] = $rc['objrefs'];
            foreach($product['objrefs'] as $rid => $ref) {
                list($pkg, $mod, $obj) = explode('.', $ref['object']);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'getObjectName');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $args['business_id'], array(
                        'object'=>$ref['object'],
                        'object_id'=>$ref['object_id']
                        ));
                    if( $rc['stat'] == 'ok' && isset($rc['name']) ) {
                        $product['objrefs'][$rid]['display_name'] = $rc['name'];
                    }
                }
            }
        } else {
            $product['objrefs'] = array();
        }

        //
        // Get the additional images for this product
        //
        if( isset($args['images']) && $args['images'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadThumbnail');
            $strsql = "SELECT ciniki_merchandise_images.id, "
                . "ciniki_merchandise_images.image_id, "
                . "ciniki_merchandise_images.name, "
                . "ciniki_merchandise_images.sequence, "
                . "ciniki_merchandise_images.description "
                . "FROM ciniki_merchandise_images "
                . "WHERE ciniki_merchandise_images.product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
                . "AND ciniki_merchandise_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "ORDER BY ciniki_merchandise_images.sequence, ciniki_merchandise_images.date_added, ciniki_merchandise_images.name "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artcatalog', array(
                array('container'=>'images', 'fname'=>'id', 'fields'=>array('id', 'image_id', 'name', 'sequence', 'description')),
                ));
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            }
            if( isset($rc['images']) ) {
                $product['images'] = $rc['images'];
                foreach($product['images'] as $inum => $img) {
                    if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                        $rc = ciniki_images_hooks_loadThumbnail($ciniki, $args['business_id'], array('image_id'=>$img['image_id'], 'maxlength'=>75));
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $product['images'][$inum]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                    }
                }
            }
        }
    }

    $rsp = array('stat'=>'ok', 'product'=>$product);

    //
    // Check if all tags should be returned
    //
    $rsp['categories'] = array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x04) ) {
        //
        // Get the available tags
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $strsql = "SELECT DISTINCT tag_name FROM ciniki_merchandise_tags WHERE tag_type = 10 AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.merchandise', 'categories', 'tag_name');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.19', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['categories']) ) {
            $rsp['categories'] = $rc['categories'];
        }
    }

    return $rsp;
}
?>
