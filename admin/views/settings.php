<?php
/**
 * Settings screen markup.
 *
 * @package RepoBridgeForge
 * @var RepoBridgeForge_Settings $settings
 * @var RepoBridgeForge_Logger   $logger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$repobridgeforge_config    = $settings->get_all();
$repobridgeforge_has_token = $settings->has_token();
$repobridgeforge_last_sync = get_option( 'repobridgeforge_last_sync', '' );
$repobridgeforge_is_ready  = ( '' !== $repobridgeforge_config['owner'] && '' !== $repobridgeforge_config['repo'] && $repobridgeforge_has_token );

// Read notice (redirect params are display-only, sanitized here).
$repobridgeforge_notice = isset( $_GET['repobridgeforge_notice'] ) ? sanitize_key( wp_unslash( $_GET['repobridgeforge_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$repobridgeforge_detail = isset( $_GET['repobridgeforge_detail'] ) ? sanitize_text_field( wp_unslash( $_GET['repobridgeforge_detail'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$repobridgeforge_notices = array(
	'saved'      => array( 'success', __( 'Settings saved.', 'repobridgeforge' ) ),
	'synced'     => array( 'success', __( 'Sync complete. See the activity log below.', 'repobridgeforge' ) ),
	'test_ok'    => array( 'success', __( 'Connected to repository:', 'repobridgeforge' ) ),
	'sync_error' => array( 'error', __( 'Sync failed:', 'repobridgeforge' ) ),
	'test_error' => array( 'error', __( 'Connection failed:', 'repobridgeforge' ) ),
);

$repobridgeforge_statuses  = array( 'publish', 'draft', 'pending', 'private' );
$repobridgeforge_freqs     = array(
	'manual'          => __( 'Manual only', 'repobridgeforge' ),
	'repobridgeforge_15min' => __( 'Every 15 minutes', 'repobridgeforge' ),
	'hourly'          => __( 'Hourly', 'repobridgeforge' ),
	'twicedaily'      => __( 'Twice daily', 'repobridgeforge' ),
	'daily'           => __( 'Daily', 'repobridgeforge' ),
);
$repobridgeforge_posttypes = get_post_types( array( 'show_ui' => true ), 'objects' );
$repobridgeforge_post_url  = esc_url( admin_url( 'admin-post.php' ) );
?>
<div class="wrap repobridgeforge-wrap">

	<header class="rp-hero">
		<div class="rp-hero-brand">
			<span class="rp-logo">RBF</span>
			<div>
				<h1>Repo Bridge Forge</h1>
				<p class="rp-tag"><?php esc_html_e( 'Publish WordPress content from a GitHub repository', 'repobridgeforge' ); ?></p>
			</div>
		</div>
		<div class="rp-hero-meta">
			<?php if ( $repobridgeforge_is_ready ) : ?>
				<span class="rp-pill is-on"><?php esc_html_e( 'Configured', 'repobridgeforge' ); ?></span>
			<?php else : ?>
				<span class="rp-pill is-off"><?php esc_html_e( 'Not connected', 'repobridgeforge' ); ?></span>
			<?php endif; ?>
			<span class="rp-version">v<?php echo esc_html( REPOBRIDGEFORGE_VERSION ); ?></span>
		</div>
	</header>

	<?php if ( '' !== $repobridgeforge_notice && isset( $repobridgeforge_notices[ $repobridgeforge_notice ] ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $repobridgeforge_notices[ $repobridgeforge_notice ][0] ); ?> is-dismissible">
			<p>
				<?php echo esc_html( $repobridgeforge_notices[ $repobridgeforge_notice ][1] ); ?>
				<?php if ( '' !== $repobridgeforge_detail ) : ?><code><?php echo esc_html( $repobridgeforge_detail ); ?></code><?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="rp-layout">
		<main class="rp-main">
			<form method="post" action="<?php echo $repobridgeforge_post_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<input type="hidden" name="action" value="repobridgeforge_save" />
				<?php wp_nonce_field( 'repobridgeforge_save' ); ?>

				<div class="rp-card">
					<h2><?php esc_html_e( 'Repository', 'repobridgeforge' ); ?></h2>
					<p class="rp-desc"><?php esc_html_e( 'Where Repo Bridge Forge reads your content from. It only ever reads, never writes.', 'repobridgeforge' ); ?></p>

					<div class="rp-grid2">
						<div class="rp-field">
							<label class="rp-label" for="repobridgeforge-owner"><?php esc_html_e( 'Owner', 'repobridgeforge' ); ?></label>
							<input name="owner" id="repobridgeforge-owner" type="text" value="<?php echo esc_attr( $repobridgeforge_config['owner'] ); ?>" placeholder="octocat" />
							<p class="description"><?php esc_html_e( 'The user or organization that owns the repo.', 'repobridgeforge' ); ?></p>
						</div>
						<div class="rp-field">
							<label class="rp-label" for="repobridgeforge-repo"><?php esc_html_e( 'Repository', 'repobridgeforge' ); ?></label>
							<input name="repo" id="repobridgeforge-repo" type="text" value="<?php echo esc_attr( $repobridgeforge_config['repo'] ); ?>" placeholder="my-content" />
							<p class="description"><?php esc_html_e( 'The repository name, without the owner.', 'repobridgeforge' ); ?></p>
						</div>
						<div class="rp-field">
							<label class="rp-label" for="repobridgeforge-branch"><?php esc_html_e( 'Branch', 'repobridgeforge' ); ?></label>
							<input name="branch" id="repobridgeforge-branch" type="text" value="<?php echo esc_attr( $repobridgeforge_config['branch'] ); ?>" placeholder="main" />
						</div>
						<div class="rp-field">
							<label class="rp-label" for="repobridgeforge-path"><?php esc_html_e( 'Folder path', 'repobridgeforge' ); ?></label>
							<input name="path" id="repobridgeforge-path" type="text" value="<?php echo esc_attr( $repobridgeforge_config['path'] ); ?>" placeholder="content/posts" />
							<p class="description"><?php esc_html_e( 'Optional. Leave empty to use the repo root.', 'repobridgeforge' ); ?></p>
						</div>
					</div>

					<div class="rp-field" style="margin-top:16px">
						<label class="rp-label" for="repobridgeforge-token"><?php esc_html_e( 'Access token', 'repobridgeforge' ); ?></label>
						<input name="token" id="repobridgeforge-token" type="password" autocomplete="new-password" value="" placeholder="<?php echo $repobridgeforge_has_token ? esc_attr__( 'Saved — leave blank to keep the current token', 'repobridgeforge' ) : 'ghp_...'; ?>" />
						<p class="description">
							<?php esc_html_e( 'Fine-grained personal access token with read-only Contents access to this repository. Stored encrypted.', 'repobridgeforge' ); ?>
							<a href="https://github.com/settings/tokens?type=beta" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create one', 'repobridgeforge' ); ?></a>
						</p>
					</div>
				</div>

				<div class="rp-card">
					<h2><?php esc_html_e( 'Content mapping', 'repobridgeforge' ); ?></h2>
					<p class="rp-desc"><?php esc_html_e( 'How each Markdown file becomes a post. A file can override these with its own front matter.', 'repobridgeforge' ); ?></p>
					<div class="rp-grid2">
						<div class="rp-field">
							<label class="rp-label" for="repobridgeforge-post-type"><?php esc_html_e( 'Post type', 'repobridgeforge' ); ?></label>
							<select name="post_type" id="repobridgeforge-post-type">
								<?php foreach ( $repobridgeforge_posttypes as $repobridgeforge_pt ) : ?>
									<option value="<?php echo esc_attr( $repobridgeforge_pt->name ); ?>" <?php selected( $repobridgeforge_config['post_type'], $repobridgeforge_pt->name ); ?>><?php echo esc_html( $repobridgeforge_pt->labels->singular_name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="rp-field">
							<label class="rp-label" for="repobridgeforge-status"><?php esc_html_e( 'Default status', 'repobridgeforge' ); ?></label>
							<select name="default_status" id="repobridgeforge-status">
								<?php foreach ( $repobridgeforge_statuses as $repobridgeforge_st ) : ?>
									<option value="<?php echo esc_attr( $repobridgeforge_st ); ?>" <?php selected( $repobridgeforge_config['default_status'], $repobridgeforge_st ); ?>><?php echo esc_html( $repobridgeforge_st ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>

				<div class="rp-card">
					<h2><?php esc_html_e( 'Sync behavior', 'repobridgeforge' ); ?></h2>
					<p class="rp-desc"><?php esc_html_e( 'When Repo Bridge Forge checks GitHub, and what to do about files you remove.', 'repobridgeforge' ); ?></p>
					<div class="rp-field">
						<label class="rp-label" for="repobridgeforge-frequency"><?php esc_html_e( 'Automatic sync', 'repobridgeforge' ); ?></label>
						<select name="frequency" id="repobridgeforge-frequency">
							<?php foreach ( $repobridgeforge_freqs as $repobridgeforge_key => $repobridgeforge_label ) : ?>
								<option value="<?php echo esc_attr( $repobridgeforge_key ); ?>" <?php selected( $repobridgeforge_config['frequency'], $repobridgeforge_key ); ?>><?php echo esc_html( $repobridgeforge_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Runs through WP-Cron. On quiet sites, cron fires when someone visits.', 'repobridgeforge' ); ?></p>
					</div>
					<div class="rp-field">
						<span class="rp-label"><?php esc_html_e( 'When a file is deleted from the repo', 'repobridgeforge' ); ?></span>
						<label class="rp-radio"><input type="radio" name="delete_behavior" value="ignore" <?php checked( $repobridgeforge_config['delete_behavior'], 'ignore' ); ?> /> <?php esc_html_e( 'Leave the post as-is (log only)', 'repobridgeforge' ); ?></label>
						<label class="rp-radio"><input type="radio" name="delete_behavior" value="trash" <?php checked( $repobridgeforge_config['delete_behavior'], 'trash' ); ?> /> <?php esc_html_e( 'Move the post to Trash', 'repobridgeforge' ); ?></label>
					</div>
				</div>

				<?php submit_button( __( 'Save settings', 'repobridgeforge' ) ); ?>
			</form>

			<div class="rp-card">
				<h2><?php esc_html_e( 'Run a sync', 'repobridgeforge' ); ?></h2>
				<p class="rp-desc"><?php esc_html_e( 'Check the connection, or pull the latest content right now.', 'repobridgeforge' ); ?></p>
				<div class="rp-actions">
					<form method="post" action="<?php echo $repobridgeforge_post_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
						<input type="hidden" name="action" value="repobridgeforge_test" />
						<?php wp_nonce_field( 'repobridgeforge_test' ); ?>
						<?php submit_button( __( 'Test connection', 'repobridgeforge' ), 'secondary', 'submit', false ); ?>
					</form>
					<form method="post" action="<?php echo $repobridgeforge_post_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
						<input type="hidden" name="action" value="repobridgeforge_sync_now" />
						<?php wp_nonce_field( 'repobridgeforge_sync_now' ); ?>
						<?php submit_button( __( 'Sync now', 'repobridgeforge' ), 'primary', 'submit', false ); ?>
					</form>
					<?php if ( '' !== $repobridgeforge_last_sync ) : ?>
						<span class="rp-last"><?php printf( /* translators: date/time of last sync. */ esc_html__( 'Last sync: %s', 'repobridgeforge' ), esc_html( $repobridgeforge_last_sync ) ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<h2 style="margin-top:26px"><?php esc_html_e( 'Activity log', 'repobridgeforge' ); ?></h2>
			<?php $repobridgeforge_entries = array_reverse( $logger->get_entries() ); ?>
			<?php if ( empty( $repobridgeforge_entries ) ) : ?>
				<div class="rp-card"><p class="rp-desc" style="margin:0"><?php esc_html_e( 'Nothing yet. Run a sync and the results will show up here.', 'repobridgeforge' ); ?></p></div>
			<?php else : ?>
				<div class="rp-log">
					<table class="widefat striped">
						<thead>
							<tr>
								<th style="width:170px"><?php esc_html_e( 'Time', 'repobridgeforge' ); ?></th>
								<th style="width:100px"><?php esc_html_e( 'Level', 'repobridgeforge' ); ?></th>
								<th><?php esc_html_e( 'Message', 'repobridgeforge' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $repobridgeforge_entries, 0, 40 ) as $repobridgeforge_e ) : ?>
								<tr>
									<td><?php echo esc_html( $repobridgeforge_e['time'] ); ?></td>
									<td><span class="rp-level <?php echo esc_attr( $repobridgeforge_e['level'] ); ?>"><?php echo esc_html( $repobridgeforge_e['level'] ); ?></span></td>
									<td><?php echo esc_html( $repobridgeforge_e['message'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</main>

		<aside class="rp-side">
			<div class="rp-card">
				<h2><?php esc_html_e( 'Quick start', 'repobridgeforge' ); ?></h2>
				<ol class="rp-steps">
					<li><?php esc_html_e( 'Enter your repository owner, name, and branch.', 'repobridgeforge' ); ?></li>
					<li><?php esc_html_e( 'Paste a read-only access token for that repo.', 'repobridgeforge' ); ?></li>
					<li><?php esc_html_e( 'Pick the post type and default status.', 'repobridgeforge' ); ?></li>
					<li><?php esc_html_e( 'Save, then click Test connection.', 'repobridgeforge' ); ?></li>
					<li><?php esc_html_e( 'Click Sync now, or set an automatic schedule.', 'repobridgeforge' ); ?></li>
				</ol>
			</div>

			<div class="rp-card">
				<h2><?php esc_html_e( 'File format', 'repobridgeforge' ); ?></h2>
				<p class="rp-desc" style="margin-bottom:12px"><?php esc_html_e( 'Each Markdown file starts with front matter, then the body.', 'repobridgeforge' ); ?></p>
				<div class="rp-code">---
title: "Getting Started"
slug: getting-started
status: publish
type: post
categories: [Guides]
tags: [setup, intro]
date: 2026-08-14
---

# Getting Started

Your **Markdown** body here.</div>
			</div>

			<div class="rp-card">
				<h2><?php esc_html_e( 'What things mean', 'repobridgeforge' ); ?></h2>
				<dl class="rp-glossary">
					<dt><?php esc_html_e( 'Branch', 'repobridgeforge' ); ?></dt>
					<dd><?php esc_html_e( 'The line of the repo to publish from, usually main. Content on other branches is ignored.', 'repobridgeforge' ); ?></dd>
					<dt><?php esc_html_e( 'Folder path', 'repobridgeforge' ); ?></dt>
					<dd><?php esc_html_e( 'Limit syncing to one sub-folder, so the rest of the repo is left alone.', 'repobridgeforge' ); ?></dd>
					<dt><?php esc_html_e( 'Access token', 'repobridgeforge' ); ?></dt>
					<dd><?php esc_html_e( 'A read-only key that lets Repo Bridge Forge see the repo. It is stored encrypted and never shared.', 'repobridgeforge' ); ?></dd>
					<dt><?php esc_html_e( 'Front matter', 'repobridgeforge' ); ?></dt>
					<dd><?php esc_html_e( 'The block between the --- lines at the top of a file. It sets the title, status, categories, and more.', 'repobridgeforge' ); ?></dd>
					<dt><?php esc_html_e( 'Change detection', 'repobridgeforge' ); ?></dt>
					<dd><?php esc_html_e( 'Repo Bridge Forge remembers each file by its Git checksum, so it only updates posts when a file actually changes.', 'repobridgeforge' ); ?></dd>
				</dl>
			</div>

			<div class="rp-card rp-support">
				<h2><?php esc_html_e( 'Enjoying Repo Bridge Forge?', 'repobridgeforge' ); ?></h2>
				<p><?php esc_html_e( 'It is free and always will be. If it saves you time, a coffee keeps it going.', 'repobridgeforge' ); ?></p>
				<a class="rp-kofi" href="https://ko-fi.com/gunjanjaswal" target="_blank" rel="noopener noreferrer">
					<span aria-hidden="true">&#9829;</span> <?php esc_html_e( 'Support on Ko-fi', 'repobridgeforge' ); ?>
				</a>
			</div>

			<div class="rp-card rp-author">
				<?php esc_html_e( 'Built by', 'repobridgeforge' ); ?>
				<a href="https://www.gunjanjaswal.me" target="_blank" rel="noopener noreferrer">Gunjan Jaswal</a>
			</div>
		</aside>
	</div>
</div>
