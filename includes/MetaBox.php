<?php
/**
 * Meta Box management for WordPress
 *
 * @author: Fred Dessaint <contact@freddessaint.com> http://www.freddessaint.com/
 * @version: 1.1
 *
 */

class MetaBox {
	protected $metabox;
	protected $fields;
	protected $nonce;

	public function __construct($args) {
		if(is_admin() == false) { return; }

		$this->metabox = $args;
		$this->nonce = $this->metabox['id'].'_nonce';
		$this->fields = array();

		// Build a default array of fields linked to the meta data
		foreach($this->metabox['fields'] as $field) {
			if($field['linked'] == true) {
				$this->fields[$field['id']] = null;
			}
		}

		add_action('add_meta_boxes', array($this, 'add'));
		add_action('save_post', array($this, 'save'));
	}

	public function add() {
		foreach($this->metabox['post-types'] as $name => $post_type)
		{
			if($post_type->show_ui == true) {
				add_meta_box(
					$this->metabox['id'],
					$this->metabox['title'],
					array($this, 'show'),
					$name,
					$this->metabox['context'],
					$this->metabox['priority']
				);
			}
		}
	}
	
	public function show($post) {
		// Get registered data fieldset
		$data = $this->get($this->metabox['id']);
		foreach($this->fields as $key => $field) {
			if(isset($data[$key]) == true) {
				$this->fields[$key] = $data[$key];
			}
		}

		// Use nonce for verification
		echo '<input type="hidden" name="', $this->nonce, '" value="', wp_create_nonce('metabox'.$this->metabox['id']), '">';
		echo '<table class="'.(isset($this->metabox['class']) == true && empty($this->metabox['class']) == false ? $this->metabox['class'] : 'form-table').'">';

		foreach($this->metabox['fields'] as $field) {
			// Get the post value or the defaut value if any
			$default = (isset($field['default']) == true ? $field['default'] : '');
			$value = (isset($this->fields[$field['id']]) == true ? $this->fields[$field['id']] : $default);

			echo '<tr>', '<th scope="row">';

			if($field['type'] != 'radio' && $field['type'] != 'checkbox') { echo '<label for="', $field['id'], '">', $field['name'], '</label>'; }
			else { echo $field['name']; }
			
			echo '</th>', '<td>';

			switch($field['type']) {
			case 'text':
				echo '<input type="text" name="', $this->metabox['id'], '[', $field['id'], ']', '" id="', $field['id'], '" value="', $value, '" size="30"';
				if(isset($field['class']) == true && empty($field['class']) == false) { echo ' class="', $field['class'], '"'; }
				echo '>';
				if(isset($field['desc']) == true && empty($field['desc']) == false) { echo '<p>'.$field['desc'].'</p>'; }
				break;

			case 'textarea':
				echo '<textarea name="', $this->metabox['id'], '[', $field['id'], ']', '" id="', $field['id'], '" cols="60" rows="4"';
				if(isset($field['class']) == true && empty($field['class']) == false) { echo ' class="', $field['class'], '"'; }
				echo '>', $value, '</textarea>';
				if(isset($field['desc']) == true && empty($field['desc']) == false) { echo '<p>'.$field['desc'].'</p>'; }
				break;

			case 'select':
				echo '<select name="', $this->metabox['id'], '[', $field['id'], ']', '" id="', $field['id'], '">';
				foreach ($field['options'] as $key => $option) {
					echo '<option value="', $key, '"', $value == $key ? ' selected="selected"' : '', '>', $option, '</option>';
				}
				echo '</select>';
				if(isset($field['desc']) == true && empty($field['desc']) == false) { echo '<p>'.$field['desc'].'</p>'; }
				break;

			case 'radio':
				echo '<fieldset>', '<legend class="screen-reader-text"><span>', $field['name'], '</span></legend>';
				foreach($field['options'] as $option) {
					echo '<input type="radio" name="', $this->metabox['id'], '[', $field['id'], ']', '" value="', $option['value'], '"', ($value == $option['value'] ? ' checked="checked"' : ''), '> ', "\n";
					echo '<label for="', $field['id'], '">', $option['name'], '</label>', "\n";
				}
				echo '</fieldset>';
				if(isset($field['desc']) == true && empty($field['desc']) == false) { echo '<p>'.$field['desc'].'</p>'; }
				break;

			case 'checkbox':
				echo '<fieldset>', '<legend class="screen-reader-text"><span>', $field['name'], '</span></legend>';
				foreach($field['options'] as $option) {
					echo '<input type="checkbox" name="', $this->metabox['id'], '[', $field['id'], ']', '" id="', $field['id'], '"', ($meta ? ' checked="checked"' : ''), '>', "\n";
					echo '<label for="', $field['id'], '">', $option['name'], '</label>', "\n";
				}
				echo '</fieldset>';
				if(isset($field['desc']) == true && empty($field['desc']) == false) { echo '<p>'.$field['desc'].'</p>'; }
				break;
			case 'custom':
				if(isset($field['render']) == true) {
					echo call_user_func_array($field['render'], array($value));
				}
				break;
			}
			echo '</td>', '</tr>';
		}
		echo '</table>';
	}

	public function get($name, $single = true, $post_id = null) {
		global $post;
		return get_post_meta((is_null($post_id) == false ? $post_id : $post->ID), $this->metabox['id'], $single);
	}

	public function set($name, $new, $post_id = null, $sanitize_callback = null) {
		global $post;
		$id = (is_null($post_id) == false ? $post_id : $post->ID);
		$new = (is_null($sanitize_callback) == false && is_callable($sanitize_callback)) ? call_user_func($sanitize_callback, $new, $this->metabox['id'], $id) : $new;
		return update_post_meta($id, $this->metabox['id'], $new);
	}

	public function delete($name, $post_id = null) {
		global $post;
		return delete_post_meta((is_null($post_id) == false ? $post_id : $post->ID), $this->metabox['id']);
	}
	
	public function save($post_id) {
		// Verify nonce
		if(!isset($_POST[$this->nonce]) || !wp_verify_nonce($_POST[$this->nonce], 'metabox'.$this->metabox['id'])) {
			return;
		}

		// Check revision
		if(wp_is_post_revision($post_id) > 0) {
			return;
		}
		
		// Check autosave
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check user permission
		if(!current_user_can('edit_page', $post_id)) {
			return;
		}

		// Update metadata whether the metabox is allowed to save data
		if($this->metabox['readonly'] == false) {
			if(sizeof($this->fields) > 0) {
				foreach($this->fields as $key => $field) {
					$this->fields[$key] = $_POST[$this->metabox['id']][$key];
				}
				$this->set($this->metabox['id'], $this->fields);
			}
			else {
				$this->delete($this->metabox['id']);
			}
		}
	}
}
?>