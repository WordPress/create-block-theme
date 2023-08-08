import {
    PanelBody,
} from '@wordpress/components';

export const GitBranchConfig = function({config}) {
    return <PanelBody title={ __( 'Branch & Path config' ) }>
    <div style={{display: 'grid', gridTemplateColumns: '3fr 5fr', gap: '1rem'}}>
        <div>{ __( 'Branch' ) }</div>
        <div style={{wordBreak: 'break-word'}}>
            {config.current_branch || <a href='#'>Create branch</a>}
        </div>
        <div>{ __( 'Commit Path' ) }</div>
        <div style={{wordBreak: 'break-word'}}>
            {config.commit_path_prefix}
        </div>
    </div>
    </PanelBody>
}