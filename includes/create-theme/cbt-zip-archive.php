<?php

if ( class_exists( 'ZipArchive' ) ) {

	// This Class extends the ZipArchive class to add the theme slug as a base folder for all the files
	class CBT_Zip_Archive extends ZipArchive {

		private string $theme_folder;

		public function __construct( $theme_slug ) {
			// If the original theme is in a subfolder the theme slug will be the last part of the path
			$complete_slug      = explode( DIRECTORY_SEPARATOR, $theme_slug );
			$folder             = end( $complete_slug );
			$this->theme_folder = $folder;
		}

		public function addFromStringToTheme( $name, $content ) {
			$name = $this->theme_folder . '/' . $name;
			return parent::addFromString( $name, $content );
		}

		public function addFileToTheme( $filepath, $entryname ) {
			$entryname = $this->theme_folder . '/' . $entryname;
			return parent::addFile( $filepath, $entryname );
		}

		public function addThemeDir( $dirname ) {
			$dirname = $this->theme_folder . '/' . $dirname;
			return parent::addEmptyDir( $dirname );
		}
	}

}
