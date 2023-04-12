<?php
if ( ! isset( $_GET['lang'] ) ) {
	header( 'Location: /?lang=de' );
	exit;
}
switch ( $_GET['lang'] ) {
	case 'de':
	case 'es':
	case 'pt':
	case 'pl':
		$lang = $_GET['lang'] . '_' . strtoupper( $_GET['lang'] );
		$locale_slug = 'default';
		$lang_with_slug = $_GET['lang'] . '/' . $locale_slug;
		break;
	default:
		die( 'Language not allowed yet.' );
}
$allowed_plugins = array(
	'activitypub' => 'options-general.php?page=activitypub',
	'friends' => 'admin.php?page=friends',
	'wordpress-seo' => '',
	'sierotki' => 'admin.php?page=iworks_orphan_index',
	'chatrix' => '',
);

if ( isset( $_GET['plugin'] ) ) {
	if ( isset( $allowed_plugins[ $_GET['plugin'] ] ) ) {
		$plugin = $_GET['plugin'];
	} else {
		die( 'Plugin not allowed yet.' );
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>WordPress Playground</title>
	</head>
	<body>
		<iframe id="wp" style="width: 1200px; height: 800px"></iframe>
		<script type="importmap">
			{
				"imports": {
					"@wp-playground/client": "https://unpkg.com/@wp-playground/client/index.js",
					"@php-wasm/progress": "https://unpkg.com/@php-wasm/progress/index.js"
				}
			}
		</script>
		<script type="module">
			import { connectPlayground, login, installPluginsFromDirectory } from '@wp-playground/client';
			import { ProgressObserver } from '@php-wasm/progress';
			let response;
			const progress = new ProgressObserver();

			const client = await connectPlayground(
				document.getElementById('wp'),
				{ loadRemote: 'https://playground.wordpress.net/remote.html' }
			);
			const lang = '<?php echo $lang; ?>';
			await client.isReady();

			await login(client, 'admin', 'password');
			await client.mkdirTree('/wordpress/wp-content/languages/plugins');
			const languages = {
				'wp/dev/': '',
				'wp/dev/admin': 'admin-',
				'wp-plugins/glotpress/dev': 'plugins/glotpress-',
				<?php if ( isset( $plugin ) ) echo "'wp-plugins/" . $plugin . "/dev': 'plugins/" . $plugin . "-',"; ?>
			};
			const filters = {
				'wp': '&filters[term]=wp-admin',
				'wp/admin': '&filters[term]=wp-admin',
			};

			for ( const path in languages ) {
				for ( const format of [ 'po', 'mo' ] ) {
					await fetch( '/translate-proxy?url=' + escape( 'https://translate.wordpress.org/projects/' + path + '/<?php echo $lang_with_slug; ?>/export-translations?format=' + format + ( path in filters ? filters[path] : '' ) ) )
					  .then(response => response.arrayBuffer() )
					  .then(response => client.writeFile( '/wordpress/wp-content/languages/' + languages[path] + '<?php echo $lang; ?>.' + format, new Uint8Array(response) ) );
				}
			}
			response = await client.run({
				code: '<' + '?' + 'php ' + `
include 'wordpress/wp-load.php';
update_option('WPLANG', '<?php echo $lang; ?>');
update_option('permalink_structure','/%year%/%monthnum%/%day%/%postname%/');
update_option('gp_enable_local_translation', 1);
update_option('gp_enable_inline_translation', 1);
file_put_contents('/wordpress/wp-content/mu-plugins/gp-sqlite.php','<' . '?' . 'php' . PHP_EOL . <<<'ENDP'
add_filter('query', function( $query ) {
	return str_replace( ' BINARY ', ' ', $query);
});
ENDP
);
			`});
			console.log(response.text);
			await installPluginsFromDirectory( client, ['glotpress-local'<?php if ( $plugin ) echo ", '$plugin'"; ?>] );
			<?php if ( $plugin || isset( $_GET['wp'] ) ) : ?>
			response = await client.run({
				code: '<' + '?' + 'php ' + `
include 'wordpress/wp-load.php';
<?php if ( isset( $_GET['wp'] ) ) : ?>
$request = new WP_REST_Request();
$request->set_param( 'name', 'WordPress');
$request->set_param( 'path', 'wp/dev');
$request->set_param( 'locale', '<?php echo $lang; ?>');
$request->set_param( 'locale_slug', '<?php echo $locale_slug; ?>');
print_r( GP::$rest->create_local_project( $request ) );
<?php endif; ?>
<?php if ( $plugin ) : ?>
$request = new WP_REST_Request();
$request->set_param( 'name', '<?php echo $plugin; ?>');
$request->set_param( 'path', 'wp-plugins/<?php echo $plugin; ?>');
$request->set_param( 'locale', '<?php echo $lang; ?>');
$request->set_param( 'locale_slug', '<?php echo $locale_slug; ?>');
print_r( GP::$rest->create_local_project( $request ) );
<?php endif; ?>
			`});
			console.log(response.text);
			<?php if ( $plugin && $allowed_plugins[$plugin] ) : ?>
			await client.goTo('/wp-admin/<?php echo $allowed_plugins[$plugin]; ?>');
			<?php else: ?>
			await client.goTo('/wp-admin/');
			<?php endif; ?>
			<?php else: ?>
			await client.goTo('/wp-admin/admin.php?page=local-glotpress');
			<?php endif; ?>
// 			await client.goTo('/wp-admin/plugins.php');
// 			console.log(response.text);
//
		</script>
	</body>
</html>
