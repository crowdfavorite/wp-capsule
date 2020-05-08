<?php
	$server_term = 'cap_client_mapping[' . $post->ID . '][' . $taxonomy . '][server_term]';
	$term_ids = 'cap_client_mapping[' . $post->ID . '][' . $taxonomy . '][term_ids][]';
?>
<input
	type="hidden"
	name="<?php echo esc_attr($server_term); ?>"
	value="<?php echo esc_attr($post->post_title); ?>"
/>
<select name="<?php echo esc_attr($term_ids); ?>" class="cap-wide-dropdown">
	<optgroup label="<?php echo esc_attr($group_label); ?>">
	<option value="0">
		<?php esc_html_e('(not mapped)', 'capsule'); ?>
	</option>
	<?php
	if (is_array($terms) && ! empty($terms)) {
		foreach ($terms as $term) {
			if ($term->name === $post->post_title) {
				$match = true;
			}
			?>
			<option value="<?php echo (int) $term->term_id; ?>" <?php selected($selected_id, $term->term_id, true); ?>>
				<?php echo esc_html($term->name); ?>
			</option>
			<?php
		}
	}
	// If there are no local terms that match the server term, provide a 'Create Term' option.
	if (! $match) {
		?>
		<option value="-1">
			<?php
			// Translators: %s is the post title.
			printf(esc_html__('Create &quot;%s&quot; Locally', 'capsule'), esc_html($post->post_title));
			?>
		</option>
		<?php
	}
	?>
	</optgroup>
</select>
