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
import { GitIntegrationForm, ShowGitConfig } from './git-repo';
import { GitNotInstalledError } from './git-errors';
import { GitChanges } from './git-changes';
import ScreenHeader from '../components/screen-header';
import { GitBranchConfig } from './git-branch';

export const GitIntegrationPanel = function() {
    const [gitConfig, setGitConfig] = useState({});
    const [changes, setChanges] = useState([]);

    useEffect(() => {
        fetchGitConfig();
    }, []);

    useEffect(() => {
        if (!gitConfig.is_git_initialized) {
            return
        }

        fetchGitChanges();
    }, [gitConfig.is_git_initialized]);

    function handleConfigChange(config_status) {
        if (config_status !== 'success' ) {
            return;
        }

        fetchGitConfig();
    }

    function handleRepoDisconnect() {
        fetchGitConfig();
    }

    function handleCommit(commit_status) {
        setGitConfig({
            ...gitConfig,
            is_git_initialized: true,
        })
    }

    function fetchGitConfig() {
        apiFetch( {
			path: '/create-block-theme/v1/get-git-config',
			method: 'GET',
		} ).then( ( response ) => {
            setGitConfig(response);
        }).catch(() => {
            setGitConfig({});
        });
    }

    function fetchGitChanges() {
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
    }

    return <>
        <ScreenHeader title={ __( 'Git Integration', 'create-block-theme' ) } description={''} />
        <VStack>
            {
                !gitConfig.version ? <GitNotInstalledError /> : (
                    !gitConfig.is_git_initialized ? <GitIntegrationForm onChange={handleConfigChange} /> :
                        <>
                            <ShowGitConfig config={gitConfig} onDisconnect={handleRepoDisconnect} />
                            <GitBranchConfig config={gitConfig} />
                            <GitChanges config={gitConfig} changes={changes} onCommit={handleCommit} />
                        </>
                )
            }
        </VStack>
    </>;
}
