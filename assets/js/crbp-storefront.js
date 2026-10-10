/**
 * Clypper's Role Based Pricing for WooCommerce: stack the volume discount badge under the sale badge.
 *
 * The badge reuses the theme's sale badge markup, so the theme positions it. Themes that float the sale badge over
 * a corner of the product image would put both badges on the same spot; this moves ours just below the sale badge.
 * Inline sale badges (and pages without this script) are left alone.
 */
( function () {
	var SALE_BADGES = '.onsale, .wc-block-components-product-sale-badge, .ast-onsale-card';
	var GAP = 4;

	function overlaps( a, b ) {
		return a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;
	}

	function stack() {
		document.querySelectorAll( '.crbp-volume-badge' ).forEach( function ( badge ) {
			var container = badge.offsetParent;

			if ( ! container || 'absolute' !== window.getComputedStyle( badge ).position ) {
				return;
			}

			// Our badge copies the theme's sale badge classes, so its first class also finds sale badges of themes
			// with their own markup.
			var themeClass = badge.classList[ 0 ];
			var selector = SALE_BADGES;

			if ( themeClass && 'crbp-volume-badge' !== themeClass ) {
				selector += ', .' + window.CSS.escape( themeClass );
			}

			container.querySelectorAll( selector ).forEach( function ( sale ) {
				if ( sale === badge || sale.classList.contains( 'crbp-volume-badge' ) ) {
					return;
				}

				var ours = badge.getBoundingClientRect();
				var theirs = sale.getBoundingClientRect();

				if ( overlaps( ours, theirs ) ) {
					var top = parseFloat( window.getComputedStyle( badge ).top ) || 0;
					badge.style.top = top + theirs.bottom - ours.top + GAP + 'px';
				}
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', stack );
	} else {
		stack();
	}
} )();
