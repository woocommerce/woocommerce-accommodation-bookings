const assert = require( 'node:assert/strict' );
const { spawnSync } = require( 'node:child_process' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { test: nodeTest } = require( 'node:test' );

const root = path.resolve( __dirname, '..' );
const launcher = path.join( __dirname, 'extension-tooling.sh' );
const packageName = '@woocommerce/extension-tooling';
const manifest = require( '../package.json' );

nodeTest(
	'the PHP-only tooling pin matches the pnpm dependency and integrity',
	() => {
		const source = fs.readFileSync( launcher, 'utf8' );
		const version = source.match( /^version=(\S+)$/m )[ 1 ];
		const hex = source.match( /^\s*sha512=([a-f0-9]{128})$/m )[ 1 ];
		const integrity = Buffer.from( hex, 'hex' ).toString( 'base64' );
		const lock = fs.readFileSync(
			path.join( root, 'pnpm-lock.yaml' ),
			'utf8'
		);

		assert.equal( version, manifest.devDependencies[ packageName ] );
		assert.ok(
			lock.includes(
				`  '${ packageName }@${ version }':\n    resolution: {integrity: sha512-${ integrity }}`
			),
			'The PHP-only archive must match the locked npm package'
		);
	}
);

nodeTest(
	'the PHP-only launcher skips disabled installs and rejects mismatched pins and archives',
	( t ) => {
		const fixture = fs.mkdtempSync(
			path.join( os.tmpdir(), 'woo-extension-tooling-' )
		);
		t.after( () => fs.rmSync( fixture, { recursive: true, force: true } ) );
		const bin = path.join( fixture, 'bin' );
		fs.mkdirSync( bin );
		const script = path.join( bin, 'extension-tooling.sh' );
		fs.copyFileSync( launcher, script );
		// Supply a bad archive without making a network request.
		fs.writeFileSync(
			path.join( bin, 'curl' ),
			'#!/usr/bin/env bash\nwhile [[ "$1" != --output ]]; do shift; done\nprintf "wrong archive" > "$2"\n',
			{ mode: 0o755 }
		);
		const env = {
			...process.env,
			PATH: `${ bin }${ path.delimiter }${ process.env.PATH }`,
		};
		const disabled = spawnSync( 'bash', [ script, 'install.sh' ], {
			cwd: fixture,
			env: { ...env, RUN_UNIT_TESTS: '0' },
			encoding: 'utf8',
		} );
		assert.equal( disabled.status, 0, disabled.stderr );

		const run = ( version ) => {
			fs.writeFileSync(
				path.join( fixture, 'package.json' ),
				JSON.stringify( {
					devDependencies: { [ packageName ]: version },
				} )
			);
			return spawnSync( 'bash', [ script, 'woo-config.sh' ], {
				cwd: fixture,
				env,
				encoding: 'utf8',
			} );
		};

		const wrongPin = run( '999.0.0' );
		assert.equal( wrongPin.status, 1 );
		assert.match( wrongPin.stderr, /Update the extension tooling version/ );

		const wrongArchive = run( manifest.devDependencies[ packageName ] );
		assert.equal( wrongArchive.status, 1 );
		assert.match(
			wrongArchive.stderr,
			/Extension tooling archive checksum does not match/
		);
		assert.equal( wrongArchive.stdout, '' );
	}
);
