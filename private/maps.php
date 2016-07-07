<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_merchandise_maps($ciniki) {
    $maps = array();
    $maps['product'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Inactive',
            '60'=>'Deleted',
            ),
        'flags'=>array(
            0x01=>'Visible',
            0x02=>'Sell Online',
            ),
        'shipping_weight_units'=>array(
            '10'=>'lb',
            '20'=>'kg',
            ),
        );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
