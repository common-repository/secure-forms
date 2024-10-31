
jQuery(document).ready(function($){
    
    if( jQuery('#wpsf_wizard').length > 0 ) {
        setTimeout(function(){
            jQuery('#wpsf_wizard').smartWizard({
                selected: 0,
                theme: 'round',
                toolbar: {
                    position: 'bottom', // none|top|bottom|both
                    showNextButton: false, // show/hide a Next button
                    showPreviousButton:false, // show/hide a Previous button
                    extraHtml: '' // Extra html to show on toolbar
                },
              
            });
        })
    }
    setTimeout(function(){
        if( $('#wpsf_wizard').length > 0 ) {
            $('#wpsf_wizard').smartWizard("fixHeight");
        }        
    },1000)
    
    var baa_radio_value = $("input[name='wpsf_baa_checked']:checked").val();
    if( baa_radio_value == 1 ) {
        $(".wpsf_baa_form_wrapper .baa_form").show();
        if( $('#wpsf_wizard').length > 0 ) {
            $('#wpsf_wizard').smartWizard("fixHeight");
        }
    } else {
        $(".wpsf_baa_form_wrapper .baa_form").hide();
        if( $('#wpsf_wizard').length > 0 ) {
            $('#wpsf_wizard').smartWizard("fixHeight");
        }
    }
    $(".wpsf_baa_checkbox").change(function(){
        var selected = $("input[name='wpsf_baa_checked']:checked").val();
        setTimeout(function(){
            if( $('#wpsf_wizard').length > 0 ) {
                $('#wpsf_wizard').smartWizard("fixHeight");
            }
        },500)
        if( selected == 1 ) {
            $(".wpsf_baa_form_wrapper .baa_form").show();
        } else {
            $(".wpsf_baa_form_wrapper .baa_form").hide();
        }
       
        $.ajax( {
            url: ajax.url,
            type: 'post',
            data: {
                action: 'wpsf_update_baa_status',
                is_baa_required : selected,
                api_nonce: ajax.wpsf_nonce,   // pass the nonce here
            },
            success( data ) {
                
            },
        } );
    })
    $(document).on('click','.sw-btn-finish',function(){
        var url = ajax.url;
        jQuery.ajax({
            url: url,
            data: {
                action: 'wpsf_finish_wizard'
            },
            success:function( res ) {
                location.reload();
            }
        })        
    })
    $('.wpsf_plan.free button').click(function(){
        $('#wpsf_wizard').smartWizard("next");
        $('#wpsf_wizard').smartWizard("setState", [0], "disable");
        wpsf_finish_step1();
    })
    $("button.wpsf_validate_api").click(function( e ) {
        e.preventDefault();
        var wpsf_api_key = $("#wpsf_api_key").val();
        $.ajax( {
            url: ajax.url,
            type: 'post',
            data: {
                action: 'wpsf_validate_api_key',
                wpsf_api_key : wpsf_api_key,
                api_nonce: ajax.wpsf_nonce,   // pass the nonce here
            },
            success( data ) {
                var response = JSON.parse( data );
                if( response.status == 200 ) {
                    jQuery(".wpsf_success_validate").css('display','flex')
                    $(".wpsf_enter_api").hide();
                    setTimeout(function(){
                        $('#wpsf_wizard').smartWizard("next");
                        $('#wpsf_wizard').smartWizard("setState", [2], "disable");
                    },1000)
                } else {
                    $(".wpsf_enter_api").before( response.message );
                    $('#wpsf_wizard').smartWizard("fixHeight");

                }      
            },
        } );
    })
    
    $("button.wpsf_request_api").click(function( e ) {
        var btn = $(this);
        e.preventDefault();
        var wpsf_email_addr = $("#wpsf_email_addr").val();
        var check = false;
        if($('#wpsf_terms').prop('checked') == true ){
            check = true;
        }
        $.ajax( {
            url: ajax.url,
            type: 'post',
            data: {
                action: 'wpsf_request_api_key',
                wpsf_email_addr : wpsf_email_addr,
                wpsf_terms: check,
                api_nonce: ajax.wpsf_nonce,   // pass the nonce here
            },
            beforeSend:function(){
                $(btn).text("Please Wait");
            },
            success( data ) {
                var response = JSON.parse( data );
                if( response.status == 200 ) {
                    $(".wpsf_success").show();
                    $(".wpsf_request_api").hide();
                    $('#wpsf_wizard').smartWizard("fixHeight");
                    setTimeout(function(){
          
                        $('#wpsf_wizard').smartWizard('next');
                    },3000)
                } else {
                    $("div.wpsf_request_api").before( response.message );
                    $(btn).text("Submit");
                    $('#wpsf_wizard').smartWizard("fixHeight");
                    setTimeout(function(){
                        $(".error").remove();
                    },5000)
                }
                
            },
        } );
    })
    $(".action a.wpsf_view_details").click(function(e){
        e.preventDefault();
        $(this).toggleClass('active');
        $(this).parents('.row').find('.wpsf_details').toggle();
        var form_id = $(this).attr('data-form_id');
        var user_name  = $(this).attr('data-user_name');
        var user_id    = $(this).attr('data-user_id');
        var ip_address = $(this).attr('data-ip_address');
        var event      = $(this).attr('data-event');
        var wpsf_id      = $(this).attr('data-wpsfid');
        var url = ajax.wpsf_url + '/wp-json/wpsf/v1/insert_log';
        if( $(this).hasClass('active') ) {
            $.ajax({
                url: url,
                type: "POST",
                
                data:{
                    form_id: form_id,
                    user_name: user_name,
                    user_id: user_id,
                    ip_address: ip_address,
                    event: event,
                    wpsf_siteid: wpsf_id
                },        
                success:function(res) {
                }
            })
        }        
    })
    if( $('#wpsf_forms').length > 0  ) {
        $('#wpsf_forms').select2({
            theme: 'classic'
        });        
    }
    setTimeout(function(){
        if( $('#wpsf_wizard').length > 0 ) {
            $('#wpsf_wizard').smartWizard("fixHeight");
        }
    },2000);
    if( $('#wpsf_wizard').length > 0 ) {
        setInterval(function(){
            $('#wpsf_wizard').smartWizard("fixHeight");
        },1000)
    }
})
function wpsf_finish_step1() {
    var url = ajax.url;
	jQuery.ajax({
		url: url,
        data: {
            action: 'wpsf_finish_step1'
        },
        success:function( res ) {
        }
	})
}