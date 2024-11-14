import './dgtadmin.css';
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import {
	useState,
	useEffect
} from '@wordpress/element';
import { Credit } from './credit';

const DgtAdmin = () => {

	const dgt_options = JSON.parse( dgtadmin_data.thumbnail_checks );
	const dgt_options2 = JSON.parse( dgtadmin_data.function_checks );

	const [ checkedItems, setCheckedItems ] = useState( dgt_options );
	const [ checkedItems2, setCheckedItems2 ] = useState( dgt_options2 );

	useEffect( () => {
		apiFetch( {
			path: 'rf/dgt_api/token',
			method: 'POST',
			data: {
				thumbnails: checkedItems,
				others: checkedItems2,
			}
		} ).then( ( response ) => {
			//console.log( response );
		} );
	}, [ checkedItems, checkedItems2 ] );

	const items = [];
	Object.keys( checkedItems ).map(
		( key ) => {
			if( checkedItems.hasOwnProperty( key ) ) {
				items.push(
					<div className="line-margin">
						<ToggleControl
							__nextHasNoMarginBottom
							label={ key }
							checked={ checkedItems[ key ] }
							onChange={ ( value ) =>
								{
									checkedItems[ key ] = value;
									let data = Object.assign( {}, checkedItems );
									setCheckedItems( data );
								}
							}
						/>
					</div>
				);
			}
		}
	);
	//console.log( checkedItems );

	const items2 = [];
	Object.keys( checkedItems2 ).map(
		( key2 ) => {
			if( checkedItems2.hasOwnProperty( key2 ) ) {
				items2.push(
					<div className="line-margin">
						<ToggleControl
							__nextHasNoMarginBottom
							label={ key2 }
							checked={ checkedItems2[ key2 ] }
							onChange={ ( value ) =>
								{
									checkedItems2[ key2 ] = value;
									let data2 = Object.assign( {}, checkedItems2 );
									setCheckedItems2( data2 );
								}
							}
						/>
					</div>
				);
			}
		}
	);
	//console.log( checkedItems2 );

	return (
		<>
			<h2>Disable Generate Thumbnails</h2>
			<Credit />
			<h2>{ __( 'Settings', 'disable-generate-thumbnails' ) }</h2>
			<h3>{ __( 'Check the thumbnails or function you want to disable.', 'disable-generate-thumbnails' ) }</h3>
			<h4>{ __( 'Thumbnails', 'disable-generate-thumbnails' ) }</h4>
			{ items }
			<h4>{ __( 'Function', 'disable-generate-thumbnails' ) }</h4>
			{ items2 }
		</>
	);

};

export { DgtAdmin };
