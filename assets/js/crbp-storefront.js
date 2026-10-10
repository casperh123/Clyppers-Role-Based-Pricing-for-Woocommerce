/**
 * Clypper's Role Based Pricing for WooCommerce: stack the volume discount badge under the sale badge.
 *
 * The badge reuses the theme's sale badge markup, so the theme positions it. Themes that float the sale badge over
 * a corner of the product image would put both badges on the same spot; this moves ours just below the sale badge.
 * Inline sale badges (and pages without this script) are left alone.
 *
 * Loaded with `defer`, so the document is parsed when this runs. The block keeps the constants out of global scope.
 */
{
	const SALE_BADGES = '.onsale, .wc-block-components-product-sale-badge, .ast-onsale-card';
	const GAP = 4;

	const overlaps = ( a, b ) => a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;

	// Our badge copies the theme's sale badge classes, so its first class also finds sale badges of themes with
	// their own markup.
	const saleBadgeSelector = ( badge ) => {
		const [ themeClass ] = badge.classList;

		return themeClass && themeClass !== 'crbp-volume-badge'
			? `${ SALE_BADGES }, .${ CSS.escape( themeClass ) }`
			: SALE_BADGES;
	};

	const stackBelowSaleBadge = ( badge ) => {
		const container = badge.offsetParent;

		if ( ! container || getComputedStyle( badge ).position !== 'absolute' ) {
			return;
		}

		for ( const sale of container.querySelectorAll( saleBadgeSelector( badge ) ) ) {
			if ( sale.classList.contains( 'crbp-volume-badge' ) ) {
				continue;
			}

			const ours = badge.getBoundingClientRect();
			const theirs = sale.getBoundingClientRect();

			if ( overlaps( ours, theirs ) ) {
				const top = Number.parseFloat( getComputedStyle( badge ).top ) || 0;
				badge.style.top = `${ top + theirs.bottom - ours.top + GAP }px`;
			}
		}
	};

	for ( const badge of document.querySelectorAll( '.crbp-volume-badge' ) ) {
		stackBelowSaleBadge( badge );
	}
}
