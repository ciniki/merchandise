<?php
//
// Description
// -----------
// This function will process a web request for the merchandise module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:        The ID of the business to get post for.
//
// args:            The possible arguments for products
//
//
// Returns
// -------
//
function ciniki_merchandise_web_processRequest(&$ciniki, $settings, $business_id, $args) {

    if( !isset($ciniki['business']['modules']['ciniki.merchandise']) ) {
        return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3581', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );

    //
    // Setup titles
    //
    if( count($page['breadcrumbs']) == 0 ) {
        if( isset($settings['page-merchandise-name']) && $settings['page-merchandise-name'] != '' ) {
            $page['breadcrumbs'][] = array('name'=>$settings['page-merchandise-name'], 'url'=>$args['base_url']);
        } else {
            $page['breadcrumbs'][] = array('name'=>'Shop', 'url'=>$args['base_url']);
        }
    }

    $display = '';
    $ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

    //
    // Parse the url to determine what was requested
    //
    $categories = array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x04) ) {
        $strsql = "SELECT ciniki_merchandise.primary_image_id AS image_id, "
            . "ciniki_merchandise_tags.permalink, "
            . "ciniki_merchandise_tags.tag_name AS title "
            . "FROM ciniki_merchandise, ciniki_merchandise_tags "
            . "WHERE ciniki_merchandise.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_merchandise.flags&0x01) = 0x01 "  // Visible on website
            . "AND ciniki_merchandise.id = ciniki_merchandise_tags.product_id "
            . "AND ciniki_merchandise_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_merchandise_tags.tag_type = 10 "
            . "ORDER BY ciniki_merchandise_tags.permalink, ciniki_merchandise_tags.tag_name, ciniki_merchandise.primary_image_id DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.merchandise', array(
            array('container'=>'categories', 'fname'=>'permalink', 'fields'=>array('permalink', 'title', 'image_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) && count($rc['categories']) > 0 ) {
            $categories = $rc['categories'];
        }
    }
    
    //
    // Setup the base url as the base url for this page. This may be altered below
    // as the uri_split is processed, but we do not want to alter the original passed in.
    //
    $base_url = $args['base_url'];

    //
    // Check if we are to display an image, from the gallery, or latest images
    //
    $display = '';

    $uri_split = $args['uri_split'];
   
    //
    // First check if there is a category and remove from uri_split
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.merchandise', 0x04) 
        && isset($categories) 
        && isset($uri_split[0]) 
        && isset($categories[$uri_split[0]])
        ) {
        $category = $categories[$uri_split[0]];
        $page['title'] = $category['title'];
        $page['breadcrumbs'][] = array('name'=>$category['title'], 'url'=>$base_url . '/' . $category['permalink']);
        $base_url .= '/' . $category['permalink'];
        array_shift($uri_split);
    }
   
    //
    // Check for an product
    //
    if( isset($uri_split[0]) && $uri_split[0] != '' ) {
        $product_permalink = $uri_split[0];
        $display = 'product';
        //
        // Check for gallery pic request
        //
        if( isset($uri_split[1]) && $uri_split[1] == 'gallery'
            && isset($uri_split[2]) && $uri_split[2] != '' 
            ) {
            $image_permalink = $uri_split[2];
            $display = 'productpic';
        }
        $ciniki['response']['head']['og']['url'] .= '/' . $product_permalink;
        $base_url .= '/' . $product_permalink;
    }

    //
    // A category was found, so display the list of products in that category
    //
    elseif( isset($category) ) {
        $display = 'categorylist';
    }

    //
    // There is a list of categories, so display the list
    //
    elseif( isset($categories) && count($categories) > 0 ) {
        $display = 'categories';
    }

    //
    // No categories, display the list
    //
    else {
        $display = 'list';
    }

    if( $display == 'list' ) {
        //
        // Display list as thumbnails
        //
        $strsql = "SELECT id, code, name, permalink, primary_image_id AS image_id, synopsis, 'yes' AS is_details "
            . "FROM ciniki_merchandise "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (flags&0x01) = 0x01 "
            . "ORDER BY name ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'product');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>"There are currently no products available. Please check back soon.");
        } else {
            $page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$base_url, 'list'=>$rc['rows']);
        }
    }

    elseif( $display == 'categorylist' ) {
        //
        // Display list as thumbnails
        //
        $strsql = "SELECT ciniki_merchandise.id, "
            . "ciniki_merchandise.name, "
            . "ciniki_merchandise.permalink, "
            . "ciniki_merchandise.primary_image_id AS image_id, "
            . "ciniki_merchandise.synopsis, "
            . "'yes' AS is_details "
            . "FROM ciniki_merchandise_tags, ciniki_merchandise "
            . "WHERE ciniki_merchandise_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_merchandise_tags.tag_type = 10 "
            . "AND ciniki_merchandise_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $category['permalink']) . "' "
            . "AND ciniki_merchandise_tags.product_id = ciniki_merchandise.id "
            . "AND ciniki_merchandise.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_merchandise.flags&0x01) = 0x01 "
            . "ORDER BY ciniki_merchandise.code, ciniki_merchandise.name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.merchandise', 'product');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>"There are currently no products available. Please check back soon.");
        } elseif( count($rc['rows']) == 1 ) {
            $display = 'product';
            $product_permalink = $rc['rows'][0]['permalink'];
            $base_url .= '/' . $product_permalink;
        } else {
            $page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$base_url, 'noimage'=>'yes', 'list'=>$rc['rows']);
        }
    }

    elseif( $display == 'categories' ) {
        $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$categories);
    }

    if( $display == 'product' || $display == 'productpic' ) {
        if( isset($category) ) {
            $ciniki['response']['head']['links'][] = array('rel'=>'canonical', 'href'=>$args['base_url'] . '/' . $product_permalink);
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'merchandise', 'web', 'productLoad');
        $rc = ciniki_merchandise_web_productLoad($ciniki, $business_id, array('permalink'=>$product_permalink, 'images'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3582', 'msg'=>"We're sorry, the page you requested is not available."));
        }
        if( !isset($rc['product']) ) {
            return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3583', 'msg'=>"We're sorry, the page you requested is not available."));
        } else {
            $product = $rc['product'];
            $page['title'] = $product['name'];
            $page['breadcrumbs'][] = array('name'=>$product['name'], 'url'=>$base_url . '/' . $product['permalink']);

            if( $display == 'productpic' ) {
                if( !isset($product['images']) || count($product['images']) < 1 ) {
                    $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
                } else {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
                    $rc = ciniki_web_galleryFindNextPrev($ciniki, $product['images'], $image_permalink);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    if( $rc['img'] == NULL ) {
                        $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
                    } else {
                        $page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$base_url . '/gallery/' . $image_permalink);
                        if( $rc['img']['title'] != '' ) {
                            $page['title'] .= ' - ' . $rc['img']['title'];
                        }
                        $block = array('type'=>'galleryimage', 'primary'=>'yes', 'image'=>$rc['img']);
                        if( $rc['prev'] != null ) {
                            $block['prev'] = array('url'=>$base_url . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
                        }
                        if( $rc['next'] != null ) {
                            $block['next'] = array('url'=>$base_url . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
                        }
                        $page['blocks'][] = $block;
                    }
                }

            } else {
                if( isset($product['primary_image_id']) && $product['primary_image_id'] > 0 ) {
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$product['primary_image_id'], 
                        'title'=>$product['name'], 'caption'=>$product['primary_image_caption'], 'base_url'=>$base_url . '/gallery', 'permalink'=>$product['uuid']);
                }
                if( isset($product['description']) && $product['description'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$product['description']);
                } elseif( isset($product['synopsis']) && $product['synopsis'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$product['synopsis']);
                }

                // Add share buttons  
                if( !isset($settings['page-merchandise-share-buttons']) || $settings['page-merchandise-share-buttons'] == 'yes' ) {
                    $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$product['name'], 'tags'=>array());
                }

                // Add gallery
                if( isset($product['images']) 
                    && (($product['primary_image_id'] > 0 && count($product['images']) > 1 ) || ($product['primary_image_id'] == 0 && count($product['images']) > 0)) ) {
                    $page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'base_url'=>$base_url . '/gallery', 'images'=>$product['images']);
                }
            }
        }
    }

    //
    // Return error if nothing found to display
    //
    if( $display == '' ) {
        return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3584', 'msg'=>"We're sorry, the page you requested is not available."));
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
