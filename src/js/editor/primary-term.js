/* eslint-disable @wordpress/no-unsafe-wp-apis */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import {
	SelectControl,
	__experimentalSpacer as Spacer,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { useSeoData } from './hooks/use-meta';
import { PRIMARY_TERMS } from './config';

// Stable reference so useSelect doesn't churn when the post has no assigned terms.
const EMPTY = [];

/**
 * Primary-term selector for one hierarchical taxonomy, rendered beneath the core
 * term checkboxes inside the taxonomy panel (Categories, etc.).
 *
 * @param {Object} props          Component props.
 * @param {Object} props.taxonomy Taxonomy REST object (slug, rest_base, name, labels).
 */
function TaxonomyPrimary( { taxonomy } ) {
	const [ data, setValue ] = useSeoData();
	const primaryTerms = data.primaryTerms || {};

	const termIds = useSelect(
		( select ) =>
			select( editorStore ).getEditedPostAttribute(
				taxonomy.rest_base
			) || EMPTY,
		[ taxonomy.rest_base ]
	);

	const terms = useSelect(
		( select ) =>
			termIds.length
				? select( coreStore ).getEntityRecords(
						'taxonomy',
						taxonomy.slug,
						{ include: termIds, per_page: -1 }
				  )
				: EMPTY,
		[ termIds, taxonomy.slug ]
	);

	if ( ! terms || terms.length < 2 ) {
		return null;
	}

	const options = terms.map( ( term ) => ( {
		label: term.name,
		value: String( term.id ),
	} ) );

	return (
		<Spacer marginTop={ 4 } marginBottom={ 0 }>
			<SelectControl
				label={ primaryLabel(
					taxonomy.labels?.singular_name || taxonomy.name
				) }
				value={ String( primaryTerms[ taxonomy.slug ] || 0 ) }
				options={ options }
				onChange={ ( value ) =>
					setValue( 'primaryTerms', {
						...primaryTerms,
						[ taxonomy.slug ]: parseInt( value, 10 ) || 0,
					} )
				}
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</Spacer>
	);
}

/**
 * Build the "Primary {taxonomy}" label as a single translatable string so
 * locales can reorder the word and the taxonomy name.
 *
 * @param {string} name Taxonomy singular label.
 * @return {string} Control label.
 */
function primaryLabel( name ) {
	/* translators: %s: taxonomy singular name (e.g. Category). */
	return sprintf( __( 'Primary %s', 'outstand-seo' ), name );
}

/**
 * Wrap core's per-taxonomy selector (the component behind the Categories panel)
 * and append the primary-term control beneath its checkboxes, matching where
 * Yoast and TSF place theirs. Only hierarchical taxonomies get the control, and
 * only when the active engine supports primary terms; TaxonomyPrimary itself
 * hides until at least two terms are assigned.
 */
const withPrimaryTerm = createHigherOrderComponent(
	( OriginalComponent ) => ( props ) => {
		const { slug } = props;

		const taxonomy = useSelect(
			( select ) =>
				select( coreStore ).getEntityRecord( 'root', 'taxonomy', slug, {
					context: 'edit',
				} ),
			[ slug ]
		);

		const showPrimary = PRIMARY_TERMS && taxonomy?.hierarchical;

		return (
			<Fragment>
				<OriginalComponent { ...props } />
				{ showPrimary && <TaxonomyPrimary taxonomy={ taxonomy } /> }
			</Fragment>
		);
	},
	'withOutstandPrimaryTerm'
);

addFilter(
	'editor.PostTaxonomyType',
	'outstand-seo/primary-term',
	withPrimaryTerm
);
