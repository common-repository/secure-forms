(function(){
    window.jotformEmbedHandler("iframe[id='JotFormIFrame-241731125683050']", "https://form.jotform.com/")
	var freemiusHandler = FS.Checkout.configure({
		name : 'Secure Forms',
		plan_id : 26272,
		plugin_id: '15764',
		public_key: 'pk_ca451966bac9629bbe5ecd1a2d3f9'
		
	});
 
	var plans = {
		pro:     '26272',
	};
	
	var addBuyHandler = function (plan, planID){
		jQuery('#' + plan + '-purchase').on('click', function (e) {
			freemiusHandler.open({				
				licenses: jQuery('#' + plan + '-licenses').val(),
				success : function (response) {
					var data = {
						action     : wpsf_fs_data.action,
						security   : wpsf_fs_data.security,
						module_id  : wpsf_fs_data.module_id 
					};
					data.license_key = response.purchase.license_key;
					jQuery.ajax({
						url: wpsf_fs_data.ajax_url,
						method: 'POST',
						data: data,
						beforeSend: function () {
							
						},
						success: function( result ) {
							var resultObj = jQuery.parseJSON( result );							
							if ( resultObj.success ) {
								wpsf_finish_step1();
								if( jQuery('#wpsf_wizard').length > 0 ) {
									jQuery('#wpsf_wizard').smartWizard("next");
								}
							}
						}
					});
				}
			});
 
			e.preventDefault();
		});
	};
 
	for (var plan in plans) {
		if (!plans.hasOwnProperty(plan))
			continue;
		
		addBuyHandler(plan, plans[plan]);
	}
})();
(function(){
	var freemiusHandler = FS.Checkout.configure({
		name : 'Secure Forms',
		plugin_id: '15764',
		public_key: 'pk_ca451966bac9629bbe5ecd1a2d3f9'
	});
 
	var plans = {
		pro:     '26272',
	};
 
	for (var plan in plans) {
		if (!plans.hasOwnProperty(plan))
			continue;
	}
})();
function wpsf_finish_step1() {
    var url = wpsf_fs_data.admin_ajax_url;
	jQuery.ajax({
		url: url,
        data: {
            action: 'wpsf_finish_step1'
        },
        success:function( res ) {
        }
	})
}