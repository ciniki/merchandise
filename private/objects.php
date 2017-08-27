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
function ciniki_merchandise_objects($ciniki) {
    
    $objects = array();
    $objects['product'] = array(
        'name'=>'Merchandise Product',
        'o_name'=>'product',
        'o_container'=>'products',
        'sync'=>'yes',
        'table'=>'ciniki_merchandise',
        'fields'=>array(
            'code'=>array('name'=>'Product Code', 'default'=>''),
            'name'=>array('name'=>'Product Name'),
            'permalink'=>array('name'=>'Permalink'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'sequence'=>array('name'=>'Sequence', 'default'=>'1'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'unit_amount'=>array('name'=>'Price', 'default'=>'0'),
            'unit_discount_amount'=>array('name'=>'Discount Amount', 'default'=>'0'),
            'unit_discount_percentage'=>array('name'=>'Discount Percentage', 'default'=>'0'),
            'taxtype_id'=>array('name'=>'Tax Type', 'default'=>'0'),
            'inventory'=>array('name'=>'Current Inventory', 'default'=>'0'),
            'shipping_other'=>array('name'=>'Shipping Cost Other', 'default'=>'0'),
            'shipping_CA'=>array('name'=>'Shipping Canada', 'default'=>'0'),
            'shipping_US'=>array('name'=>'Shipping USA', 'default'=>'0'),
            'primary_image_id'=>array('name'=>'Image', 'default'=>'0', 'ref'=>'ciniki.images.image'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_merchandise_history',
        );
    $objects['objref'] = array(
        'name'=>'Object Reference',
        'o_name'=>'objref',
        'o_container'=>'objrefs',
        'sync'=>'yes',
        'table'=>'ciniki_merchandise_objrefs',
        'fields'=>array(
            'product_id'=>array('name'=>'Product', 'ref'=>'ciniki.merchandise.product'),
            'object'=>array('name'=>'Object'),
            'object_id'=>array('name'=>'Object ID'),
            'sequence'=>array('name'=>'Sequence', 'default'=>'1'),
            ),
        'history_table'=>'ciniki_merchandise_history',
        );
    $objects['image'] = array(
        'name'=>'Image',
        'o_name'=>'image',
        'o_container'=>'images',
        'sync'=>'yes',
        'table'=>'ciniki_merchandise_images',
        'fields'=>array(
            'product_id'=>array('name'=>'Product', 'ref'=>'ciniki.merchandise.product'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'sequence'=>array('name'=>'Sequence'),
            'flags'=>array('name'=>'Options', 'default'=>'1'),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'description'=>array('name'=>'Description'),
            ),
        'history_table'=>'ciniki_merchandise_history',
        );
    $objects['tag'] = array(
        'name'=>'Tag',
        'o_name'=>'tag',
        'o_container'=>'tags',
        'sync'=>'yes',
        'table'=>'ciniki_merchandise_tags',
        'fields'=>array(
            'product_id'=>array('name'=>'Product', 'ref'=>'ciniki.merchandise.product'),
            'tag_type'=>array('name'=>'Type'),
            'tag_name'=>array('name'=>'Tag'),
            'permalink'=>array('name'=>'Permalink'),
            ),
        'history_table'=>'ciniki_merchandise_history',
        );
// Not yet implemented
//    $objects['setting'] = array(
//        'type'=>'settings',
//        'name'=>'Merchandise Settings',
//        'table'=>'ciniki_merchandise_settings',
//        'history_table'=>'ciniki_merchandise_history',
//        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
