import './index.css';
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { DgtAdmin } from './components/dgtadmin';

domReady( () => {
    const root = createRoot(
        document.getElementById( 'disablegeneratethumbnails' )
    );

    root.render( <DgtAdmin /> );
} );
