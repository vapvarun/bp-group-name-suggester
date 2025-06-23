<?php
/**
 * Plugin Name: BuddyPress Group Name Suggester
 * Plugin URI: https://wbcomdesigns.com/
 * Description: Advanced randomizer generator names for BuddyPress groups. Generate creative, topic-based group names and descriptions instantly. This powerful name randomizer generator helps users create unique group names with our intelligent suggestion system. Perfect for community builders who need a reliable group name generator with built-in randomizer features.
 * Version: 1.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * License: GPL v2 or later
 * Text Domain: bp-group-name-suggester
 * Tags: buddypress, group names, randomizer, generator, names, random name generator, group name ideas, name suggester
 */

// Prevent direct access
if (!defined('ABSPATH')) {
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
     * Constructor
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if BuddyPress is active
        if (!function_exists('buddypress')) {
            add_action('admin_notices', array($this, 'buddypress_required_notice'));
            return;
        }
        
        // Add hooks
        add_action('bp_before_group_details_creation_step', array($this, 'add_name_suggester_section'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_suggest_group_names', array($this, 'ajax_suggest_group_names'));
        add_action('wp_ajax_nopriv_suggest_group_names', array($this, 'ajax_suggest_group_names'));
        
        // Add custom AJAX endpoints for extensibility
        add_action('wp_ajax_bp_gns_custom_suggestions', array($this, 'ajax_custom_suggestions'));
        add_action('wp_ajax_nopriv_bp_gns_custom_suggestions', array($this, 'ajax_custom_suggestions'));
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize plugin hooks for extensibility
     */
    private function init_hooks() {
        // Action hooks
        do_action('bp_gns_init');
        
        // Allow developers to add custom suggestion providers
        add_filter('bp_gns_suggestion_providers', array($this, 'register_default_provider'), 10);
    }
    
    /**
     * Register default suggestion provider
     */
    public function register_default_provider($providers) {
        $providers['default'] = array(
            'name' => __('Default Generator', 'bp-group-name-suggester'),
            'callback' => array($this, 'generate_group_suggestion'),
            'priority' => 10
        );
        
        return $providers;
    }
    
    /**
     * Show admin notice if BuddyPress is not active
     */
    public function buddypress_required_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('BuddyPress Group Name Suggester requires BuddyPress to be installed and activated.', 'bp-group-name-suggester'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add the name suggester section to the group creation form
     */
    public function add_name_suggester_section() {
        if (bp_is_group_creation_step('group-details')) {
            // Allow filtering of topics
            $topics = $this->get_available_topics();
            
            // Allow custom template override
            $template = apply_filters('bp_gns_template_path', plugin_dir_path(__FILE__) . 'templates/suggester-form.php');
            
            if (file_exists($template)) {
                include $template;
            } else {
                // Default inline template
                ?>
                <div class="bp-group-name-suggester">
                    <?php do_action('bp_gns_before_suggester'); ?>
                    
                    <h3><?php echo apply_filters('bp_gns_heading_text', __('Need Help Naming Your Group?', 'bp-group-name-suggester')); ?></h3>
                    
                    <div class="topic-selector">
                        <label><?php _e('Select your group topic:', 'bp-group-name-suggester'); ?></label>
                        <div class="topic-buttons">
                            <?php foreach ($topics as $key => $topic): ?>
                                <button type="button" class="topic-btn" data-topic="<?php echo esc_attr($key); ?>">
                                    <span class="topic-icon"><?php echo esc_html($topic['icon']); ?></span> 
                                    <?php echo esc_html($topic['label']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php do_action('bp_gns_after_topic_selector'); ?>
                    
                    <div class="suggestions-container" style="display: none;">
                        <h4><?php _e('Suggested Names for Your Group:', 'bp-group-name-suggester'); ?></h4>
                        <div class="suggestions-grid"></div>
                        <div class="suggester-actions">
                            <button type="button" id="get-more-suggestions" class="button" style="display: none;">
                                <?php _e('Get More Suggestions', 'bp-group-name-suggester'); ?>
                            </button>
                            <span class="spinner" style="display: none;"></span>
                        </div>
                    </div>
                    
                    <?php do_action('bp_gns_after_suggester'); ?>
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
            'technology' => array('icon' => '</>', 'label' => __('Technology', 'bp-group-name-suggester')),
            'arts' => array('icon' => '◈', 'label' => __('Arts & Creative', 'bp-group-name-suggester')),
            'sports' => array('icon' => '◉', 'label' => __('Sports & Fitness', 'bp-group-name-suggester')),
            'music' => array('icon' => '♪', 'label' => __('Music', 'bp-group-name-suggester')),
            'education' => array('icon' => '◊', 'label' => __('Education & Learning', 'bp-group-name-suggester')),
            'business' => array('icon' => '▣', 'label' => __('Business & Professional', 'bp-group-name-suggester')),
            'health' => array('icon' => '+', 'label' => __('Health & Wellness', 'bp-group-name-suggester')),
            'gaming' => array('icon' => '▲', 'label' => __('Gaming', 'bp-group-name-suggester')),
            'travel' => array('icon' => '➤', 'label' => __('Travel & Adventure', 'bp-group-name-suggester')),
            'food' => array('icon' => '◆', 'label' => __('Food & Cooking', 'bp-group-name-suggester')),
            'nature' => array('icon' => '❋', 'label' => __('Nature & Environment', 'bp-group-name-suggester')),
            'social' => array('icon' => '○', 'label' => __('Social & Community', 'bp-group-name-suggester')),
            'hobbies' => array('icon' => '✦', 'label' => __('Hobbies & Interests', 'bp-group-name-suggester')),
            'random' => array('icon' => '※', 'label' => __('Random Mix', 'bp-group-name-suggester'))
        );
        
        // Allow plugins to add/modify topics
        return apply_filters('bp_gns_available_topics', $default_topics);
    }
    
    /**
     * Enqueue necessary scripts
     */
    public function enqueue_scripts() {
        if (bp_is_group_create()) {
            wp_enqueue_script(
                'bp-group-name-suggester',
                plugin_dir_url(__FILE__) . 'assets/js/script.js',
                array('jquery'),
                self::VERSION,
                true
            );
            
            // Localize script with extensible data
            $localize_data = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bp_group_name_suggester_nonce'),
                'providers' => $this->get_active_providers(),
                'settings' => $this->get_plugin_settings()
            );
            
            wp_localize_script('bp-group-name-suggester', 'bpGroupNameSuggester', 
                apply_filters('bp_gns_localize_data', $localize_data)
            );
            
            wp_enqueue_style(
                'bp-group-name-suggester',
                plugin_dir_url(__FILE__) . 'assets/css/style.css',
                array(),
                self::VERSION
            );
        }
    }
    
    /**
     * Get active suggestion providers
     */
    private function get_active_providers() {
        $providers = apply_filters('bp_gns_suggestion_providers', array());
        
        $active = array();
        foreach ($providers as $key => $provider) {
            $active[$key] = array(
                'name' => $provider['name'],
                'active' => isset($provider['active']) ? $provider['active'] : true
            );
        }
        
        return $active;
    }
    
    /**
     * Get plugin settings
     */
    private function get_plugin_settings() {
        $defaults = array(
            'suggestions_per_load' => 20,
            'more_suggestions_count' => 10,
            'enable_ai' => false,
            'ai_provider' => '',
            'cache_suggestions' => true,
            'cache_duration' => 3600
        );
        
        return apply_filters('bp_gns_settings', $defaults);
    }
    
    /**
     * Handle AJAX request to suggest group names
     */
    public function ajax_suggest_group_names() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bp_group_name_suggester_nonce')) {
            wp_die('Security check failed');
        }
        
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : 'random';
        $count = isset($_POST['count']) ? intval($_POST['count']) : 20;
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : 'default';
        
        // Allow custom suggestion generation
        $suggestions = apply_filters('bp_gns_generate_suggestions', null, $topic, $count, $provider);
        
        if (is_null($suggestions)) {
            // Use default generation
            $suggestions = $this->generate_topic_based_suggestions($topic, $count);
        }
        
        // Allow filtering of final suggestions
        $suggestions = apply_filters('bp_gns_suggestions_output', $suggestions, $topic);
        
        wp_send_json_success($suggestions);
    }
    
    /**
     * Handle custom AJAX suggestions endpoint
     */
    public function ajax_custom_suggestions() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bp_group_name_suggester_nonce')) {
            wp_die('Security check failed');
        }
        
        // Allow complete custom handling
        do_action('bp_gns_custom_ajax_handler', $_POST);
        
        // Default response
        wp_send_json_error('No custom handler registered');
    }
    
    /**
     * Generate suggestions based on selected topic
     */
    private function generate_topic_based_suggestions($topic, $count = 20) {
        $suggestions = array();
        $used_names = array();
        
        for ($i = 0; $i < $count; $i++) {
            $attempt = 0;
            do {
                $group_data = $this->generate_group_suggestion($topic);
                $attempt++;
            } while (in_array($group_data['name'], $used_names) && $attempt < 10);
            
            if (!in_array($group_data['name'], $used_names)) {
                $suggestions[] = $group_data;
                $used_names[] = $group_data['name'];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Generate group name and description based on topic
     */
    private function generate_group_suggestion($topic) {
        // Get topic data with filter
        $topic_data = apply_filters('bp_gns_topic_data', $this->get_topic_data($topic), $topic);
        
        // General prefixes that work with any topic
        $general_prefixes = array(
            'Amazing', 'Creative', 'Dynamic', 'Enthusiastic', 'Innovative',
            'Passionate', 'Active', 'Dedicated', 'Friendly', 'United'
        );
        
        // Mix topic-specific and general prefixes
        $prefixes = array_merge($topic_data['prefixes'], $general_prefixes);
        
        // Select random elements
        $prefix = $prefixes[array_rand($prefixes)];
        $keyword = $topic_data['keywords'][array_rand($topic_data['keywords'])];
        $noun = $topic_data['nouns'][array_rand($topic_data['nouns'])];
        $activity = $topic_data['activities'][array_rand($topic_data['activities'])];
        $purpose = $topic_data['purposes'][array_rand($topic_data['purposes'])];
        
        // Generate name with filter
        $name = apply_filters('bp_gns_generate_name', $this->create_name_pattern($prefix, $keyword, $noun), $prefix, $keyword, $noun);
        
        // Generate description with filter
        $description = apply_filters('bp_gns_generate_description', 
            $this->create_description($keyword, $activity, $purpose), 
            $keyword, $activity, $purpose
        );
        
        return array(
            'name' => $name,
            'description' => $description,
            'topic' => $topic,
            'metadata' => apply_filters('bp_gns_suggestion_metadata', array(), $topic)
        );
    }
    
    /**
     * Create name pattern
     */
    private function create_name_pattern($prefix, $keyword, $noun) {
        $patterns = apply_filters('bp_gns_name_patterns', array(
            $prefix . ' ' . $keyword . ' ' . $noun,
            $keyword . ' ' . $noun,
            'The ' . $keyword . ' ' . $noun,
            $noun . ' of ' . $keyword,
            $prefix . ' ' . $keyword . ' Community',
            $keyword . ' ' . $noun . ' Club'
        ), $prefix, $keyword, $noun);
        
        return $patterns[array_rand($patterns)];
    }
    
    /**
     * Create description
     */
    private function create_description($keyword, $activity, $purpose) {
        $templates = apply_filters('bp_gns_description_templates', array(
            "A community for {$keyword} enthusiasts who love {$activity}. We {$purpose} while exploring everything related to {$keyword}.",
            "Welcome to our {$keyword} group! We're passionate about {$activity} and dedicated to helping members {$purpose}.",
            "Join fellow {$keyword} lovers as we {$purpose}. Perfect for anyone interested in {$activity}!",
            "This is the place for {$keyword} fans to connect, share, and grow. Our mission is to {$purpose} through {$activity}.",
            "Bringing together people who share a passion for {$keyword}. We focus on {$activity} to {$purpose}."
        ), $keyword, $activity, $purpose);
        
        return $templates[array_rand($templates)];
    }
    
    /**
     * Get topic-specific vocabulary
     */
    private function get_topic_data($topic) {
        $default_topics = array(
            'technology' => array(
                'prefixes' => array('Digital', 'Tech', 'Cyber', 'Smart', 'Future'),
                'keywords' => array('Coding', 'Programming', 'AI', 'Web', 'Software', 'Hardware', 'Innovation', 'Development', 'Digital', 'Tech'),
                'nouns' => array('Developers', 'Innovators', 'Coders', 'Hackers', 'Engineers', 'Creators', 'Builders', 'Pioneers', 'Experts', 'Masters'),
                'activities' => array('coding together', 'building projects', 'sharing tech tips', 'exploring new technologies', 'developing solutions'),
                'purposes' => array('advance our technical skills', 'create innovative solutions', 'stay updated with tech trends', 'build amazing projects', 'share knowledge and expertise')
            ),
            // ... other topics remain the same ...
            'random' => array(
                'prefixes' => array('Amazing', 'Creative', 'Dynamic', 'Fantastic', 'United'),
                'keywords' => array('Adventure', 'Discovery', 'Connection', 'Innovation', 'Community', 'Experience', 'Journey', 'Vision', 'Dream', 'Future'),
                'nouns' => array('Explorers', 'Creators', 'Dreamers', 'Builders', 'Pioneers', 'Visionaries', 'Champions', 'Leaders', 'Innovators', 'Friends'),
                'activities' => array('exploring together', 'sharing experiences', 'building community', 'creating magic', 'making connections'),
                'purposes' => array('achieve great things', 'build something amazing', 'connect and grow', 'make dreams reality', 'create positive change')
            )
        );
        
        // Allow custom topic data
        $topics = apply_filters('bp_gns_topic_vocabulary', $default_topics);
        
        return isset($topics[$topic]) ? $topics[$topic] : $topics['random'];
    }
}

// Initialize the plugin
new BP_Group_Name_Suggester();

// API Functions for developers
if (!function_exists('bp_gns_add_topic')) {
    /**
     * Add a new topic
     */
    function bp_gns_add_topic($key, $label, $icon, $vocabulary) {
        add_filter('bp_gns_available_topics', function($topics) use ($key, $label, $icon) {
            $topics[$key] = array('icon' => $icon, 'label' => $label);
            return $topics;
        });
        
        add_filter('bp_gns_topic_vocabulary', function($vocab) use ($key, $vocabulary) {
            $vocab[$key] = $vocabulary;
            return $vocab;
        });
    }
}

if (!function_exists('bp_gns_register_ai_provider')) {
    /**
     * Register an AI provider
     */
    function bp_gns_register_ai_provider($key, $name, $callback) {
        add_filter('bp_gns_suggestion_providers', function($providers) use ($key, $name, $callback) {
            $providers[$key] = array(
                'name' => $name,
                'callback' => $callback,
                'priority' => 20
            );
            return $providers;
        });
    }
}