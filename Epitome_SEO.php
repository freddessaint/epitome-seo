<?php
/**
 * Epitome SEO
 *
 * The main class of Epitome_SEO. Use class MetaBox as an include.
 *
 * @author: Fred Dessaint <contact@freddessaint.com> http://www.freddessaint.com/
 * @version: 1.2
 *
 */

class Epitome_SEO
{
	private static $instance = null;
	public $settings = null;
	public $tabs = null;

	public function __construct() {
		$this->init();
		$this->hooks();
	}

	/**
	 * Singleton
	 *
	 * @static
	 * @param	none
	 *
	 * @return	void
	 *
	 * @access	public
	 * @since	1.1
	 */
	public static function get_instance() {

		if(is_null(self::$instance)) {
			self::$instance = new Epitome_SEO();
		}

		return self::$instance;
	}

	/**
	 * Get the theme text domain.
	 *
	 * @param	none
	 *
	 * @return	String
	 *
	 * @access	public
	 * @since	1.1
	 */
	public static function get_text_domain() {
		return 'seoco';
	}

	/**
	 * Method to initialize plugin properties.
	 * Called by the class constructor.
	 *
	 * @param	none
	 *
	 * @return	void
	 *
	 * @since 1.0
	 */
	public function init() {

	}

	/**
	 * Method to initialize WordPress action and filter hooks.
	 * Called by the class constructor.
	 *
	 * @param	none
	 *
	 * @return	void
	 *
	 * @access	public
	 * @since	1.1
	 */
	public function hooks() {
		// Setting the action procedure
		add_action('plugins_loaded', array($this, 'setup_plugin'));
		add_action('admin_init', array($this, 'plugin_options_init'));
		add_action('admin_menu', array($this, 'plugin_options_add_page'));

		if(is_admin() == true && isset($_GET['taxonomy']) == true) {
			add_action($_GET['taxonomy'].'_edit_form_fields', array($this, 'render_term_custom_fields'), 10, 1);
		}
		add_action('edit_term', array($this, 'save_term_custom_fields'), 10, 3);
		
		// Setting the filter procedure
		add_filter('wp_title', array($this, 'wp_title'), 10, 3);
		add_filter('wp_head', array($this, 'wp_head'));
		add_filter('wp_footer', array($this, 'wp_footer'), 100);

		// Remore some procedure
		remove_action('wp_head', 'rel_canonical');
		remove_action('wp_head', 'wp_no_robots');
		remove_action('wp_head', 'noindex', 1);
	}

