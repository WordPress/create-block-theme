export function getStyleFromGoogleVariant ( variant ) {
    return variant.includes('italic') ? 'italic' : 'normal';
}

export function getWeightFromGoogleVariant ( variant ) {
    return variant === 'regular' || variant === 'italic' ? '400' : variant.replace( 'italic', '' );
}

export function forceHttps ( url ) {
    return url.replace("http://", "https://");
}
