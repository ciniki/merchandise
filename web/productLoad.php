<?php
//
// Description
// ===========
// This method will return all the information about an product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the product is attached to.
// product_id:          The ID of the product to get the details for.
//
// Returns
// -------
//
function ciniki_merchandise_web_productLoad($ciniki, $business_id, $args) {
    
    $strsql = "SELECT ciniki_merchandise.id, "
        . "ciniki_merchandise.uuid, "
        . "ciniki_merchandise.name, "
        . "ciniki_merchandise.permalink, "
        . "ciniki_merchandise.flags, "
        . "ciniki_merchandise.primary_image_id, "
        . "'' AS primary_image_caption, "
        . "ciniki_merchandise.synopsis, "
        . "ciniki_merchandise.description "
        . "FROM ciniki_merchandise "
        . "WHERE ciniki_merchandise.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    if( isset($args['permalink']) && $args['permalink'] != '' ) {
        $strsql .= "AND ciniki_merchandise.permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' ";
    } elseif( isset($args['id']) && $args['id'] > 0 ) {
        $strsql .= "AND ciniki_merchandise.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.27', 'msg'=>'No product specified'));
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.28', 'msg'=>'Product not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.merchandise.29', 'msg'=>'Unable to find Product'));
    }
    $product = $rc['product'];

    //
    // Get the images
    //
    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $strsql = "SELECT id, "
            . "name AS title, "
            . "permalink, "
            . "flags, "
            . "image_id, "
            . "description "
            . "FROM ciniki_merchandise_images "
            . "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.merchandise', array(
            array('container'=>'images', 'fname'=>'id', 'fields'=>array('id', 'title', 'permalink', 'flags', 'image_id', 'description')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            $product['images'] = $rc['images'];
        } else {
            $product['images'] = array();
        }
        if( $product['primary_image_id'] > 0 ) {
            $found = 'no';
            foreach($product['images'] as $image) {
                if( $image['image_id'] == $product['primary_image_id'] ) {
                    $found = 'yes';
                }
            }
            if( $found == 'no' ) {
                array_unshift($product['images'], array('title'=>'', 'flags'=>1, 'permalink'=>$product['uuid'], 'image_id'=>$product['primary_image_id'], 'description'=>''));
            }
        }
    }

    return array('stat'=>'ok', 'product'=>$product);
}
?>
