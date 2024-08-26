const settings = window.wc.wcSettings.getSetting( 'uddoktapay_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'UddoktaPay', 'uddoktapay' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};
const UddoktaPay_Block_Gateway = {
    name: 'uddoktapay',
    label: label,
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( UddoktaPay_Block_Gateway );