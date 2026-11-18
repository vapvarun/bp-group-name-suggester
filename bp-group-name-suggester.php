<?php
/**
 * Plugin Name: BuddyPress Group Name Suggester
 * Plugin URI: https://wbcomdesigns.com/
 * Description: Advanced randomizer generator names for BuddyPress groups. Generate creative, topic-based group names and descriptions instantly. This powerful name randomizer generator helps users create unique group names with our intelligent suggestion system. Perfect for community builders who need a reliable group name generator with built-in randomizer features.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * License: GPL v2 or later
 * Text Domain: bp-group-name-suggester
 * Domain Path: /languages
 * Tags: buddypress, group names, randomizer, generator, names, random name generator, group name ideas, name suggester
 *
 * @package BP_Group_Name_Suggester
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class BP_Group_Name_Suggester {

	/**
	 * Plugin version
	 */
	const VERSION = '1.0.0';

	/**
	 * Upper bound for suggestions returned by one request.
	 */
	const MAX_SUGGESTIONS = 50;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Priority 20: after BuddyPress has set up its active components on plugins_loaded:10.
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
		// On init, not earlier: WordPress 6.7+ flags translations loaded before init.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load translations from the plugin's languages folder.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'bp-group-name-suggester', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		// Check if BuddyPress is active.
		if ( ! function_exists( 'buddypress' ) ) {
			add_action( 'admin_notices', array( $this, 'buddypress_required_notice' ) );
			return;
		}

		// The suggester only lives on the Create Group screen.
		if ( ! bp_is_active( 'groups' ) ) {
			add_action( 'admin_notices', array( $this, 'groups_component_required_notice' ) );
			return;
		}

		// Add hooks.
		add_action( 'bp_before_group_details_creation_step', array( $this, 'add_name_suggester_section' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Logged-in only: the suggester lives on the Create Group screen, which guests never reach.
		add_action( 'wp_ajax_suggest_group_names', array( $this, 'ajax_suggest_group_names' ) );

		// Add custom AJAX endpoints for extensibility.
		add_action( 'wp_ajax_bp_gns_custom_suggestions', array( $this, 'ajax_custom_suggestions' ) );

		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize plugin hooks for extensibility
	 */
	private function init_hooks() {
		// Action hooks.
		do_action( 'bp_gns_init' );

		// Allow developers to add custom suggestion providers.
		add_filter( 'bp_gns_suggestion_providers', array( $this, 'register_default_provider' ), 10 );
	}

	/**
	 * Register default suggestion provider
	 *
	 * @param array $providers Registered suggestion providers.
	 * @return array
	 */
	public function register_default_provider( $providers ) {
		$providers['default'] = array(
			'name'     => __( 'Default Generator', 'bp-group-name-suggester' ),
			'callback' => array( $this, 'generate_group_suggestion' ),
			'priority' => 10,
		);

		return $providers;
	}

	/**
	 * Show admin notice if BuddyPress is not active
	 */
	public function buddypress_required_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'BuddyPress Group Name Suggester requires BuddyPress to be installed and activated.', 'bp-group-name-suggester' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice if the BuddyPress Groups component is not active.
	 */
	public function groups_component_required_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$components_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) );
		?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %s: link to the BuddyPress Components settings screen. */
					esc_html__( 'BuddyPress Group Name Suggester needs the BuddyPress Groups component. Turn it on in %s.', 'bp-group-name-suggester' ),
					'<a href="' . esc_url( $components_url ) . '">' . esc_html__( 'Settings > BuddyPress > Components', 'bp-group-name-suggester' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add the name suggester section to the group creation form
	 */
	public function add_name_suggester_section() {
		if ( bp_is_group_creation_step( 'group-details' ) ) {
			// Allow filtering of topics.
			$topics = $this->get_available_topics();

			// Allow custom template override.
			$template = apply_filters( 'bp_gns_template_path', plugin_dir_path( __FILE__ ) . 'templates/suggester-form.php' );

			if ( file_exists( $template ) ) {
				include $template;
			} else {
				// Default inline template.
				?>
				<div class="bp-group-name-suggester">
					<?php do_action( 'bp_gns_before_suggester' ); ?>
					
					<h3><?php echo esc_html( apply_filters( 'bp_gns_heading_text', __( 'Need Help Naming Your Group?', 'bp-group-name-suggester' ) ) ); ?></h3>
					
					<div class="topic-selector">
						<p id="bp-gns-topic-label" class="topic-selector-label"><?php esc_html_e( 'Select your group topic:', 'bp-group-name-suggester' ); ?></p>
						<div class="topic-buttons" role="group" aria-labelledby="bp-gns-topic-label">
							<?php foreach ( $topics as $key => $topic ) : ?>
								<button type="button" class="topic-btn" data-topic="<?php echo esc_attr( $key ); ?>" aria-pressed="false">
									<span class="topic-icon" aria-hidden="true"><?php echo esc_html( $topic['icon'] ); ?></span>
									<?php echo esc_html( $topic['label'] ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
					
					<?php do_action( 'bp_gns_after_topic_selector' ); ?>
					
					<div class="suggestions-container" style="display: none;">
						<h4><?php esc_html_e( 'Suggested Names for Your Group:', 'bp-group-name-suggester' ); ?></h4>
						<div class="bp-gns-status" role="status" aria-live="polite"></div>
						<div class="suggestions-grid"></div>
						<div class="suggester-actions">
							<button type="button" id="get-more-suggestions" class="button" style="display: none;">
								<?php esc_html_e( 'Get More Suggestions', 'bp-group-name-suggester' ); ?>
							</button>
							<span class="spinner" style="display: none;"></span>
						</div>
					</div>
					
					<?php do_action( 'bp_gns_after_suggester' ); ?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Get available topics with filter
	 */
	public function get_available_topics() {
		$default_topics = array(
			'technology' => array(
				'icon'  => '</>',
				'label' => __( 'Technology', 'bp-group-name-suggester' ),
			),
			'arts'       => array(
				'icon'  => '◈',
				'label' => __( 'Arts & Creative', 'bp-group-name-suggester' ),
			),
			'sports'     => array(
				'icon'  => '◉',
				'label' => __( 'Sports & Fitness', 'bp-group-name-suggester' ),
			),
			'music'      => array(
				'icon'  => '♪',
				'label' => __( 'Music', 'bp-group-name-suggester' ),
			),
			'education'  => array(
				'icon'  => '◊',
				'label' => __( 'Education & Learning', 'bp-group-name-suggester' ),
			),
			'business'   => array(
				'icon'  => '▣',
				'label' => __( 'Business & Professional', 'bp-group-name-suggester' ),
			),
			'health'     => array(
				'icon'  => '+',
				'label' => __( 'Health & Wellness', 'bp-group-name-suggester' ),
			),
			'gaming'     => array(
				'icon'  => '▲',
				'label' => __( 'Gaming', 'bp-group-name-suggester' ),
			),
			'travel'     => array(
				'icon'  => '➤',
				'label' => __( 'Travel & Adventure', 'bp-group-name-suggester' ),
			),
			'food'       => array(
				'icon'  => '◆',
				'label' => __( 'Food & Cooking', 'bp-group-name-suggester' ),
			),
			'nature'     => array(
				'icon'  => '❋',
				'label' => __( 'Nature & Environment', 'bp-group-name-suggester' ),
			),
			'social'     => array(
				'icon'  => '○',
				'label' => __( 'Social & Community', 'bp-group-name-suggester' ),
			),
			'hobbies'    => array(
				'icon'  => '✦',
				'label' => __( 'Hobbies & Interests', 'bp-group-name-suggester' ),
			),
			'random'     => array(
				'icon'  => '※',
				'label' => __( 'Random Mix', 'bp-group-name-suggester' ),
			),
		);

		// Allow plugins to add/modify topics.
		return apply_filters( 'bp_gns_available_topics', $default_topics );
	}

	/**
	 * Enqueue necessary scripts
	 */
	public function enqueue_scripts() {
		if ( bp_is_group_create() ) {
			wp_enqueue_script(
				'bp-group-name-suggester',
				plugin_dir_url( __FILE__ ) . 'assets/js/script.js',
				array( 'jquery' ),
				self::VERSION,
				true
			);

			// Localize script with extensible data.
			$localize_data = array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'bp_group_name_suggester_nonce' ),
				'providers' => $this->get_active_providers(),
				'settings'  => $this->get_plugin_settings(),
				'i18n'      => array(
					'loading' => __( 'Generating suggestions...', 'bp-group-name-suggester' ),
					'error'   => __( 'Suggestions could not be loaded. Please try again.', 'bp-group-name-suggester' ),
					'empty'   => __( 'No suggestions found for this topic. Try another topic.', 'bp-group-name-suggester' ),
					'retry'   => __( 'Try again', 'bp-group-name-suggester' ),
					'useName' => __( 'Use This Name', 'bp-group-name-suggester' ),
				),
			);

			wp_localize_script(
				'bp-group-name-suggester',
				'bpGroupNameSuggester',
				apply_filters( 'bp_gns_localize_data', $localize_data )
			);

			wp_enqueue_style(
				'bp-group-name-suggester',
				plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
				array(),
				self::VERSION
			);
		}
	}

	/**
	 * Get active suggestion providers
	 */
	private function get_active_providers() {
		$providers = apply_filters( 'bp_gns_suggestion_providers', array() );

		$active = array();
		foreach ( $providers as $key => $provider ) {
			$active[ $key ] = array(
				'name'   => $provider['name'],
				'active' => isset( $provider['active'] ) ? $provider['active'] : true,
			);
		}

		return $active;
	}

	/**
	 * Get plugin settings
	 */
	private function get_plugin_settings() {
		$defaults = array(
			'suggestions_per_load'   => 20,
			'more_suggestions_count' => 10,
			'enable_ai'              => false,
			'ai_provider'            => '',
			'cache_suggestions'      => true,
			'cache_duration'         => 3600,
		);

		return apply_filters( 'bp_gns_settings', $defaults );
	}

	/**
	 * Handle AJAX request to suggest group names
	 */
	public function ajax_suggest_group_names() {
		if ( ! check_ajax_referer( 'bp_group_name_suggester_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session has expired. Reload the page and try again.', 'bp-group-name-suggester' ) ), 403 );
		}

		$topic    = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : 'random';
		$count    = isset( $_POST['count'] ) ? absint( wp_unslash( $_POST['count'] ) ) : 20;
		$count    = max( 1, min( self::MAX_SUGGESTIONS, $count ) );
		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : 'default';

		// Allow custom suggestion generation.
		$suggestions = apply_filters( 'bp_gns_generate_suggestions', null, $topic, $count, $provider );

		if ( is_null( $suggestions ) ) {
			// Use default generation.
			$suggestions = $this->generate_topic_based_suggestions( $topic, $count );
		}

		// Allow filtering of final suggestions.
		$suggestions = apply_filters( 'bp_gns_suggestions_output', $suggestions, $topic );

		wp_send_json_success( $suggestions );
	}

	/**
	 * Handle custom AJAX suggestions endpoint
	 */
	public function ajax_custom_suggestions() {
		if ( ! check_ajax_referer( 'bp_group_name_suggester_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session has expired. Reload the page and try again.', 'bp-group-name-suggester' ) ), 403 );
		}

		// Allow complete custom handling.
		do_action( 'bp_gns_custom_ajax_handler', $_POST );

		// Default response.
		wp_send_json_error( 'No custom handler registered' );
	}

	/**
	 * Generate suggestions based on selected topic
	 *
	 * @param string $topic Topic key.
	 * @param int    $count Number of suggestions to build.
	 * @return array
	 */
	private function generate_topic_based_suggestions( $topic, $count = 20 ) {
		$suggestions = array();
		$used_names  = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$attempt = 0;
			do {
				$group_data = $this->generate_group_suggestion( $topic );
				++$attempt;
			} while ( in_array( $group_data['name'], $used_names, true ) && $attempt < 10 );

			if ( ! in_array( $group_data['name'], $used_names, true ) ) {
				$suggestions[] = $group_data;
				$used_names[]  = $group_data['name'];
			}
		}

		return $suggestions;
	}

	/**
	 * Generate group name and description based on topic
	 *
	 * @param string $topic Topic key.
	 * @return array
	 */
	private function generate_group_suggestion( $topic ) {
		// Get topic data with filter.
		$topic_data = apply_filters( 'bp_gns_topic_data', $this->get_topic_data( $topic ), $topic );

		// General prefixes that work with any topic.
		$general_prefixes = array(
			'Amazing',
			'Creative',
			'Dynamic',
			'Enthusiastic',
			'Innovative',
			'Passionate',
			'Active',
			'Dedicated',
			'Friendly',
			'United',
		);

		// Mix topic-specific and general prefixes.
		$prefixes = array_merge( $topic_data['prefixes'], $general_prefixes );

		// Select random elements.
		$prefix   = $prefixes[ array_rand( $prefixes ) ];
		$keyword  = $topic_data['keywords'][ array_rand( $topic_data['keywords'] ) ];
		$noun     = $topic_data['nouns'][ array_rand( $topic_data['nouns'] ) ];
		$activity = $topic_data['activities'][ array_rand( $topic_data['activities'] ) ];
		$purpose  = $topic_data['purposes'][ array_rand( $topic_data['purposes'] ) ];

		// Generate name with filter.
		$name = apply_filters( 'bp_gns_generate_name', $this->create_name_pattern( $prefix, $keyword, $noun ), $prefix, $keyword, $noun );

		// Generate description with filter.
		$description = apply_filters(
			'bp_gns_generate_description',
			$this->create_description( $keyword, $activity, $purpose ),
			$keyword,
			$activity,
			$purpose
		);

		return array(
			'name'        => $name,
			'description' => $description,
			'topic'       => $topic,
			'metadata'    => apply_filters( 'bp_gns_suggestion_metadata', array(), $topic ),
		);
	}

	/**
	 * Create name pattern
	 *
	 * @param string $prefix  Name prefix.
	 * @param string $keyword Topic keyword.
	 * @param string $noun    Group noun.
	 * @return string
	 */
	private function create_name_pattern( $prefix, $keyword, $noun ) {
		$patterns = apply_filters(
			'bp_gns_name_patterns',
			array(
				$prefix . ' ' . $keyword . ' ' . $noun,
				$keyword . ' ' . $noun,
				'The ' . $keyword . ' ' . $noun,
				$noun . ' of ' . $keyword,
				$prefix . ' ' . $keyword . ' Community',
				$keyword . ' ' . $noun . ' Club',
			),
			$prefix,
			$keyword,
			$noun
		);

		return $patterns[ array_rand( $patterns ) ];
	}

	/**
	 * Create description
	 *
	 * @param string $keyword  Topic keyword.
	 * @param string $activity Group activity.
	 * @param string $purpose  Group purpose.
	 * @return string
	 */
	private function create_description( $keyword, $activity, $purpose ) {
		$templates = apply_filters(
			'bp_gns_description_templates',
			array(
				"A community for {$keyword} enthusiasts who love {$activity}. We {$purpose} while exploring everything related to {$keyword}.",
				"Welcome to our {$keyword} group! We're passionate about {$activity} and dedicated to helping members {$purpose}.",
				"Join fellow {$keyword} lovers as we {$purpose}. Perfect for anyone interested in {$activity}!",
				"This is the place for {$keyword} fans to connect, share, and grow. Our mission is to {$purpose} through {$activity}.",
				"Bringing together people who share a passion for {$keyword}. We focus on {$activity} to {$purpose}.",
			),
			$keyword,
			$activity,
			$purpose
		);

		return $templates[ array_rand( $templates ) ];
	}

	/**
	 * Get topic-specific vocabulary
	 *
	 * @param string $topic Topic key.
	 * @return array
	 */
	private function get_topic_data( $topic ) {
		$default_topics = array(
			'technology' => array(
				'prefixes'   => array( 'Digital', 'Tech', 'Cyber', 'Smart', 'Future' ),
				'keywords'   => array( 'Coding', 'Programming', 'AI', 'Web', 'Software', 'Hardware', 'Innovation', 'Development', 'Digital', 'Tech' ),
				'nouns'      => array( 'Developers', 'Innovators', 'Coders', 'Hackers', 'Engineers', 'Creators', 'Builders', 'Pioneers', 'Experts', 'Masters' ),
				'activities' => array( 'coding together', 'building projects', 'sharing tech tips', 'exploring new technologies', 'developing solutions' ),
				'purposes'   => array( 'advance our technical skills', 'create innovative solutions', 'stay updated with tech trends', 'build amazing projects', 'share knowledge and expertise' ),
			),
			'arts'       => array(
				'prefixes'   => array( 'Artistic', 'Inspired', 'Colorful', 'Imaginative', 'Expressive' ),
				'keywords'   => array( 'Art', 'Painting', 'Sketching', 'Design', 'Photography', 'Illustration', 'Crafts', 'Pottery', 'Sculpture', 'Watercolor' ),
				'nouns'      => array( 'Artists', 'Painters', 'Makers', 'Designers', 'Creators', 'Sketchers', 'Crafters', 'Visionaries', 'Studio', 'Collective' ),
				'activities' => array( 'sharing our latest work', 'sketching together', 'trading creative feedback', 'exploring new art techniques', 'visiting galleries and exhibitions' ),
				'purposes'   => array( 'grow as artists', 'find fresh inspiration', 'showcase our creations', 'learn new mediums', 'support each other\'s creative journey' ),
			),
			'sports'     => array(
				'prefixes'   => array( 'Athletic', 'Fearless', 'Unstoppable', 'Strong', 'Victorious' ),
				'keywords'   => array( 'Running', 'Cycling', 'Football', 'Basketball', 'Fitness', 'Training', 'Yoga', 'Hiking', 'Tennis', 'Swimming' ),
				'nouns'      => array( 'Athletes', 'Runners', 'Champions', 'Team', 'Squad', 'Crew', 'Warriors', 'Riders', 'Trainers', 'League' ),
				'activities' => array( 'training together', 'tracking our progress', 'joining local races and matches', 'sharing workout plans', 'cheering each other on' ),
				'purposes'   => array( 'reach new personal bests', 'stay active and motivated', 'build a stronger team', 'prepare for our next event', 'make fitness fun' ),
			),
			'music'      => array(
				'prefixes'   => array( 'Harmonic', 'Rhythmic', 'Melodic', 'Acoustic', 'Electric' ),
				'keywords'   => array( 'Music', 'Guitar', 'Piano', 'Jazz', 'Rock', 'Hip Hop', 'Choir', 'Songwriting', 'Drumming', 'Vinyl' ),
				'nouns'      => array( 'Musicians', 'Players', 'Band', 'Ensemble', 'Singers', 'Jammers', 'Collective', 'Producers', 'Listeners', 'Beat Makers' ),
				'activities' => array( 'jamming together', 'sharing new tracks', 'swapping playlists', 'practicing our instruments', 'writing songs' ),
				'purposes'   => array( 'grow as musicians', 'discover new sounds', 'perform together', 'learn from each other', 'keep live music alive' ),
			),
			'education'  => array(
				'prefixes'   => array( 'Curious', 'Wise', 'Bright', 'Scholarly', 'Studious' ),
				'keywords'   => array( 'Study', 'Learning', 'Science', 'History', 'Math', 'Language', 'Reading', 'Research', 'Homework', 'Exam Prep' ),
				'nouns'      => array( 'Learners', 'Scholars', 'Students', 'Study Circle', 'Thinkers', 'Mentors', 'Tutors', 'Bookworms', 'Academy', 'Classmates' ),
				'activities' => array( 'studying together', 'sharing notes and resources', 'reviewing for exams', 'discussing big ideas', 'reading and reflecting' ),
				'purposes'   => array( 'ace our exams', 'learn something new every week', 'help each other understand tough topics', 'build lifelong learning habits', 'grow our knowledge together' ),
			),
			'business'   => array(
				'prefixes'   => array( 'Professional', 'Strategic', 'Ambitious', 'Driven', 'Savvy' ),
				'keywords'   => array( 'Business', 'Startup', 'Marketing', 'Leadership', 'Sales', 'Finance', 'Entrepreneur', 'Freelance', 'Networking', 'Career' ),
				'nouns'      => array( 'Founders', 'Leaders', 'Professionals', 'Network', 'Partners', 'Mentors', 'Builders', 'Collective', 'Alliance', 'Circle' ),
				'activities' => array( 'sharing growth strategies', 'networking with peers', 'reviewing business ideas', 'swapping industry insights', 'mentoring new founders' ),
				'purposes'   => array( 'grow our businesses', 'advance our careers', 'open new opportunities', 'learn from real experience', 'build lasting professional connections' ),
			),
			'health'     => array(
				'prefixes'   => array( 'Radiant', 'Mindful', 'Balanced', 'Vital', 'Thriving' ),
				'keywords'   => array( 'Wellness', 'Mindfulness', 'Nutrition', 'Meditation', 'Self-Care', 'Healthy Living', 'Mental Health', 'Recovery', 'Sleep', 'Wellbeing' ),
				'nouns'      => array( 'Circle', 'Community', 'Collective', 'Seekers', 'Companions', 'Supporters', 'Tribe', 'Allies', 'Friends', 'Network' ),
				'activities' => array( 'sharing healthy habits', 'meditating together', 'swapping nutrition tips', 'checking in on each other', 'setting wellness goals' ),
				'purposes'   => array( 'feel our best every day', 'build healthier routines', 'support each other\'s wellbeing', 'find calm and balance', 'stay accountable to our goals' ),
			),
			'gaming'     => array(
				'prefixes'   => array( 'Epic', 'Legendary', 'Elite', 'Pixel', 'Retro' ),
				'keywords'   => array( 'Gaming', 'Esports', 'Console', 'PC Gaming', 'RPG', 'Strategy', 'Board Game', 'Speedrun', 'Co-op', 'Indie Game' ),
				'nouns'      => array( 'Gamers', 'Players', 'Guild', 'Clan', 'Raiders', 'Squad', 'Legends', 'Party', 'Heroes', 'Alliance' ),
				'activities' => array( 'playing together online', 'planning raids and tournaments', 'sharing tips and builds', 'streaming our sessions', 'trying new releases' ),
				'purposes'   => array( 'level up our skills', 'win more matches together', 'find teammates for every game', 'celebrate great plays', 'have fun and play fair' ),
			),
			'travel'     => array(
				'prefixes'   => array( 'Wandering', 'Global', 'Adventurous', 'Roaming', 'Intrepid' ),
				'keywords'   => array( 'Travel', 'Adventure', 'Backpacking', 'Road Trip', 'Wanderlust', 'Exploration', 'Camping', 'Expedition', 'City Break', 'Globetrotting' ),
				'nouns'      => array( 'Travelers', 'Explorers', 'Nomads', 'Wanderers', 'Adventurers', 'Globetrotters', 'Voyagers', 'Backpackers', 'Trekkers', 'Journeyers' ),
				'activities' => array( 'planning trips together', 'sharing travel stories', 'swapping destination tips', 'exploring new places', 'meeting up on the road' ),
				'purposes'   => array( 'see more of the world', 'travel smarter and safer', 'find travel companions', 'discover hidden gems', 'turn trip ideas into real journeys' ),
			),
			'food'       => array(
				'prefixes'   => array( 'Delicious', 'Savory', 'Tasty', 'Homemade', 'Gourmet' ),
				'keywords'   => array( 'Cooking', 'Baking', 'Recipe', 'Foodie', 'Vegan', 'Grill', 'Kitchen', 'Street Food', 'Pastry', 'Home Chef' ),
				'nouns'      => array( 'Cooks', 'Chefs', 'Bakers', 'Foodies', 'Kitchen Crew', 'Tasters', 'Gourmets', 'Grill Masters', 'Supper Club', 'Food Lovers' ),
				'activities' => array( 'sharing favorite recipes', 'cooking together', 'trying new cuisines', 'swapping kitchen tips', 'hosting potluck dinners' ),
				'purposes'   => array( 'cook with confidence', 'discover new flavors', 'master tricky recipes', 'share great meals', 'celebrate good food together' ),
			),
			'nature'     => array(
				'prefixes'   => array( 'Green', 'Wild', 'Sustainable', 'Earthy', 'Evergreen' ),
				'keywords'   => array( 'Nature', 'Gardening', 'Wildlife', 'Conservation', 'Birdwatching', 'Climate', 'Outdoors', 'Forest', 'Ocean', 'Eco' ),
				'nouns'      => array( 'Guardians', 'Gardeners', 'Stewards', 'Explorers', 'Rangers', 'Naturalists', 'Trailblazers', 'Keepers', 'Growers', 'Friends' ),
				'activities' => array( 'exploring local trails', 'tending community gardens', 'spotting wildlife', 'organizing clean-up days', 'sharing eco-friendly tips' ),
				'purposes'   => array( 'protect our planet', 'live more sustainably', 'connect with the outdoors', 'restore local habitats', 'inspire greener habits' ),
			),
			'social'     => array(
				'prefixes'   => array( 'Friendly', 'Welcoming', 'Neighborly', 'Together', 'Kind' ),
				'keywords'   => array( 'Community', 'Neighborhood', 'Volunteer', 'Meetup', 'Friendship', 'Local', 'Social', 'Charity', 'Book Club', 'Hangout' ),
				'nouns'      => array( 'Neighbors', 'Friends', 'Volunteers', 'Circle', 'Collective', 'Network', 'Crew', 'Gathering', 'Hub', 'Club' ),
				'activities' => array( 'meeting up regularly', 'organizing local events', 'volunteering together', 'welcoming new members', 'sharing community news' ),
				'purposes'   => array( 'bring people together', 'make a difference locally', 'build lasting friendships', 'support our neighbors', 'create a welcoming community' ),
			),
			'hobbies'    => array(
				'prefixes'   => array( 'Handy', 'Curious', 'Crafty', 'Devoted', 'Weekend' ),
				'keywords'   => array( 'Hobby', 'DIY', 'Knitting', 'Woodworking', 'Photography', 'Collecting', 'Model Building', 'Origami', 'Puzzle', 'Chess' ),
				'nouns'      => array( 'Hobbyists', 'Makers', 'Tinkerers', 'Collectors', 'Crafters', 'Enthusiasts', 'Club', 'Workshop', 'Guild', 'Circle' ),
				'activities' => array( 'working on projects together', 'sharing our latest builds', 'swapping tips and tools', 'hosting weekend workshops', 'showing off our collections' ),
				'purposes'   => array( 'get better at what we love', 'learn new skills', 'finish more projects', 'meet people who share our hobbies', 'turn pastimes into passions' ),
			),
			'random'     => array(
				'prefixes'   => array( 'Amazing', 'Creative', 'Dynamic', 'Fantastic', 'United' ),
				'keywords'   => array( 'Adventure', 'Discovery', 'Connection', 'Innovation', 'Community', 'Experience', 'Journey', 'Vision', 'Dream', 'Future' ),
				'nouns'      => array( 'Explorers', 'Creators', 'Dreamers', 'Builders', 'Pioneers', 'Visionaries', 'Champions', 'Leaders', 'Innovators', 'Friends' ),
				'activities' => array( 'exploring together', 'sharing experiences', 'building community', 'creating magic', 'making connections' ),
				'purposes'   => array( 'achieve great things', 'build something amazing', 'connect and grow', 'make dreams reality', 'create positive change' ),
			),
		);

		// Allow custom topic data.
		$topics = apply_filters( 'bp_gns_topic_vocabulary', $default_topics );

		return isset( $topics[ $topic ] ) ? $topics[ $topic ] : $topics['random'];
	}
}

