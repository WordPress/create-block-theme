<?php

if ( class_exists( 'ZipArchive' ) ) {

	// This Class extends the ZipArchive class to add the theme slug as a base folder for all the files
	class CbtZipArchive extends ZipArchive {

		private string $theme_slug;

		function __construct( $theme_slug ) {
			$this->theme_slug = $theme_slug;
		}

		function addFromString( string $name, string $content, int $flags = ZipArchive::FL_OVERWRITE ) {
			$name = $this->theme_slug . '/' . $name;
			return parent::addFromString( $name, $content );
		}

		function addFile( string $filepath, string $entryname = '', int $start = 0, int $length = 0, int $flags = ZipArchive::FL_OVERWRITE ) {
			$entryname = $this->theme_slug . '/' . $entryname;
			return parent::addFile( $filepath, $entryname );
		}

		function addEmptyDir( string $dirname, int $flags = 0 ) {
			$dirname = $this->theme_slug . '/' . $dirname;
			return parent::addEmptyDir( $dirname );
		}

	}

}
