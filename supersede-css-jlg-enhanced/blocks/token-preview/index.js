( function ( wp ) {
    const { registerBlockType } = wp.blocks;
    const { Fragment, createElement: el, useMemo } = wp.element;
    const { __ } = wp.i18n;
    const { Notice, Spinner } = wp.components;
    const { registerStore, useSelect } = wp.data;
    const apiFetch = wp.apiFetch;

    const STORE_NAME = 'supersede/token-preview';
    const BASE_CSS = '.ssc-token-preview{display:grid;gap:1.25rem;margin:0;padding:0;}' +
        '.ssc-token-preview__items{display:grid;gap:0.75rem;}' +
        '.ssc-token-preview__item{display:grid;gap:0.35rem;padding:1rem;border-radius:0.85rem;border:1px solid rgba(15,23,42,0.12);background:rgba(255,255,255,0.9);box-shadow:0 1px 2px rgba(15,23,42,0.08);}' +
        '.ssc-token-preview__item code{font-size:0.85rem;font-weight:600;}' +
        '.ssc-token-preview__value{font-size:0.95rem;}' +
        '.ssc-token-preview__description{font-size:0.8rem;color:rgba(15,23,42,0.7);}' +
        '.ssc-token-preview__group{font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:rgba(15,23,42,0.6);}' +
        '.ssc-token-preview__swatch{display:block;height:40px;border-radius:0.75rem;border:1px solid rgba(15,23,42,0.15);}' +
        '.ssc-token-preview__empty{margin:0;font-style:italic;color:rgba(15,23,42,0.75);}';

    const DEFAULT_STATE = {
        tokens: [],
        css: '',
        isLoading: false,
        error: null,
    };

    const ACTION_TYPES = {
        RECEIVE_TOKENS: 'RECEIVE_TOKENS',
        SET_IS_LOADING: 'SET_IS_LOADING',
        SET_ERROR: 'SET_ERROR',
        FETCH_TOKENS: 'FETCH_TOKENS',
    };

    const actions = {
        receiveTokens( tokens, css ) {
            return {
                type: ACTION_TYPES.RECEIVE_TOKENS,
                tokens,
                css,
            };
        },
        setIsLoading( isLoading ) {
            return {
                type: ACTION_TYPES.SET_IS_LOADING,
                isLoading,
            };
        },
        setError( error ) {
            return {
                type: ACTION_TYPES.SET_ERROR,
                error,
            };
        },
        fetchTokens() {
            return {
                type: ACTION_TYPES.FETCH_TOKENS,
            };
        },
    };

    const selectors = {
        getTokens( state ) {
            return state.tokens;
        },
        getCss( state ) {
            return state.css;
        },
        isLoading( state ) {
            return state.isLoading;
        },
        getError( state ) {
            return state.error;
        },
    };

    const controls = {
        [ ACTION_TYPES.FETCH_TOKENS ]() {
            return apiFetch( { path: '/ssc/v1/tokens' } );
        },
    };

    const resolvers = {
        * getTokens() {
            yield actions.setIsLoading( true );
            yield actions.setError( null );

            try {
                const response = yield actions.fetchTokens();
                const tokens = Array.isArray( response?.tokens ) ? response.tokens : [];
                const css = typeof response?.css === 'string' ? response.css : '';
                yield actions.receiveTokens( tokens, css );
            } catch ( error ) {
                const message = error?.message || __( 'Impossible de récupérer les tokens Supersede.', 'supersede-css-jlg' );
                yield actions.setError( message );
            }

            yield actions.setIsLoading( false );
        },
    };

    const reducer = ( state = DEFAULT_STATE, action ) => {
        switch ( action.type ) {
            case ACTION_TYPES.RECEIVE_TOKENS:
                return {
                    ...state,
                    tokens: action.tokens,
                    css: action.css,
                };
            case ACTION_TYPES.SET_IS_LOADING:
                return {
                    ...state,
                    isLoading: action.isLoading,
                };
            case ACTION_TYPES.SET_ERROR:
                return {
                    ...state,
                    error: action.error,
                };
            default:
                return state;
        }
    };

    if ( ! wp.data?.stores || ! Object.prototype.hasOwnProperty.call( wp.data.stores, STORE_NAME ) ) {
        registerStore( STORE_NAME, {
            reducer,
            actions,
            selectors,
            controls,
            resolvers,
        } );
    }

    const TokenList = ( { tokens } ) => {
        if ( ! tokens?.length ) {
            return el(
                'p',
                { className: 'ssc-token-preview__empty' },
                __( 'Aucun token n\'est défini pour le moment. Ajoutez vos premiers tokens dans Supersede CSS.', 'supersede-css-jlg' )
            );
        }

        return el(
            'div',
            { className: 'ssc-token-preview__items' },
            tokens.map( ( token ) => {
                const children = [
                    el( 'code', { key: 'name', className: 'ssc-token-preview__name' }, token.name ),
                    el( 'span', { key: 'value', className: 'ssc-token-preview__value' }, token.value ),
                ];

                if ( token.type === 'color' ) {
                    children.unshift(
                        el( 'span', {
                            key: 'swatch',
                            className: 'ssc-token-preview__swatch',
                            style: { background: token.value },
                            'aria-hidden': true,
                        } )
                    );
                }

                if ( token.description ) {
                    children.push(
                        el( 'span', { key: 'description', className: 'ssc-token-preview__description' }, token.description )
                    );
                }

                if ( token.group ) {
                    children.push(
                        el( 'span', { key: 'group', className: 'ssc-token-preview__group' }, token.group )
                    );
                }

                return el(
                    'div',
                    {
                        key: token.name,
                        className: 'ssc-token-preview__item',
                    },
                    children
                );
            } )
        );
    };

    registerBlockType( 'supersede/token-preview', {
        apiVersion: 3,
        title: __( 'Supersede Token Preview', 'supersede-css-jlg' ),
        category: 'widgets',
        icon: 'admin-customizer',
        description: __( 'Affiche un aperçu des tokens Supersede CSS disponibles directement dans l\'éditeur.', 'supersede-css-jlg' ),
        edit() {
            const tokens = useSelect( ( select ) => select( STORE_NAME ).getTokens(), [] );
            const isLoading = useSelect( ( select ) => select( STORE_NAME ).isLoading(), [] );
            const error = useSelect( ( select ) => select( STORE_NAME ).getError(), [] );
            const css = useSelect( ( select ) => select( STORE_NAME ).getCss(), [] );

            const inlineStyles = useMemo( () => {
                const combinedCss = css ? BASE_CSS + '\n' + css : BASE_CSS;

                return el( 'style', {
                    key: 'inline-style',
                    className: 'ssc-token-preview__inline-styles',
                    dangerouslySetInnerHTML: { __html: combinedCss },
                } );
            }, [ css ] );

            let content;
            if ( isLoading && ! tokens.length ) {
                content = el( 'div', { className: 'ssc-token-preview__loading' }, el( Spinner, null ) );
            } else if ( error ) {
                content = el( Notice, { status: 'error', isDismissible: false }, error );
            } else {
                content = el( TokenList, { tokens } );
            }

            return el(
                Fragment,
                null,
                inlineStyles,
                el(
                    'div',
                    { className: 'ssc-token-preview ssc-token-preview--editor' },
                    content
                )
            );
        },
        save() {
            return null;
        },
    } );
} )( window.wp );
