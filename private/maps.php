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
            0x08=>'Sold Out',
            0x10=>'Shipped Product',
            0x20=>'Digital Download',
            ),
        );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
