;( function( $ ) {

	if ( typeof wcpbc_settings_edit_zone_params === 'undefined' ) {
		return false;
	}

	/**
	 * Country tool buttons.
	 */
	$('.-container-country-select .button.-select-all').on( 'click', function(e){
		e.preventDefault();
		$(this).closest( '.-container-country-select' ).find( 'select option' ).prop( 'selected', true );
		$(this).closest( '.-container-country-select' ).find('select').trigger( 'change' );
	});

	$('.-container-country-select .button.-select-none').on( 'click', function(e){
		e.preventDefault();
		$(this).closest( '.-container-country-select' ).find( 'select option' ).prop( 'selected', false );
		$(this).closest( '.-container-country-select' ).find('select').trigger( 'change' );
	});

	$('.-container-country-select .button.-select-eur').on( 'click', function(e){
		e.preventDefault();

		if ( ! wcpbc_settings_edit_zone_params.eur_countries instanceof Array ) {
			return;
		}

		$(this).closest( '.-container-country-select' ).find( 'select option' ).each( function( index, that ) {
			if ( wcpbc_settings_edit_zone_params.eur_countries.indexOf( $(that).attr( 'value' ) ) > -1 ) {
				$( that ).prop( 'selected', true );
			}
		});
		$(this).closest( '.-container-country-select' ).find('select').trigger( 'change' );
	});

	$('.-container-country-select .button.-select-eur-none').on( 'click', function(e){
		e.preventDefault();

		if ( ! wcpbc_settings_edit_zone_params.eur_countries instanceof Array ) {
			return;
		}

		$(this).closest( '.-container-country-select' ).find( 'select option' ).each( function( index, that ) {
			if ( wcpbc_settings_edit_zone_params.eur_countries.indexOf( $(that).attr( 'value' ) ) > -1 ) {
				$( that ).prop( 'selected', false );
			}
		});
		$(this).closest( '.-container-country-select' ).find('select').trigger( 'change' );
	});

	/**
	 * Change exchange rate append text on currency change.
	 */
	 $('select#currency').on('change', function(){
		$('.-container-exchange_rate span.wcpbc-input-append').text( $(this).val() );
	});

	/**
	 * Change title on type.
	 */
	$('input#name').on('keyup', function(){
		let name = $( this ).val();
		$( '.wcpbc-settings-section-container.-heading .wcpbc-zone-name' ).text( name ? name : 'Zone' );
	});


})( jQuery );