	/**
	 * Setup text domain, settings and tabs. Called by "plugins_loaded" action hook.
	 *
	 * @param none
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function setup_plugin() {
		load_plugin_textdomain($this->get_text_domain(), false, dirname(plugin_basename(__FILE__)).'/languages/');

		$tab = '';

		if(isset($_GET['tab'])) {
			$tab = $_GET['tab'];
		}
		else if($_SERVER['REQUEST_METHOD'] == 'POST') {
			parse_str(parse_url(wp_get_referer(), PHP_URL_QUERY), $var_array);

			if(isset($var_array['page']) && 'seo-core-options' == $var_array['page'] && isset($var_array['tab'])) {
				$tab = $var_array['tab'];
			}
		}

		switch($tab) {
			case 'models':
				$this->settings = $this->get_model_settings();
				break;
			case 'advanced':
				$this->settings = $this->get_advanced_settings();
				break;
			default:
				$this->settings = $this->get_model_settings();
				break;
		}

		$this->tabs = array(
			'models' => __("Models", $this->get_text_domain()),
			'advanced' => __("Advanced", $this->get_text_domain()),
		);
	}

	/**
	 * Render fields of the taxonomy meta data.
	 *
	 * @param Object $term - The current term to render.
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	function render_term_custom_fields($term) {
		// we need to know the values of our existing entries if any
		$taxometa = get_option('seoco_taxonomy_meta');
		$termmeta = (isset($taxometa[$term->taxonomy][$term->term_id]) == true ? $taxometa[$term->taxonomy][$term->term_id] : null);
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="optimized-name"><?php _e("Optimized name", $this->get_text_domain()); ?></label></th>
			<td>
				<input id="optimized-name" type="text" name="termmeta[optimized-name]" value="<?php if ( isset( $termmeta['optimized-name'] ) ) esc_attr_e( $termmeta['optimized-name'] ); ?>" />
				<p class="description"><?php _e("Use the optimized name if you want to improve taxonomies titles in your theme. The default name is used then for small areas of your layout.", $this->get_text_domain());
				// Utiliser le nom personnalisé à la place du nom par défaut si vous voulez améliorer les titres de taxinomies dans votre thème. Le nom par défaut s'utilise alors pour les petits emplacements de votre mise en page. ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="meta-title"><?php _e("Title for search engine", $this->get_text_domain()); ?></label></th>
			<td>
				<input id="meta-title" type="text" name="termmeta[meta-title]" value="<?php if ( isset( $termmeta['meta-title'] ) ) esc_attr_e( $termmeta['meta-title'] ); ?>" />
				<p class="description"><?php _e("Most search engines use a maximum of 60 chars for the title.", $this->get_text_domain()); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="meta-description"><?php _e("Meta description", $this->get_text_domain()); ?></label></th>
			<td>
				<textarea class="large-text" cols="50" rows="5" id="meta-description" name="termmeta[meta-description]"><?php if ( isset( $termmeta['meta-description'] ) ) esc_attr_e( $termmeta['meta-description'] ); ?></textarea>
				<p class="description"><?php _e("Most search engines use a maximum of 160 chars for the description.", $this->get_text_domain()); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="canonical"><?php _e("Canonical", $this->get_text_domain()); ?></label></th>
			<td>
				<input id="canonical" type="text" name="termmeta[canonical]" value="<?php if ( isset( $termmeta['canonical'] ) ) esc_attr_e( $termmeta['canonical'] ); ?>" />
				<p class="description"><?php _e("The canonical link is shown on this term's archive page.", $this->get_text_domain()); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="canonical"><?php _e("Long description", $this->get_text_domain()); ?></label></th>
			<td>
				<?php wp_editor(
					(isset($termmeta['long-description']) == true ? $termmeta['long-description'] : ''),
					'long-description',
					array(
						'textarea_name' => 'termmeta[long-description]',
						'textarea_rows' => 4,
						'editor_css' => '<style>.quicktags-toolbar input { width: auto; } .wp-editor-container textarea.wp-editor-area { border-style: none; }</style>',
						'media_buttons' => false,
					)
				); ?>
				<p class="description"><?php _e("The long description is used in the context of search engine optimization, while the built-in short description of WordPress can be used as an introduction in your theme.", $this->get_text_domain()); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e("Meta Robots Index", $this->get_text_domain()); ?></th>
			<?php
				$input = array(
					'name' => 'robots-index',
					'fields' => array(
						array(
							'id' => 'robots-index',
							'label' => __("Yes", $this->get_text_domain()),
							'value' => 'index'
						),
						array(
							'id' => 'robots-noindex',
							'label' => __("No", $this->get_text_domain()),
							'value' => 'noindex'
						)
					),
					'default' => 'index',
					'value' => ''
				);
				$input['value'] = (isset($termmeta[$input['name']]) == true ? $termmeta[$input['name']] : $input['default']);
			?>
			<td>
			<?php foreach($input['fields'] as $field): ?>
				<input id="<?php echo $field['id']; ?>" type="radio" <?php if($input['value'] == $field['value']): ?>checked="checked" <?php endif; ?>value="<?php echo $field['value']; ?>" name="termmeta[<?php echo $input['name']; ?>]">
				<label for="<?php echo $field['id']; ?>"><?php echo $field['label']; ?></label>
			<?php endforeach; ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><?php _e("Meta Robots Follow", $this->get_text_domain()); ?></th>
			<?php
				$input = array(
					'name' => 'robots-follow',
					'fields' => array(
						array(
							'id' => 'robots-follow',
							'label' => __("Yes", $this->get_text_domain()),
							'value' => 'follow'
						),
						array(
							'id' => 'robots-nofollow',
							'label' => __("No", $this->get_text_domain()),
							'value' => 'nofollow'
						)
					),
					'default' => 'follow',
					'value' => ''
				);
				$input['value'] = (isset($termmeta[$input['name']]) == true ? $termmeta[$input['name']] : $input['default']);
			?>
			<td>
			<?php foreach($input['fields'] as $field): ?>
				<input id="<?php echo $field['id']; ?>" type="radio" <?php if($input['value'] == $field['value']): ?>checked="checked" <?php endif; ?>value="<?php echo $field['value']; ?>" name="termmeta[<?php echo $input['name']; ?>]">
				<label for="<?php echo $field['id']; ?>"><?php echo $field['label']; ?></label>
			<?php endforeach; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Update the taxonomy meta data on save.
	 *
	 * @param int $term_id - ID of the term to save data for
	 * @param int $tt_id - The taxonomy_term_id for the term.
	 * @param string $taxonomy - The taxonmy the term belongs to.
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	function save_term_custom_fields($term_id, $tt_id, $taxonomy) {
		$taxometa = get_option('seoco_taxonomy_meta');

		$keys = array(
			'optimized-name', 'meta-title', 'meta-description', 'canonical', 'long-description',
			'robots-index', 'robots-follow'
		);
		foreach($keys as $key) {
			if(isset($_POST['termmeta'][$key]) == true) {
				$taxometa[$taxonomy][$term_id][$key] = stripslashes($_POST['termmeta'][$key]);
			}
		}

		update_option('seoco_taxonomy_meta', $taxometa);
	}

	/**
	 * Set all fields from a list of sections with saved value
	 * or a default value if not found in the database.
	 *
	 * @param Array $sections - List of sections.
	 * @param PHP string $option_name - The option name used for get_option().
	 *
	 * @return Array $sections - The modified list of sections.
	 *
	 * @since 1.1
	 */
	public function set_default_section_fields($sections, $option_name) {
		// Setting the default for every single field
		$defaults = array();
		foreach($sections as $section_id => $section) {
			foreach($section['fields'] as $field_id => $field) {
				// Setting the default value
				$defaults[$field['id']] = $field['render-args']['default'];
			}
		}

		// Create a value property from the saved data for every single field
		$values = get_option($option_name, $defaults);
		foreach($sections as $section_id => $section) {
			foreach($section['fields'] as $field_id => $field) {
				$sections[$section_id]['fields'][$field_id]['render-args']['value'] = (isset($values[$field['id']]) == true ? $values[$field['id']] : $defaults[$field['id']]);
			}
		}

		return $sections;
	}

	/**
	 * Get the parameters for the model settings
	 * - Analytics Tracking Code
	 *
	 * @param none
	 *
	 * @return Array $settings - Current settings with main data, list of sections and fields.
	 *
	 * @since 1.1
	 */
	public function get_advanced_settings() {
		$sections = array();

		$sections[] = array(
			'id' => 'seo-misc',
			'label' => __("Misc options", $this->get_text_domain()),
			'callback' => '__return_false', // array($this, 'render_section_information'),
			'fields' => array(
				array(
					'id' => 'analytics-tracking-code',
					'label' => __("Analytics Tracking Code", $this->get_text_domain()),
					'callback' => array($this, 'render_field'),
					'section_id' => 'seo-misc',
					'render-args' => array(
						'id' => 'analytics-tracking-code',
						'label_for' => 'meta-description',
						'description' => __("Paste one or more JavaScript codes given by the analitycs provider.", $this->get_text_domain()),
						'type' => 'textarea',
						'class' => 'large-text',
						'default' => ''
					)
				),
			)
		);

		$option_name = 'seoco_advanced';

		$settings = array(
			'group-name' => 'seo-core-options',
			'option-name' => $option_name,
			'callback' => array($this, 'settings_validate'),
			'tab-slug' => 'advanced',
			'menu-slug' => 'seo-core-options',
			'sections' => $this->set_default_section_fields($sections, $option_name)
		);

		return $settings;
	}

