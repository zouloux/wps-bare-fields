jQuery( ( $ ) =>  {
	const orderTable = $( 'tbody' )
	orderTable.sortable({
		handle : '.BareFields_orderHandle',
		helper : (el, ui) => {
			ui.children().each( function () {
				$( this ).width( $( this ).width() )
			})
			return ui
		},
		placeholder : 'BareFields_sortablePlaceholder',
		update : function () {
			const order = []
			orderTable.children( 'tr' ).each( function () {
				const id = $( this ).attr( 'id' )
				if ( id && id.indexOf( 'post-' ) === 0 )
					order.push( parseInt( id.replace( 'post-', '' ), 10 ) )
			})
			$.post(wpsCptOrder.ajaxUrl, {
				action : 'wps_cpt_order_save',
				order  : order
			})
		}
	})
})
