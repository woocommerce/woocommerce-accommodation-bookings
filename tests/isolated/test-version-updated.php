<?php
// phpcs:ignoreFile -- Unit test files use the test-*.php convention, which conflicts with the class-file-name sniff.
/**
 * Tests for bin/version_updated.sh.
 *
 * @package WooCommerce Extension Tooling
 */

use PHPUnit\Framework\TestCase;

/**
 * Runs bin/version_updated.sh on PHP files. The docblock before the first class, interface, trait
 * or enum in a changed file must contain `@version x.x.x`.
 *
 * @version x.x.x
 */
class Version_Updated_Script_Tests extends TestCase {

	/**
	 * Files and whether the check passes them.
	 *
	 * @return array
	 */
	public function files() {
		$tagged   = "/**\n * @version x.x.x\n */";
		$untagged = "/**\n * @version 1.0.0\n */";
		return array(
			'tagged class'              => array( "$tagged\nclass Foo {}", true ),
			'untagged class'            => array( "$untagged\nclass Foo {}", false ),
			'tag in the file header'    => array( "$tagged\n\n$untagged\nclass Foo {}", true ),
			'tag only on a later class' => array( "$untagged\nclass One {}\n$tagged\nclass Two {}", false ),
			'tag only after the class'  => array( "$untagged\nclass Foo {\n\t$tagged\n\tpublic function a() {}\n}", false ),
			'abstract'                  => array( "$untagged\nabstract class Foo {}", false ),
			'final'                     => array( "$untagged\nfinal class Foo {}", false ),
			'interface'                 => array( "$untagged\ninterface Foo {}", false ),
			'trait'                     => array( "$untagged\ntrait Foo {}", false ),
			'enum'                      => array( "$untagged\nenum Foo {}", false ),
			'indented, tab after class' => array( "if ( true ) {\n\t$untagged\n\tclass\tFoo_2 {}\n}", false ),
			'no declaration'            => array( "$untagged\nfunction foo() {}", true ),
		);
	}

	/**
	 * @dataProvider files
	 *
	 * @param string $source Code after `<?php`.
	 * @param bool   $passes Whether the check passes the file.
	 */
	public function test_check( $source, $passes ) {
		$file = tempnam( sys_get_temp_dir(), 'version-updated' );
		file_put_contents( $file, "<?php\n$source\n" );
		// The pull request environment, so the script does not skip itself on trunk.
		$env     = array_merge( getenv(), array( 'GITHUB_ACTIONS' => 'true', 'GITHUB_BASE_REF' => 'trunk', 'GITHUB_ENV' => '/dev/null' ) );
		try {
			$process = proc_open( array( 'bash', dirname( __DIR__, 2 ) . '/bin/version_updated.sh', $file ), array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, null, $env );
			$this->assertIsResource( $process, 'Could not start bash.' );
			$output = stream_get_contents( $pipes[1] ) . stream_get_contents( $pipes[2] );
			fclose( $pipes[1] );
			fclose( $pipes[2] );
			$this->assertSame( $passes ? 0 : 1, proc_close( $process ), $output );
		} finally {
			unlink( $file );
		}
	}
}
