import { __ } from '@wordpress/i18n';
import {
    // eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalText as Text,
    // eslint-disable-next-line
	__experimentalHeading as Heading,
    // eslint-disable-next-line
	__experimentalNavigatorToParentButton as NavigatorToParentButton,
    PanelBody,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	chevronLeft,
} from '@wordpress/icons';
import { GitIntegrationForm } from './git-config';
import { GitNotInstalledError } from './git-errors';
import { GitChanges } from './git-changes';

export const GitIntegrationPanel = function() {
    const [gitConfig, setGitConfig] = useState({});
    const [changes, setChanges] = useState([]);

    useEffect(() => {
        apiFetch( {
			path: '/create-block-theme/v1/get-git-config',
			method: 'GET',
		} ).then( ( response ) => {
            setGitConfig(response);
        }).catch(() => {
            setGitConfig({});
        });
    }, []);

    useEffect(() => {
        if (!gitConfig.git_configured) {
            return
        }

        apiFetch( {
			path: '/create-block-theme/v1/get-git-changes',
			method: 'POST',
            headers: {
				'Content-Type': 'application/json',
			},
		} ).then( ( response ) => {
            setChanges(response);
        }).catch(() => {
            setChanges([]);
        });
    }, [gitConfig.git_configured]);

    function handleConfigChange(git_configured) {
        setGitConfig({
            ...gitConfig,
            git_configured,
        })
    }

    function handleCommit(commit_status) {
        setGitConfig({
            ...gitConfig,
            git_configured: true,
        })
    }

    return <PanelBody>
        <Heading>
            <NavigatorToParentButton icon={ chevronLeft }>
                { __( 'Git Integration', 'create-block-theme' ) }
            </NavigatorToParentButton>
        </Heading>
        <VStack>
            {
                !gitConfig.version ? <GitNotInstalledError /> : (
                    !gitConfig.git_configured ? <GitIntegrationForm onChange={handleConfigChange} /> :
                        <GitChanges changes={changes} onCommit={handleCommit} />
                )
            }
        </VStack>
    </PanelBody>;
}