	/**
	 * Get the parameters for the model settings
	 * - Front page
	 * - Singular page
	 * - Category
	 * - Tag
	 * - Taxonomy
	 * - Author
	 * - Date
	 * - Search
	 * - 404
	 * - Paged
	 *
	 * @param none
	 *
	 * @return Array $settings - Current settings with main data, list of sections and fields.
	 *
	 * @since 1.1
	 */
	public function get_model_settings() {
		$params = array(
			array(
				'slug' => 'frontpage',
				'label' => __("Frontpage model", $this->get_text_domain()),
				'description' => true,
				'robots' => true
			),
			array(
				'slug' => 'singular',
				'label' => __("Singular model", $this->get_text_domain()),
				'description' => true,
				'robots' => true
			),
			array(
				'slug' => 'category',
				'label' => __("Category model", $this->get_text_domain()),
				'description' => true,
				'robots' => true
			),
			array(
				'slug' => 'tag',
				'label' => __("Tag model", $this->get_text_domain()),
				'description' => true,
				'robots' => true
			),
			array(
				'slug' => 'taxonomy',
				'label' => __("Taxonomy model", $this->get_text_domain()),
				'description' => true,
				'robots' => true
			),
			array(
				'slug' => 'author',
				'label' => __("Author model", $this->get_text_domain()),
				'description' => true,
				'robots' => true
			),
			array(
				'slug' => 'date',
				'label' => __("Date model", $this->get_text_domain()),
				'description' => true,
				'robots' => true
			),
			array(
				'slug' => 'search',
				'label' => __("Search model", $this->get_text_domain()),
				'description' => false,
				'robots' => false
			),
			array(
				'slug' => 'notfound',
				'label' => __("404 model", $this->get_text_domain()),
				'description' => false,
				'robots' => false
			)
		);

		$sections = array();

		foreach($params as $param)
		{
			$fields = array(
				array(
					'id' => $param['slug'].'-meta-title',
					'label' => __("Title for search engine", $this->get_text_domain()),
					'callback' => array($this, 'render_field'),
					'section_id' => 'seo-'.$param['slug'],
					'render-args' => array(
						'id' => $param['slug'].'-meta-title',
						'label_for' => 'meta-title',
						'description' => __("Most search engines use a maximum of 60 chars for the title.", $this->get_text_domain()),
						'type' => 'text',
						'class' => 'regular-text',
						'default' => ''
					)
				)
			);

			if($param['description'] == true)
			{
				$fields[] = array(
					'id' => $param['slug'].'-meta-description',
					'label' => __("Meta description", $this->get_text_domain()),
					'callback' => array($this, 'render_field'),
					'section_id' => 'seo-'.$param['slug'],
					'render-args' => array(
						'id' => $param['slug'].'-meta-description',
						'label_for' => 'meta-description',
						'description' => __("Most search engines use a maximum of 160 chars for the description.", $this->get_text_domain()),
						'type' => 'textarea',
						'class' => 'large-text',
						'default' => ''
					)
				);
			}

			if($param['robots'] == true)
			{
				$fields[] = array(
					'id' => $param['slug'].'-robots-index',
					'label' => __("Meta Robots Index", $this->get_text_domain()),
					'callback' => array($this, 'render_field'),
					'section_id' => 'seo-'.$param['slug'],
					'render-args' => array(
						'id' => $param['slug'].'-robots-index',
						'description' => '', //__("Select the default behaviour for each new page.", $this->get_text_domain()),
						'type' => 'radio',
						'class' => '',
						'options' => array(
							array('name' => __("Yes", $this->get_text_domain()), 'value' => 'index'),
							array('name' => __("No", $this->get_text_domain()), 'value' => 'noindex')
						),
						'default' => (get_option('blog_public') == 1 ? 'index' : 'noindex')
					)
				);

				$fields[] = array(
					'id' => $param['slug'].'-robots-follow',
					'label' => __("Meta Robots Follow", $this->get_text_domain()),
					'callback' => array($this, 'render_field'),
					'section_id' => 'seo-'.$param['slug'],
					'render-args' => array(
						'id' => $param['slug'].'-robots-follow',
						'description' => '', //__("Select the default behaviour for each new page.", $this->get_text_domain()),
						'type' => 'radio',
						'class' => '',
						'options' => array(
							array('name' => __("Yes", $this->get_text_domain()), 'value' => 'follow'),
							array('name' => __("No", $this->get_text_domain()), 'value' => 'nofollow')
						),
						'default' => 'follow'
					)
				);
			}

			$sections[] = array(
				'id' => 'seo-'.$param['slug'],
				'label' => $param['label'],
				'callback' => '__return_false', // array($this, 'render_section_information'),
				'fields' => $fields
			);
		}
		
		$sections[] = array(
			'id' => 'seo-paged',
			'label' => __("Paged model", $this->get_text_domain()),
			'callback' => '__return_false', // array($this, 'render_section_information'),
			'fields' => array(
				array(
					'id' => 'paged-meta-title',
					'label' => __("Title suffix for paged display", $this->get_text_domain()),
					'callback' => array($this, 'render_field'),
					'section_id' => 'seo-paged',
					'render-args' => array(
						'id' => 'paged-meta-title',
						'label_for' => 'meta-title',
						'description' => __("Text added at the end of the main title for paged display. For instance: Page %pagenum% of %pagetotal%", $this->get_text_domain()),
						'type' => 'text',
						'class' => 'regular-text',
						'default' => ''
					)
				),
				array(
					'id' => 'paged-robots-index',
					'label' => __("Meta Robots Index", $this->get_text_domain()),
					'callback' => array($this, 'render_field'),
					'section_id' => 'seo-paged',
					'render-args' => array(
						'id' => 'paged-robots-index',
						'description' => '', //__("Select the default behaviour for each new page.", $this->get_text_domain()),
						'type' => 'radio',
						'class' => '',
						'options' => array(
							array('name' => __("Yes", $this->get_text_domain()), 'value' => 'index'),
							array('name' => __("No", $this->get_text_domain()), 'value' => 'noindex')
						),
						'default' => 'noindex'
					)
				),
				array(
					'id' => 'paged-robots-follow',
					'label' => __("Meta Robots Follow", $this->get_text_domain()),
					'callback' => array($this, 'render_field'),
					'section_id' => 'seo-paged',
					'render-args' => array(
						'id' => 'paged-robots-follow',
						'description' => __("Override previous Meta Robots settings whether paged pages.", $this->get_text_domain()),
						'type' => 'radio',
						'class' => '',
						'options' => array(
							array('name' => __("Yes", $this->get_text_domain()), 'value' => 'follow'),
							array('name' => __("No", $this->get_text_domain()), 'value' => 'nofollow')
						),
						'default' => 'follow'
					)
				)
			)
		);

		$option_name = 'seoco_models';

		$settings = array(
			'group-name' => 'seo-core-options',
			'option-name' => $option_name,
			'callback' => array($this, 'settings_validate'),
			'tab-slug' => 'models',
			'menu-slug' => 'seo-core-options',
			'sections' => $this->set_default_section_fields($sections, $option_name)
		);

		return $settings;
	}

