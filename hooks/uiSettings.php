<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get merchandise for.
//
// Returns
// -------
//
function ciniki_merchandise_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.merchandise'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5700,
            'label'=>'Products', 
            'edit'=>array('app'=>'ciniki.merchandise.main'),
            'add'=>array('app'=>'ciniki.merchandise.main', 'args'=>array('product_id'=>'0')),
            'search'=>array(
                'method'=>'ciniki.merchandise.productSearch',
                'args'=>array(),
                'container'=>'products',
                'cols'=>2,
                'cellValues'=>array(
                    '0'=>'d.code_name;',
                    '1'=>'d.price_display;',
                    ),
                'noData'=>'No items found',
                'edit'=>array('method'=>'ciniki.merchandise.main', 'args'=>array('product_id'=>'d.id;')),
                'submit'=>array('method'=>'ciniki.merchandise.main', 'args'=>array('search'=>'search_str')),
                ),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    return $rsp;
}
?>
