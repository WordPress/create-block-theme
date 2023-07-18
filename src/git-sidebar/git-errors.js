import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line
	__experimentalText as Text,
} from '@wordpress/components';

export const GitNotInstalledError = function () {
    return <Text>
        { __(
            'Git is not installed on the server.',
            'create-block-theme'
        ) }
    </Text>;
}
