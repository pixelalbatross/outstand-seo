import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import {
	SelectControl,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { useSeoData } from '../hooks/use-meta';
import { PRIMARY_TERMS } from '../config';

// Stable reference so useSelect doesn't churn when the post has no assigned terms.
const EMPTY = [];

/**
 * Primary-term selector for one hierarchical taxonomy.
 *
 * @param {Object} props          Component props.
 * @param {Object} props.taxonomy Taxonomy REST object (slug, rest_base, name).
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
		[ termIds ]
	);

	if ( ! terms || terms.length < 2 ) {
		return null;
	}

	const options = [
		{ label: __( '— none —', 'outstand-seo' ), value: '0' },
		...terms.map( ( term ) => ( {
			label: term.name,
			value: String( term.id ),
		} ) ),
	];

	return (
		<SelectControl
			label={ primaryLabel( taxonomy.name ) }
			value={ String( primaryTerms[ taxonomy.slug ] || 0 ) }
			options={ options }
			onChange={ ( value ) =>
				setValue( 'primaryTerms', {
					...primaryTerms,
					[ taxonomy.slug ]: parseInt( value, 10 ) || 0,
				} )
			}
			help={ __(
				'The main term for this post, used by the SEO engine for breadcrumbs and links.',
				'outstand-seo'
			) }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		/>
	);
}

/**
 * Build the "Primary {taxonomy}" label as a single translatable string so
 * locales can reorder the word and the taxonomy name.
 *
 * @param {string} name Taxonomy label.
 * @return {string} Control label.
 */
function primaryLabel( name ) {
	/* translators: %s: taxonomy singular name (e.g. Category). */
	return sprintf( __( 'Primary %s', 'outstand-seo' ), name );
}

/**
 * Primary-term selectors for every hierarchical taxonomy on the post type.
 * Renders nothing when there are none, so it can be embedded without leaving an
 * empty panel header.
 */
export default function PrimaryTermPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);

	const taxonomies = useSelect(
		( select ) =>
			select( coreStore ).getTaxonomies( {
				type: postType,
				per_page: -1,
			} ) || [],
		[ postType ]
	);

	const hierarchical = taxonomies.filter( ( tax ) => tax.hierarchical );

	if ( ! PRIMARY_TERMS || ! hierarchical.length ) {
		return null;
	}

	return (
		<VStack spacing={ 4 }>
			{ hierarchical.map( ( tax ) => (
				<TaxonomyPrimary key={ tax.slug } taxonomy={ tax } />
			) ) }
		</VStack>
	);
}
