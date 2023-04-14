import styles from './styles.module.css';

export function CreateThemePageLayout( { header, sidebar, main } ) {
	return (
		<div className={ styles.pageLayout }>
			<div className={ styles.pageHeader }>{ header }</div>
			<div className={ styles.pageContainer }>
				{ sidebar }
				{ main }
			</div>
		</div>
	);
}
