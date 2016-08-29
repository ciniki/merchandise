//
// This app will handle the listing, additions and deletions of merchandise.  These are associated business.
//
function ciniki_merchandise_main() {
    //
    // merchandise panel
    //
    this.menu = new M.panel('Merchandise', 'ciniki_merchandise_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.merchandise.main.menu');
    this.menu.category = '';
    this.menu.nextPrevList = [];
    this.menu.sections = {
//        '_tabs':{'label':'', 'type':'menutabs', 'selected':'ingredients', 'tabs':{
//            'products':{'label':'Products', 'fn':'M.ciniki_merchandise_main.menu.open(null,"products");'},
//            'orders':{'label':'Orders', 'fn':'M.ciniki_merchandise_main.menu.open(null,"orders");'},
//            'inventory':{'label':'Inventory', 'fn':'M.ciniki_merchandise_main.menu.open(null,"inventory");'},
//            }},
        'product_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 
//            'visible':function() {return M.ciniki_merchandise_main.menu.sections._tabs.selected=='products'?'yes':'no';},
            'cellClasses':['multiline'],
            'hint':'Search notes', 
            'noData':'No notes found',
            }, 
        'products':{'label':'Products', 'type':'simplegrid', 'num_cols':1, 
//            'visible':function() {return M.ciniki_merchandise_main.menu.sections._tabs.selected=='products'?'yes':'no';},
            'noData':'No products',
            'addTxt':'Add Product',
            'addFn':'M.ciniki_merchandise_main.product.open(\'M.ciniki_merchandise_main.menu.open();\',0,null,\'\',0);',
            },
//        'orders':{'label':'Ailments', 'type':'simplegrid', 'num_cols':1, 
//            'visible':function() {return M.ciniki_merchandise_main.menu.sections._tabs.selected=='orders'?'yes':'no';},
//            'noData':'No Orders',
//            },
    };
    this.menu.sectionData = function(s) {
        return this.data[s];
    };
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'product_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.merchandise.productSearch', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_merchandise_main.menu.liveSearchShow('product_search',null,M.gE(M.ciniki_merchandise_main.menu.panelUID + '_' + s), rsp.products);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'product_search' ) { 
            return d.display_name;
        }
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'product_search' ) {
            return 'M.ciniki_merchandise_main.product.open(\'M.ciniki_merchandise_main.menu.show();\',\'' + d.id + '\');';
        }
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'products' ) {
            switch (j) {
                case 0: return d.display_name;
            }
        }
    };
    this.menu.rowFn = function(s, i, d) {
        if( s == 'products' ) {
            return 'M.ciniki_merchandise_main.product.open(\'M.ciniki_merchandise_main.menu.open();\',\'' + d.id + '\',M.ciniki_merchandise_main.menu.nextPrevList);';
        }
    };
    this.menu.open = function(cb, tab) {
        this.data = {};
        //if( tab != null ) { this.sections._tabs.selected = tab; }
        args = {'business_id':M.curBusinessID};
        method = 'ciniki.merchandise.productList';
//        switch( this.sections._tabs.selected ) {
//            case 'products': method = 'ciniki.merchandise.productList'; break;
//            case 'orders': method = 'ciniki.merchandise.orderList'; break;
//        } 
//        if( this.sections._tabs.selected == 'products' || this.sections._tabs.selected == 'inventory' ) {
//            args['category'] = this.category;
//        }
        M.api.getJSONCb(method, args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_merchandise_main.menu;
            p.data = rsp;
            p.nextPrevList = null;
            if( rsp.nextprevlist != null ) {
                p.nextPrevList = rsp.nextprevlist;
            }
            p.refresh();
            p.show(cb);
        });
    };
    this.menu.addClose('Back');

    //
    // The panel for editing a product
    //
    this.product = new M.panel('Product', 'ciniki_merchandise_main', 'product', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.merchandise.main.product');
    this.product.data = {};
    this.product.product_id = 0;
    this.product.sections = { 
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_merchandise_main.product.setFieldValue('primary_image_id', iid, null, null);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                        M.ciniki_merchandise_main.product.setFieldValue(fid, 0, null, null);
                        return true;
                    },
                },
            }},
        'general':{'label':'Product', 'aside':'yes', 'fields':{
            'code':{'label':'Code', 'type':'text', 'active':function() { return M.modFlagSet('ciniki.merchandise', 0x01);}, 'size':'small'},
            'name':{'label':'Name', 'type':'text', 'livesearch':'yes'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '50':'Inactive', '60':'Deleted'}},
            'flags1':{'label':'Options', 'type':'flagspiece', 'field':'flags', 'mask':0x0F, 'flags':{
                '1':{'name':'Visible'},
                '2':{'name':'Sell Online'},
                '4':{'name':'Sold Out'},
                }},
            'flags2':{'label':'Shipped', 'type':'flagtoggle', 'field':'flags', 'bit':0x10, 'on_sections':['shipping']},
            }},
        'pricing':{'label':'', 'aside':'yes', 'fields':{
            'unit_amount':{'label':'Price', 'type':'text', 'size':'small'},
            'inventory':{'label':'Inventory', 'type':'text', 'size':'small', 'active':function() { return M.modFlagSet('ciniki.merchandise', 0x10); }},
            }},
        'shipping':{'label':'Shipping Costs', 'aside':'yes', 'fields':{
            'shipping_CA':{'label':'Canada', 'type':'text', 'size':'small'},
            'shipping_US':{'label':'United States', 'type':'text', 'size':'small'},
            'shipping_other':{'label':'Everywhere Else', 'type':'text', 'size':'small'},
            }},
        '_categories':{'label':'Web Categories', 'aside':'yes', 
            'visible':function() { return M.modFlagSet('ciniki.merchandise', 0x04);},
            'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        'images':{'label':'Gallery', 'type':'simplethumbs'},
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Additional Image',
            'addFn':'M.ciniki_merchandise_main.product.save("M.ciniki_merchandise_main.productimage.edit(\'M.ciniki_merchandise_main.product.refreshImages();\',0,M.ciniki_merchandise_main.product.product_id);");',
            },
        'objrefs':{'label':'Attached to', 'type':'simplegrid', 'num_cols':2,
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_merchandise_main.product.save();'},
            'delete':{'label':'Delete', 'visible':function() {return M.ciniki_merchandise_main.product.product_id>0?'yes':'no';}, 
                'fn':'M.ciniki_merchandise_main.product.remove();'},
            }},
        };  
    this.product.sectionData = function(s) { return this.data[s]; }
    this.product.fieldValue = function(s, i, d) { 
//        if( i == 'flags1' || i == 'flags2' ) { return this.data.flags; }
        return this.data[i]; 
    }
    this.product.thumbFn = function(s, i, d) {
        return 'M.ciniki_merchandise_main.productimage.edit(\'M.ciniki_merchandise_main.product.refreshImages();\',\'' + d.id + '\');';
    };
    this.product.refreshImages = function() {
        if( M.ciniki_merchandise_main.product.product_id > 0 ) {
            M.api.getJSONCb('ciniki.merchandise.productGet', {'business_id':M.curBusinessID, 'product_id':this.product_id, 'images':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_merchandise_main.product;
                p.data.images = rsp.product.images;
                p.refreshSection('images');
                p.show();
            });
        }
    }
    this.product.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.merchandise.productHistory', 'args':{'business_id':M.curBusinessID, 'product_id':this.product_id, 'field':i}};
    }
    this.product.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.display_name;
            case 1: return '<button onclick="event.stopPropagation(); M.ciniki_merchandise_main.product.removeObjRef(event,' + i + ');">Remove</button>';
        }
    }
    this.product.liveSearchCb = function(s, i, v) {
        if( i == 'name' && v != '' ) {
            M.api.getJSONBgCb('ciniki.merchandise.productSearch', {'business_id':M.curBusinessID, 'start_needle':v},
                function(rsp) {
                    M.ciniki_merchandise_main.product.liveSearchShow(s, i, M.gE(M.ciniki_merchandise_main.product.panelUID + '_' + i), rsp.products);
                });
            return true;
        }
    }
    this.product.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'name' ) {
            return d.display_name;
        }
    }
    this.product.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( f == 'name' ) {
            return 'M.ciniki_merchandise_main.product.addObjRef(\'' + d.id + '\');';
        }
    }
    this.product.addObjRef = function(id) {
        M.api.getJSONCb('ciniki.merchandise.productAddObjRef', {'business_id':M.curBusinessID, 'product_id':id, 'object':this.object, 'object_id':this.object_id},
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_merchandise_main.product.close();
        })
    }
    this.product.addDropImage = function(iid) {
        if( this.product_id == 0 ) {
            var c = this.serializeForm('yes');
            if( this.object != null && this.object != '' ) {
                c += '&object=' + this.object + '&object_id=' + this.object_id;
            }
            M.api.postJSONCb('ciniki.merchandise.productAdd', {'business_id':M.curBusinessID, 'product_id':this.product_id, 'image_id':iid}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_merchandise_main.product.product_id = rsp.id;
                    M.ciniki_merchandise_main.product.refreshImages();
                });
        } else {
            M.api.getJSONCb('ciniki.merchandise.imageAdd', {'business_id':M.curBusinessID, 'image_id':iid, 'name':'', 'product_id':this.product_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_merchandise_main.product.refreshImages();
            });
        }
        return true;
    };
    this.product.open = function(cb, id, list, obj, obj_id) {
        this.reset();
        if( id != null ) { this.product_id = id; }
        if( list != null ) { this.nextPrevList = list; }
        if( obj != null ) { this.object = obj; }
        if( obj_id != null ) { this.object_id = obj_id; }
        M.api.getJSONCb('ciniki.merchandise.productGet', {'business_id':M.curBusinessID, 'product_id':this.product_id, 'images':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_merchandise_main.product;
            p.data = rsp.product;
            p.sections._categories.fields.categories.tags = [];
            if( rsp.categories != null ) {
                p.sections._categories.fields.categories.tags = rsp.categories;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.product.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_merchandise_main.product.close();'; }
        if( this.product_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.merchandise.productUpdate', {'business_id':M.curBusinessID, 'product_id':this.product_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            if( this.object != null && this.object != '' ) {
                c += '&object=' + this.object + '&object_id=' + this.object_id;
            }
            M.api.postJSONCb('ciniki.merchandise.productAdd', {'business_id':M.curBusinessID, 'product_id':this.product_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_merchandise_main.product.product_id = rsp.id;
                    eval(cb);
                });
        }
    };
    this.product.removeObjRef = function(event, i) {
        if( confirm('Are you sure you want to remove the product from ' + this.data.objrefs[i].display_name + '?') ) {
            M.api.getJSONCb('ciniki.merchandise.productDeleteObjRef', {'business_id':M.curBusinessID, 'objref_id':this.data.objrefs[i].id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                delete(M.ciniki_merchandise_main.product.data.objrefs[i]);
                M.ciniki_merchandise_main.product.refreshSection('objrefs');
            });
        }
    }
    this.product.remove = function() {
        if( confirm('Do you want to remove this product? It will be removed from your shop and all other items it is attached to.') ) {
            M.api.getJSONCb('ciniki.merchandise.productDelete', {'business_id':M.curBusinessID, 'product_id':this.product_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_merchandise_main.product.close();
            });
        }
    };
    this.product.nextButtonFn = function() {
        if( this.nextPrevList != null && this.nextPrevList.indexOf('' + this.product_id) < (this.nextPrevList.length - 1) ) {
            return 'M.ciniki_merchandise_main.product.save(\'M.ciniki_merchandise_main.product.open(null,' + this.nextPrevList[this.nextPrevList.indexOf('' + this.product_id) + 1] + ');\');';
        }
        return null;
    }
    this.product.prevButtonFn = function() {
        if( this.nextPrevList != null && this.nextPrevList.indexOf('' + this.product_id) > 0 ) {
            return 'M.ciniki_merchandise_main.product.save(\'M.ciniki_merchandise_main.product.open(null,' + this.nextPrevList[this.nextPrevList.indexOf('' + this.product_id) - 1] + ');\');';
        }
        return null;
    }
    this.product.addButton('save', 'Save', 'M.ciniki_merchandise_main.product.save();');
    this.product.addClose('Cancel');
    this.product.addButton('next', 'Next');
    this.product.addLeftButton('prev', 'Prev');

    //
    // The panel to display the edit form
    //
    this.productimage = new M.panel('Edit Image', 'ciniki_merchandise_main', 'productimage', 'mc', 'medium', 'sectioned', 'ciniki.merchandise.main.productimage');
    this.productimage.data = {};
    this.productimage.productimage_id = 0;
    this.productimage.product_id = 0;
    this.productimage.sections = {
        '_image':{'label':'Image', 'type':'imageform', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
            'flags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':{'1':{'name':'Visible'}}},
            }},
        '_description':{'label':'Description', 'type':'simpleform', 'fields':{
            'description':{'label':'', 'type':'textarea', 'size':'medium', 'hidelabel':'yes'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_merchandise_main.productimage.save();'},
            'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_merchandise_main.productimage.remove();'},
            }},
    };
    this.productimage.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) { return this.data[i]; } 
        return ''; 
    };
    this.productimage.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.merchandise.imageHistory', 'args':{'business_id':M.curBusinessID, 'productimage_id':this.productimage_id, 'field':i}};
    };
    this.productimage.addDropImage = function(iid) {
        M.ciniki_merchandise_main.productimage.setFieldValue('image_id', iid, null, null);
        return true;
    };
    this.productimage.edit = function(cb, iid, pid) {
        if( iid != null ) { this.productimage_id = iid; }
        if( pid != null ) { this.product_id = pid; }
        this.reset();
        this.sections._buttons.buttons.delete.visible = 'yes';
        M.api.getJSONCb('ciniki.merchandise.imageGet', {'business_id':M.curBusinessID, 'productimage_id':this.productimage_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_merchandise_main.productimage;
            p.data = rsp.image;
            p.refresh();
            p.show(cb);
        });
    };
    this.productimage.save = function() {
        if( this.productimage_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.merchandise.imageUpdate', {'business_id':M.curBusinessID, 
                    'productimage_id':this.productimage_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_merchandise_main.productimage.close();
                        }
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.merchandise.imageAdd', {'business_id':M.curBusinessID, 'product_id':this.product_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_merchandise_main.productimage.productimage_id = rsp.id;
                M.ciniki_merchandise_main.productimage.close();
            });
        }
    };
    this.productimage.remove = function() {
        if( confirm('Are you sure you want to delete this image?') ) {
            M.api.getJSONCb('ciniki.merchandise.imageDelete', {'business_id':M.curBusinessID, 
                'productimage_id':this.productimage_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_merchandise_main.productimage.close();
                });
        }
    };
    this.productimage.addButton('save', 'Save', 'M.ciniki_merchandise_main.productimage.save();');
    this.productimage.addClose('Cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_merchandise_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        if( args.product_id != null ) {
            this.product.open(cb, args.product_id, args.list, args.object, args.object_id);
        } else {
            this.menu.open(cb);
        }
    }
};
