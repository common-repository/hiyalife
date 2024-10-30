var widgetwizard = function() {

    var _forcewidth=false;
    var _forceheight=false;
    var _defaultwidth="100%";
    var _defaultheight="480px";

    // PRIVATE VARS   
    //Dom
    var _$formsubmit; 
    var _$widgettype; 
    var _$previewcontainer; 
    var _$codeinput; 
    var _$tagsinput; 

    // For shortcode WP
    var _name="hiyalife";
    var _type="brand";
    var _height=_defaultheight;
    var _width=_defaultwidth;
    var _button=0;
    var _tiles=0;

    // PRIVATE METHODS
    function _initVars (){
        //console.time("_initVars");
        _$formsubmit      = jQuery('#submit');
        _$widgettype      = jQuery('#widgettype');
        _$previewcontainer      = jQuery('#preview-container');
        _$codeinput      = jQuery('#code');
        _$tagsinput      = jQuery('#taggroup input');
    }


    /**
    * Delete unused vars
    */
    function _deleteVars(){
    }


    /**
    * Execute actions in DOM elements
    */
    function _doActionDOM   (){
        jQuery(document).ready(function() { 
            /*-- INIT --*/                
            _initVars();
            _$formsubmit.on('click',function(e){
                e.preventDefault();
                var htmlobject="";
                var _params={};
                var prefix="http://hiyalife.com/widget/";
                var url="";
                _defaultheight="480px";
                if(jQuery('#usertype').val()==lits['widget.configurator.user']){
                 prefix+='user/';
                 _type="user";
             }
             else if(jQuery('#usertype').val()==lits['widget.configurator.brand']) 
             {
                prefix+='brand/';
                _type="brand";
            }
            if (!jQuery('#submitstoyrgroup input').is(":checked")) 
            {
                _params['nobutton']=1;
                _button = 1; 
            }
            if (jQuery('#tilesgroup input').is(":checked")) {
                _params['tiles']=1;
                _tiles=1;
            }
            url=prefix+jQuery('#objectid').val();
            _name=jQuery('#objectid').val();
            if(Object.keys(_params).length>0){
                url+="?";
                var _first=true;
                for (_param in _params) {
                    if(!_first)url+="&";
                    url+=_param+"="+_params[_param];
                    _first=false;
                }
            }

            htmlobject="<object ";
            if(_forcewidth){
                htmlobject+=' width="'+_forcewidth+'" ';
            }else if(jQuery('#width').val()){
                 htmlobject+=' width="'+jQuery('#width').val()+'" ';
                 _width=jQuery('#width').val();
             }
            else {
                htmlobject+=' width="'+_defaultwidth+'" ';
                _width=_defaultwidth;
            }
            if(_forceheight){
                htmlobject+=' height="'+_forceheight+'" ';
            }else if(jQuery('#height').val()){
                htmlobject+=' height="'+jQuery('#height').val()+'" ';
                _height=jQuery('#height').val();
            }
            else
            { 
                htmlobject+=' height="'+_defaultheight+'" ';
                _height=_defaultheight;
            }    
            htmlobject+=' data="'+url+'" ';
            htmlobject+=' >';
            htmlobject+='</object>';
            _$codeinput.val(htmlobject);
            _$previewcontainer.html(htmlobject);
                //_$previewcontainer.empty().append(htmlobject);
            });
_$widgettype.on('change', function() {
    jQuery('#usertypecontainer').hide();
    jQuery('#submitstoyrgroup').hide();
    jQuery('#taggroup').hide();
    jQuery('#tilesgroup').hide();
    jQuery('#heightgroup').show();
    jQuery('#idoption').remove();
    jQuery('#objectid').attr("placeholder", lits['widget.configurator.username']);
    if( this.value=="User life"||this.value==lits['widget.configurator.hiyacode'] ){
        jQuery('#objectidlabel').text(lits['widget.configurator.user']);
        if( this.value==lits['widget.configurator.userlife']){
            jQuery('#usertypecontainer').show();
            jQuery('#submitstoyrgroup').show();
            jQuery('#taggroup').show();
            jQuery('#tilesgroup').show();
            if(jQuery('#idoption').length==0){
                jQuery('#usertype').append('<option id="idoption">id</option>');
            }
        }else{
            jQuery('#heightgroup').hide();
        }

    }
    if( this.value==lits['widget.configurator.story'] ){
        jQuery('#objectidlabel').text(lits['widget.configurator.story']);
        jQuery('#objectid').attr("placeholder", lits['widget.configurator.storyid']);
    }
});
if(_$tagsinput.hasClass('autocomplete')){
    _$tagsinput.autocomplete({
        source: function( request, response ) {

            $.ajax({
                url: "/tags/used",
                dataType: "json",
                data: {
                    str: request.term,
                    idm: jQuery("#idmeemo_timeline").val()
                },
                success: function( data ) {
                    response( $.map( data.arr, function( item ) {
                        return {
                            value: item.tag
                        }
                    }));
                }
            });
        },
        minLength: 1,
        select: function( event, ui ) {
            _$tagsinput.val(ui.item.value);
        },
        open: function() {
        },
        close: function() {
        }
    });
}
_deleteVars();

});
}


    // PUBLIC METHODS 
    /**
    * Execute script page
    */
    this.init = function(){          
        _doActionDOM();

    }


    /**
    * Get WP Shortcode
    */
    this.getShortCode = function(){
        _$formsubmit.click();
        var sc = "[hiyalifeline ";
        if(_type=="user"){
            sc += "user='"+ _name+"'";
        }else{
            sc += "brand='"+ _name+"'";     
        }
        if(_button!=0) sc += " nobutton='"+ _button+"'";
        if(_tiles!=0) sc += " tiles='"+ _tiles+"'";
        sc += " width='"+ _width+"'";
        sc += " height='"+ _height+"'";
        sc += "]";
        return sc;
    }



}; 

jQuery(document).ready(function(){
    var Hfwidgetwizard = new widgetwizard();
    Hfwidgetwizard.init(); 
    tinyMCEPopup.onInit.add(function(ed) {
        var form = window.document.forms[0],

        isEmpty = function(value) {
            return (/^\s*$/.test(value));
        },

        encodeStr = function(value) {
            return value.replace(/\s/g, "%20")
            .replace(/"/g, "%22")
            .replace(/'/g, "%27")
            .replace(/=/g, "%3D")
            .replace(/\[/g, "%5B")
            .replace(/\]/g, "%5D")
            .replace(/\//g, "%2F");
        },

        insertShortcode = function(e){
            var sc = Hfwidgetwizard.getShortCode();
            ed.execCommand('mceInsertContent', 0, sc);
            tinyMCEPopup.close();

            return false;
        };
        document.getElementById('insertCode').onclick = insertShortcode;
        tinyMCEPopup.resizeToInnerSize();
    });
});