// Initialize the plugin.
new BP_Group_Name_Suggester();

// API Functions for developers.
if ( ! function_exists( 'bp_gns_add_topic' ) ) {
	/**
	 * Add a new topic
	 *
	 * @param string $key        Topic key.
	 * @param string $label      Topic label.
	 * @param string $icon       Topic icon.
	 * @param array  $vocabulary Topic vocabulary (prefixes, keywords, nouns, activities, purposes).
	 */
	function bp_gns_add_topic( $key, $label, $icon, $vocabulary ) {
		add_filter(
			'bp_gns_available_topics',
			function ( $topics ) use ( $key, $label, $icon ) {
				$topics[ $key ] = array(
					'icon'  => $icon,
					'label' => $label,
				);
				return $topics;
			}
		);

		add_filter(
			'bp_gns_topic_vocabulary',
			function ( $vocab ) use ( $key, $vocabulary ) {
				$vocab[ $key ] = $vocabulary;
				return $vocab;
			}
		);
	}
}

if ( ! function_exists( 'bp_gns_register_ai_provider' ) ) {
	/**
	 * Register an AI provider
	 *
	 * @param string   $key      Provider key.
	 * @param string   $name     Provider name.
	 * @param callable $callback Suggestion callback.
	 */
	function bp_gns_register_ai_provider( $key, $name, $callback ) {
		add_filter(
			'bp_gns_suggestion_providers',
			function ( $providers ) use ( $key, $name, $callback ) {
				$providers[ $key ] = array(
					'name'     => $name,
					'callback' => $callback,
					'priority' => 20,
				);
				return $providers;
			}
		);
	}
}