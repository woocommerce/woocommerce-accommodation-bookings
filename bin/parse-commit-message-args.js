/**
 * Parse the commit message.
 *
 * This bin is used in ci.yml to parse the current commit message and extract version numbers for testing.
 */
const regex =
	/--test-([\w-]+)=((latest|nightly)|(\d+(\.\d+)*(-\w+(\.\w+)*)*))/g;

function searchForKey( entryString, key ) {
	const matches = [];
	let match;

	while ( ( match = regex.exec( entryString ) ) !== null ) {
		if ( match[ 1 ] === key ) {
			matches.push( match[ 2 ] );
		}
	}

	return matches;
}

const entryString = process.argv[ 2 ];
const key = process.argv[ 3 ];
const defaultValue = process.argv[ 4 ];

if ( entryString === undefined || ! key || ! defaultValue ) {
	process.stderr.write(
		'Usage: node script.js <entry string> <key> <default value>\n'
	);
	process.exitCode = 1;
} else {
	const results = searchForKey( entryString, key );
	// CI reads the value from stdout.
	process.stdout.write(
		`${ results.length > 0 ? results[ 0 ] : defaultValue }\n`
	);
}