	/**
	 * Init plugin options to white list our options
	 *
	 * @param none
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function plugin_options_init() {
		// Metaboxes
		$this->metaboxes_init();

		// Settings page
		if(is_null($this->settings) == false) {
			/**
			 * Each argument of register_setting() means as follow:
			 * - A settings group name
			 * - The name of an option to sanitize and save
			 * - A callback function that sanitizes the option's value
			 */
			register_setting(
				$this->settings['group-name'],
				$this->settings['option-name'],
				$this->settings['callback']
			);
			
			// Add settings sections
			foreach($this->settings['sections'] as $section) {
				/**
				 * Each argument of add_settings_section() means as follow:
				 * - Unique identifier for the settings section
				 * - Section title
				 * - Section callback (we don't want anything)
				 * - Menu slug, used to uniquely identify the page, should match $menu_slug from add_theme_page
				 */
				add_settings_section(
					$section['id'],
					$section['label'],
					$section['callback'],
					$this->settings['menu-slug']
				);

				// Add settings field related to section
				foreach($section['fields'] as $field) {
					/**
					 * Each argument of add_setting_fields() means as follow:
					 * - Unique identifier for the field for this section
					 * - Setting field label
					 * - Function that renders the settings field
					 * - Menu slug, used to uniquely identify the page, should match $menu_slug from add_theme_page
					 * - Unique identifier of the settings section
					 * - Additional arguments that are passed to the render function
					 */
					add_settings_field(
						$field['id'],
						$field['label'],
						$field['callback'],
						$this->settings['menu-slug'],
						$field['section_id'],
						$field['render-args']
					);
				}
			}
		}
	}

	/**
	 * Init theme metaboxes called by admin_init action hook.
	 *
	 * @param none
	 *
	 * @return PHP string $html - Generated HTML code.
	 *
	 * @since 1.0
	 */
	public function metaboxes_init() {
		new MetaBox(array(
			'id' => 'seoco_metabox',
			'title' => __("SEO", $this->get_text_domain()),
			'post-types' => get_post_types(array('public' => true), 'objects'),
			'context' => 'normal',
			'priority' => 'high',
			'readonly' => false,
			'fields' => array(
				array(
					'id' => 'meta-title',
					'name' => __("Title for search engine", $this->get_text_domain()),
					'desc' => __("Most search engines use a maximum of 60 chars for the title.", $this->get_text_domain()),
					'type' => 'text',
					'default' => '',
					'class' => 'regular-text',
					'linked' => true
				),
				array(
					'id' => 'meta-description',
					'name' => __("Meta description", $this->get_text_domain()),
					'desc' => __("Most search engines use a maximum of 160 chars for the description.", $this->get_text_domain()),
					'type' => 'textarea',
					'default' => '',
					'class' => 'large-text',
					'linked' => true
				),
				array(
					'id' => 'canonical',
					'name' => __("Canonical", $this->get_text_domain()),
					'desc' => __("The canonical URL that this page should point to. Leave empty to default to permalink.", $this->get_text_domain()),
					'type' => 'text',
					'default' => '',
					'class' => 'large-text',
					'linked' => true
				),
				array(
					'id' => 'robots-index',
					'name' => __("Meta Robots Index", $this->get_text_domain()),
					'type' => 'radio',
					'options' => array(
						array('name' => __("Yes", $this->get_text_domain()), 'value' => 'index'),
						array('name' => __("No", $this->get_text_domain()), 'value' => 'noindex')
					),
					'default' => 'index',
					'linked' => true
				),
				array(
					'id' => 'robots-follow',
					'name' => __("Meta Robots Follow", $this->get_text_domain()),
					'type' => 'radio',
					'options' => array(
						array('name' => __("Yes", $this->get_text_domain()), 'value' => 'follow'),
						array('name' => __("No", $this->get_text_domain()), 'value' => 'nofollow')
					),
					'default' => 'follow',
					'linked' => true
				)
				/* Examples of fields
				array(
					'id' => 'parent_id',
					'name' => __("Related tour", $this->get_text_domain()),
					'desc' => __("Select the tour name to attach the package to.", $this->get_text_domain()),
					'type' => 'select',
					'options' => $option_tours,
					'default' => $default_tour,
					'linked' => false
				),			
				array(
					'name' => __("Title", $this->get_text_domain()),
					'desc' => __("That's usually the package name.", $this->get_text_domain()),
					'id' => 'seoco_title',
					'type' => 'text',
					'default' => ''
				),
				array(
					'name' => 'Textarea',
					'desc' => 'Enter big text here',
					'id' => 'seoco_textarea',
					'type' => 'textarea',
					'default' => 'Default value 2'
				),
				array(
					'name' => 'Select box',
					'id' => 'seoco_select',
					'type' => 'select',
					'options' => array('Option 1', 'Option 2', 'Option 3')
				),
				array(
					'name' => 'Radio',
					'id' => 'seoco_radio',
					'type' => 'radio',
					'options' => array(
						array('name' => 'Name 1', 'value' => 'Value 1'),
						array('name' => 'Name 2', 'value' => 'Value 2')
					)
				),
				array(
					'name' => 'Checkbox',
					'id' => 'seoco_checkbox',
					'type' => 'checkbox',
					'options' => array(
						array('name' => 'Name A'),
						array('name' => 'Name B')
					)
				)
				*/
			)
		));
	}

	/**
	 * Load up the menu page.
	 *
	 * @param none
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function plugin_options_add_page() {
		/**
		 * Each argument of add_options_page() means as follow:
		 * - Name of page
		 * - Label in menu
		 * - Capability required
		 * - Menu slug, used to uniquely identify the page
		 * - Function that renders the options page
		 */
		add_options_page(
			__("SEO", $this->get_text_domain()),
			__("SEO", $this->get_text_domain()),
			'edit_others_posts',
			$this->settings['menu-slug'],
			array($this, 'render_settings_page')
		);
	}

	/**
	 * Render some explanations about that section.
	 *
	 * @param Array $section - The section.
	 *
	 * @return PHP string $html - Generated HTML code.
	 *
	 * @since 1.0
	 */
	public function render_section_information($section) {
		echo '<p>'.__("Global options.", $this->get_text_domain()).'</p>'."\n";
	}

	/**
	 * Render the navigation tabs.
	 *
	 * @param none
	 *
	 * @return PHP string $html - Generated HTML code.
	 *
	 * @since 1.0
	 */
	public function render_page_tabs($current) {
		$html = '';
		$html .= '<h2 class="nav-tab-wrapper">';

		foreach($this->tabs as $id => $tab) {
			$html .= '<a class="nav-tab';
			$html .= ($id == $current ? ' nav-tab-active' : '').'" ';
			$html .= 'href="?page=seo-core-options&tab='.$id.'">'.$tab.'</a>';
		}

		$html .= '</h2>'."\n";
		
		return $html;
	}

	/**
	 * Create the options page.
	 *
	 * @param none
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function render_settings_page() {
		$seoco_output_tab = $this->render_page_tabs($this->settings['tab-slug']);

		require_once(plugin_dir_path(__FILE__).'options-page.php');
		/*
		if($overridden_template = locate_template('options-page.php')) {
			load_template($overridden_template);
		} else {
			load_template(plugin_dir_path(__FILE__).'options-page.php');
		}
		*/
	}

	/**
	 * Create the option field
	 *
	 * @param Array $args - A list of arguments.
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function render_field($args = array()) {
		$html = '';

		switch($args['type']) {
		case 'text':
			$html .= '<input id="'.$this->settings['option-name'].'['.$args['id'].']" ';
			$html .= 'class="'.$args['class'].'" ';
			$html .= 'type="text" ';
			$html .= 'name="'.$this->settings['option-name'].'['.$args['id'].']" ';
			$html .= 'value="'.esc_attr($args['value']).'">'."\n";
			if(empty($args['description']) == false) { $html .= '<p class="description">'.$args['description'].'</p>'."\n"; }
			break;

		case 'textarea':
			$html .= '<textarea id="'.$this->settings['option-name'].'['.$args['id'].']" ';
			$html .= 'class="'.$args['class'].'" ';
			$html .= 'name="'.$this->settings['option-name'].'['.$args['id'].']" ';
			$html .= 'cols="50" rows="3">';
			$html .= esc_attr($args['value']);
			$html .= '</textarea>'."\n";
			if(empty($args['description']) == false) { $html .= '<p class="description">'.$args['description'].'</p>'."\n"; }
			break;

		case 'radio':
			foreach($args['options'] as $option) {
				$html .= '<input type="radio" name="'.$this->settings['option-name'].'['.$args['id'].']" ';
				$html .= 'value="'.$option['value'].'"';
				$html .= ($args['value'] == $option['value'] ? ' checked="checked"' : '').'> '."\n";
				$html .= '<label for="'.$args['id'].'">'.$option['name'].'</label>'."\n";
			}
			if(empty($args['description']) == false) { $html .= '<p class="description">'.$args['description'].'</p>'."\n"; }
			break;
		}

		echo $html;
		/*
		if($overridden_template = locate_template('plugin-options-field.php')) {
			load_template($overridden_template);
		} else {
			load_template(plugin_dir_path(__FILE__).'plugin-options-field.php');
		}
		*/
	}

	/**
	 * Sanitize and validate input. Accepts an array, return a sanitized array.
	 *
	 * @param PHP string $input - Input value to sanitize.
	 *
	 * @return PHP string $input - Modified input value.
	 *
	 * @since 1.0
	 */
	public function settings_validate($input) {
		// Say our text option must be safe text with no HTML tags
		// $input['companyname'] = wp_filter_nohtml_kses($input['companyname'] );

		// Say our text option must be safe text with no HTML tags
		// $input['copyrightyear'] = wp_filter_nohtml_kses($input['copyrightyear'] );

		return $input;
	}

	/**
	 * Determine whether the frontpage displays latest posts.
	 *
	 * @param void
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	function is_frontpage_posts() {
		return (is_home() == true && get_option('show_on_front') == 'posts');
	}

	/**
	 * Determine whether the fontpage displays a static page with a selected page on front.
	 *
	 * @param void
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	function is_frontpage_home() {
		return (is_front_page() == true && get_option('show_on_front') == 'page' && is_page(get_option('page_on_front')) == true);
	}

	/**
	 * Determine whether the fontpage displays a static page.
	 *
	 * @param void
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	function is_fontpage_static() {
		return (is_home() == true && get_option('show_on_front') == 'page');
	}

	/**
	 * Return the meta tag description for a page.
	 *
	 * @param void
	 *
	 * @return PHP string $desc - The queried description.
	 *
	 * @since 1.0
	 */
	public function get_meta_description() {
		global $wp_query;
		$desc = '';

		// @see function wp_title() as WordPress team do to find a single page
		if(is_singular() == true) {
			$post = $wp_query->get_queried_object();
			$metadata = get_post_meta($post->ID, 'seoco_metabox', true);

			// Find SEO meta description (if any)
			if(isset($metadata['meta-description']) == true) { $desc = $metadata['meta-description']; }
			
			// Find the post excerpt (if any)
			if(empty($desc) == true) { $desc = $post->post_excerpt; }

			// Create an abstract from the post content (if description not found)
			if(empty($desc) == true) { $desc = strip_tags($post->post_content); }
		}
		else {
			$metadata = get_option('seoco_models');

			if(is_archive() == true) {
				// Get model description for category, tag, or taxonomy (if any)
				if(is_category() == true) {
					$desc = $this->get_option_meta($metadata, 'category-meta-description', $desc);
				}
				elseif(is_tag() == true) {
					$desc = $this->get_option_meta($metadata, 'tag-meta-description', $desc);
				}
				elseif(is_tax() == true) {
					$desc = $this->get_option_meta($metadata, 'taxonomy-meta-description', $desc);
				}

				// Get customized description from a taxonomy, category or tag included (if any)
				if(is_category() == true || is_tag() == true || is_tax() == true) {
					$term = get_queried_object();
					if(isset($term) == true) {
						// Get meta description whether is found or taxonomy model description by default
						$desc = $this->get_taxonomy_meta('meta-description', $desc);

						// Get native WordPress description whether meta description is not found
						$desc = (empty($desc) == true ? trim(strip_tags(term_description($term->term_id, $term->taxonomy))) : $desc);
					}
				}

				if(is_author() == true) {
					$desc = $this->get_option_meta($metadata, 'author-meta-description', '');
				}

				if(is_date() == true) {
					$desc = $this->get_option_meta($metadata, 'date-meta-description', '');
				}
			}
			else if(is_front_page() == true || is_home() == true) {
				$desc = $this->get_option_meta($metadata, 'frontpage-meta-description', '');
			}
		}

		if(empty($desc) == true) {
			$desc = get_bloginfo('description').'.';
		}

		$desc = trim(strip_tags($desc));

		/**
		 * Long description is cutted at the maximum length of characters.
		 *
		 * @link http://wordpress.org/support/topic/create-excerpt-for-meta-description
		 *
		 * Meta Title up to 70 characters displayed by Google. We can write up to 80 chars inside the tag.
		 * Meta Description up to 155 characters displayed by Google. We can write up to 200-250 chars
		 * inside the tag. To determine the character count of a UTF8 string, we use utf8_decode(),
		 * because that are not in ISO-8859-1 are converted to a single "?".
		 *
		 * @link http://php.net/manual/en/function.strlen.php#45407
		 */
		if(empty($desc) == false && strlen(utf8_decode($desc)) > 200) {
			$desc = substr($desc, 0, 200);
			$desc_words = preg_split('/[\n\r\t ]+/', $desc, -1, PREG_SPLIT_NO_EMPTY);
			array_pop($desc_words);
			$desc = implode(' ', $desc_words).'...';
		}

		return esc_attr($desc);
	}

	/**
	 * Build the translation from a string model that contains custom vars.
	 *
	 * @param	$model String	A string model for translation.
	 * @param	$options Array	Options of some values
	 *
	 * @return	String of model with translated vars
	 *
	 * @since 1.0
	 */
	private function var_translation($model, $options = array()) {
		if(preg_match_all('/%[^%]+?%/', $model, $matches) == true) {
			foreach($matches[0] as $match) {
				switch($match) {
				case '%title%':
					$value = (isset($options['title']) == true ? $options['title'] : '?');
					break;
				case '%sitename%':
					$value = get_bloginfo('name');
					break;
				case '%date%':
					$value = (is_single() == true || is_home() == true || is_page() == true ? get_the_date() : '?');
					break;
				case '%author%':
					$object = get_queried_object();
					$value = (is_author() == true && isset($object) == true ? $object->display_name : '?');
					break;
				case '%taxonomy%':
					$term = get_queried_object();
					if(isset($term) == true) {
						$value = (is_category() == true || is_tag() == true || is_tax() == true ? $term->name : '?');
					}
					break;
				case '%search%':
					$value = (is_search() == true ? strip_tags(get_query_var('s')) : '?');
					break;
				case '%pagenum%':
					global $page;
					$value = 1;//$page;
					break;
				case '%pagetotal%':
					global $paged;
					$value = 2;//$paged;
					break;
				default:
					$value = '?';
					break;
				}

				$model = str_replace($match, $value, $model);
			}
		}

		return $model;
	}

	/**
	 * Get a meta value from the queried taxonomy.
	 *
	 * @param	$property String	Property name.
	 * @param	$property String	Default property value (optional).
	 *
	 * @return	String of the meta value
	 *
	 * @since 1.0
	 */
	public function get_taxonomy_meta($property, $default = '') {
		$value = $default;
		$metadata = get_option('seoco_taxonomy_meta');
		if(isset($metadata) == true) {
			$term = get_queried_object();
			if(isset($term) == true &&
				isset($metadata[$term->taxonomy][$term->term_id]) == true &&
				empty($metadata[$term->taxonomy][$term->term_id][$property]) == false) {
				$value = $metadata[$term->taxonomy][$term->term_id][$property];
			}

			$value = (empty($metadata[$term->taxonomy][$term->term_id][$property]) == true ? $default : $value);
		}

		return $value;
	}

	/**
	 * Get a meta value from an option metadata.
	 *
	 * @param	$metadata Array		Metadata array.
	 * @param	$property String	Property name.
	 * @param	$default String		Default property value (optional).
	 *
	 * @return	String of the meta value
	 *
	 * @since 1.0
	 */
	private function get_option_meta($metadata, $property, $default = '') {
		$value = $default;

		if(isset($metadata[$property]) == true) {
			if(is_null($metadata[$property]) == false && empty($metadata[$property]) == false) {
				$value = $metadata[$property];
			}
		}
		
		return $value;
	}

	/**
	 * Return the HTML title for a page customized by the editor in the admin options, if any.
	 * If none, return the title formatted by the native function wp_title().
	 * This function is a filter hook for wp_title().
	 * A part of this source code is from the function wp_title() in general-template.php
	 *
	 * @param	$title String			Title of the page.
	 * @param	$sep String				How to separate the various items within the page title (optional).
	 * @param	$seplocation String		Direction to display title (optional).
	 *
	 * @return	String of the title
	 *
	 * @since 1.0
	 */
	public function wp_title($title, $sep = '-', $seplocation = 'right') {
		global $wp_query;
		global $post;
 
		$title = trim($title);
		$seotitle = null;

		// Find customized title from a single post or page (if any)
		// if(is_front_page() == false && (is_single() == true || is_home() == true || is_page() == true))
		if(is_singular() == true) {
			$post = $wp_query->get_queried_object();
			$metadata = get_post_meta($post->ID, 'seoco_metabox', true);
			
			if(isset($metadata['meta-title']) == true && empty($metadata['meta-title']) == false) {
				$seotitle = $metadata['meta-title'];
			}
			else {
				$metadata = get_option('seoco_models');
				$seotitle = $this->get_option_meta($metadata, 'singular-meta-title', $title);
			}
		}
		else
		{
			$metadata = get_option('seoco_models');

			if(is_archive() == true) {
				// Get model title for category, tag, or taxonomy (if any)
				if(is_category() == true) {
					$seotitle = $this->get_option_meta($metadata, 'category-meta-title', $title);
				}
				elseif(is_tag() == true) {
					$seotitle = $this->get_option_meta($metadata, 'tag-meta-title', $title);
				}
				elseif(is_tax() == true) {
					$seotitle = $this->get_option_meta($metadata, 'taxonomy-meta-title', $title);
				}

				// Get customized title from a taxonomy, category or tag included (if any)
				if(is_category() == true || is_tag() == true || is_tax() == true) {
					$term = get_queried_object();
					if(isset($term) == true) {
						// Get meta description whether is found or taxonomy model description by default
						$seotitle = $this->get_taxonomy_meta('meta-title', $seotitle);

						// Get native WordPress title whether meta description is not found
						$seotitle = (empty($seotitle) == true ? $title : $seotitle);
					}
				}

				// Find customized title from an author archive (if any)
				if(is_author() == true) {
					$seotitle = $this->get_option_meta($metadata, 'author-meta-title', $title);
				}

				if(is_date() == true) {
					$seotitle = $this->get_option_meta($metadata, 'date-meta-title', $title);
				}
			}
			else if(is_front_page() == true || is_home() == true) {
				$seotitle = $this->get_option_meta($metadata, 'frontpage-meta-title', $title);
			}
			else if(is_search() == true) {
				$seotitle = $this->get_option_meta($metadata, 'search-meta-title', $title);
			}
			else if(is_404() == true) {
				$seotitle = $this->get_option_meta($metadata, 'notfound-meta-title', $title);
			}
		}

		// Sanitize the SEO title
		$seotitle = (is_null($seotitle) == true ? $title : $seotitle);

		// Check for paged pages
		if(is_paged() == true) {
			$seotitle .= sprintf(' %s %s', (empty($sep) == true ? '|' : $sep), $this->get_option_meta(
				$metadata,
				'paged-meta-title',
				__("Page %pagenum% of %pagetotal%", $this->get_text_domain())
			));
		}

		// Find vars to translate
		$seotitle = $this->var_translation($seotitle, array('title' => $title));

		return apply_filters('the_title', $seotitle);
	}

	/**
	 * Build the canonical link for a page customized by the editor in the admin options, if any.
	 * Return canonical link in tag, or null if not found.
	 *
	 * @param none
	 *
	 * @return	String of the canonical link in tag
	 *
	 * @since 1.0
	 */
	public function canonical() {
		$canonical = null;

		if(is_singular() == true) {
			global $post, $wp_query;
			$post = $wp_query->get_queried_object();
			$metadata = get_post_meta($post->ID, 'seoco_metabox', true);
			$canonical = (isset($metadata['canonical']) == true && empty($metadata['canonical']) == false ? $metadata['canonical'] : get_permalink($post->ID));
		}
		else {
			$metadata = get_option('seoco_models');

			if(is_archive() == true) {
				if(is_category() == true || is_tag() == true || is_tax() == true) {
					$term = get_queried_object();
					$canonical = $this->get_taxonomy_meta('canonical', get_term_link($term, $term->taxonomy));
				}
				// else if(function_exists('get_post_type_archive_link') == true && is_post_type_archive() == true) {
					// $canonical = get_post_type_archive_link( get_post_type() );
				// }
				else if(is_author() == true) {
					$canonical = get_author_posts_url(get_query_var('author'), get_query_var('author_name'));
				}
				else if(is_date() == true) {
					if(is_day() == true) {
						$canonical = get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
					}
					else if(is_month() == true) {
						$canonical = get_month_link(get_query_var('year'), get_query_var('monthnum'));
					}
					else if(is_year() == true) {
						$canonical = get_year_link(get_query_var('year'));
					}
				}
			}
			else if(is_search() == true) {
				$canonical = get_search_link();
			}
			else if(is_front_page() == true || is_home() == true) {
				$canonical = home_url('/');
			}
			else if($this->is_fontpage_static() == true) {
				$canonical = home_url('/');
			}
			else if(is_404() == true) {
				$canonical = null;
			}
		}

		return (is_null($canonical) == false ? '<link rel="canonical" href="'.esc_url($canonical).'">'."\n" : '');
	}

	/**
	 * Build the robots directive for a page customized by the editor in the admin options, if any.
	 * Return robots directive in tag, or "index, follow" as default if not found.
	 *
	 * @param void
	 *
	 * @return	String of the robots directive in tag
	 *
	 * @since 1.0
	 */
	public function robots() {
		$robots = array();

		if(is_singular() == true) {
			global $post, $wp_query;
			$post = $wp_query->get_queried_object();
			$metadata = get_post_meta($post->ID, 'seoco_metabox', true);
			$robots['index'] = (isset($metadata['robots-index']) == true ? $metadata['robots-index'] : null);
			$robots['follow'] = (isset($metadata['robots-follow']) == true ? $metadata['robots-follow'] : null);
		}
		else {
			$metadata = get_option('seoco_models');

			if(is_archive() == true) {
				// Get model directive for category, tag, or taxonomy (if any)
				if(is_category() == true) {
					$robots['index'] = $this->get_taxonomy_meta('robots-index', $this->get_option_meta($metadata, 'category-robots-index', 'index'));
					$robots['follow'] = $this->get_taxonomy_meta('robots-follow', $this->get_option_meta($metadata, 'category-robots-follow', 'follow'));
				}
				else if(is_tag() == true) {
					$robots['index'] = $this->get_taxonomy_meta('robots-index', $this->get_option_meta($metadata, 'tag-robots-index', 'index'));
					$robots['follow'] = $this->get_taxonomy_meta('robots-follow', $this->get_option_meta($metadata, 'tag-robots-follow', 'follow'));
				}
				else if(is_tax() == true) {
					$robots['index'] = $this->get_taxonomy_meta('robots-index', $this->get_option_meta($metadata, 'taxonomy-robots-index', 'index'));
					$robots['follow'] = $this->get_taxonomy_meta('robots-follow', $this->get_option_meta($metadata, 'taxonomy-robots-follow', 'follow'));
				}
				else if(is_author() == true) {
					$robots['index'] = $this->get_option_meta($metadata, 'author-robots-index', 'index');
					$robots['follow'] = $this->get_option_meta($metadata, 'author-robots-follow', 'follow');
				}
				else if(is_date() == true) {
					$robots['index'] = $this->get_option_meta($metadata, 'date-robots-index', 'index');
					$robots['follow'] = $this->get_option_meta($metadata, 'date-robots-follow', 'follow');
				}
			}
			else if(is_search() == true) {
				$robots['index'] = 'noindex';
				$robots['follow'] = 'follow';
			}
			else if(is_front_page() == true || $this->is_fontpage_static() == true) {
				$robots['index'] = $this->get_option_meta($metadata, 'frontpage-robots-index', 'index');
				$robots['follow'] = $this->get_option_meta($metadata, 'frontpage-robots-follow', 'follow');
			}
			else if(is_404() == true) {
				$robots['index'] = 'noindex';
				$robots['follow'] = 'nofollow';
			}
		}

		// Override previous settings for paged pages
		if(is_paged() == true) {
			$robots['index'] = $this->get_option_meta($metadata, 'paged-robots-index', 'noindex');
			$robots['follow'] = $this->get_option_meta($metadata, 'paged-robots-follow', 'follow');
		}

		// Sanitize the directives
		$robots['index'] = (isset($robots['index']) == true && is_null($robots['index']) == false ? esc_attr($robots['index']) : 'index');
		$robots['follow'] = (isset($robots['follow']) == true && is_null($robots['follow']) == false ? esc_attr($robots['follow']) : 'follow');

		// Send directive "noindex, nofollow" whether the blog publicy option is set to private.
		if(get_option('blog_public') == 1) {
			$directive = sprintf('<meta name="robots" content="%s, %s">', $robots['index'], $robots['follow'])."\n";
		}
		else {
			$directive = '<meta name="robots" content="noindex, nofollow">'."\n";
		}

		return $directive;
	}

	/**
	 * Return some auto generated meta tags to the site header.
	 * This function is a filter hook for wp_head().
	 *
	 * @param none
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function wp_head() {
		// Output the description meta tag
		echo '<meta name="description" content="'.$this->get_meta_description().'">'."\n";
		
		// Output rel canonical link
		echo $this->canonical();
		echo $this->robots();
		// $this->author();
	}

	/**
	 * Return some auto generated data to the site footer.
	 * This function is a filter hook for wp_head().
	 *
	 * @param none
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function wp_footer() {
		// Output analytics tracking code
		if(get_option('blog_public') == 1) {
			$metadata = get_option('seoco_advanced');

			echo '<script type="text/javascript">'."\n";
			echo $this->get_option_meta($metadata, 'analytics-tracking-code')."\n";
			echo '</script>'."\n";
		}
	}
}
