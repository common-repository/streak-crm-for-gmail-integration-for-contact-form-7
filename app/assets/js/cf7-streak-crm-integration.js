jQuery( document ).ready(
	function ($) {
		$( '.streak_crm_page #tabs' ).tabs();

		$( '.cfsci_type_class' ).change(
			function () {
				var val = $( this ).val();

				if (val === '1') {
					$( '.pipeline_person' ).show();
					$( '.pipeline_org' ).hide();
				} else {
					$( '.pipeline_person' ).hide();
					$( '.pipeline_org' ).show();
				}
			}
		);
	}
);
