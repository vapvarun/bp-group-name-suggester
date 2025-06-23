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
            ?>
            <div class="bp-group-name-suggester">
                <h3><?php _e('Need Help Naming Your Group?', 'bp-group-name-suggester'); ?></h3>
                
                <div class="topic-selector">
                    <label><?php _e('Select your group topic:', 'bp-group-name-suggester'); ?></label>
                    <div class="topic-buttons">
                        <button type="button" class="topic-btn" data-topic="technology">🖥️ Technology</button>
                        <button type="button" class="topic-btn" data-topic="arts">🎨 Arts & Creative</button>
                        <button type="button" class="topic-btn" data-topic="sports">⚽ Sports & Fitness</button>
                        <button type="button" class="topic-btn" data-topic="music">🎵 Music</button>
                        <button type="button" class="topic-btn" data-topic="education">📚 Education & Learning</button>
                        <button type="button" class="topic-btn" data-topic="business">💼 Business & Professional</button>
                        <button type="button" class="topic-btn" data-topic="health">💚 Health & Wellness</button>
                        <button type="button" class="topic-btn" data-topic="gaming">🎮 Gaming</button>
                        <button type="button" class="topic-btn" data-topic="travel">✈️ Travel & Adventure</button>
                        <button type="button" class="topic-btn" data-topic="food">🍽️ Food & Cooking</button>
                        <button type="button" class="topic-btn" data-topic="nature">🌿 Nature & Environment</button>
                        <button type="button" class="topic-btn" data-topic="social">👥 Social & Community</button>
                        <button type="button" class="topic-btn" data-topic="hobbies">🎯 Hobbies & Interests</button>
                        <button type="button" class="topic-btn" data-topic="random">🎲 Random Mix</button>
                    </div>
                </div>
                
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
            </div>
            <?php
        }
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
                '1.0.0',
                true
            );
            
            wp_localize_script('bp-group-name-suggester', 'bpGroupNameSuggester', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bp_group_name_suggester_nonce')
            ));
            
            wp_enqueue_style(
                'bp-group-name-suggester',
                plugin_dir_url(__FILE__) . 'assets/css/style.css',
                array(),
                '1.0.0'
            );
        }
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
        
        $suggestions = $this->generate_topic_based_suggestions($topic, $count);
        
        wp_send_json_success($suggestions);
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
        // Topic-specific word lists
        $topic_data = $this->get_topic_data($topic);
        
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
        
        // Generate name variations
        $name_patterns = array(
            $prefix . ' ' . $keyword . ' ' . $noun,
            $keyword . ' ' . $noun,
            'The ' . $keyword . ' ' . $noun,
            $noun . ' of ' . $keyword,
            $prefix . ' ' . $keyword . ' Community',
            $keyword . ' ' . $noun . ' Club'
        );
        
        $name = $name_patterns[array_rand($name_patterns)];
        
        // Generate description
        $description_templates = array(
            "A community for {$keyword} enthusiasts who love {$activity}. We {$purpose} while exploring everything related to {$keyword}.",
            "Welcome to our {$keyword} group! We're passionate about {$activity} and dedicated to helping members {$purpose}.",
            "Join fellow {$keyword} lovers as we {$purpose}. Perfect for anyone interested in {$activity}!",
            "This is the place for {$keyword} fans to connect, share, and grow. Our mission is to {$purpose} through {$activity}.",
            "Bringing together people who share a passion for {$keyword}. We focus on {$activity} to {$purpose}."
        );
        
        $description = $description_templates[array_rand($description_templates)];
        
        return array(
            'name' => $name,
            'description' => $description,
            'topic' => $topic
        );
    }
    
    /**
     * Get topic-specific vocabulary
     */
    private function get_topic_data($topic) {
        $topics = array(
            'technology' => array(
                'prefixes' => array('Digital', 'Tech', 'Cyber', 'Smart', 'Future'),
                'keywords' => array('Coding', 'Programming', 'AI', 'Web', 'Software', 'Hardware', 'Innovation', 'Development', 'Digital', 'Tech'),
                'nouns' => array('Developers', 'Innovators', 'Coders', 'Hackers', 'Engineers', 'Creators', 'Builders', 'Pioneers', 'Experts', 'Masters'),
                'activities' => array('coding together', 'building projects', 'sharing tech tips', 'exploring new technologies', 'developing solutions'),
                'purposes' => array('advance our technical skills', 'create innovative solutions', 'stay updated with tech trends', 'build amazing projects', 'share knowledge and expertise')
            ),
            'arts' => array(
                'prefixes' => array('Creative', 'Artistic', 'Inspired', 'Imaginative', 'Expressive'),
                'keywords' => array('Art', 'Design', 'Creative', 'Visual', 'Artistic', 'Craft', 'Studio', 'Gallery', 'Canvas', 'Palette'),
                'nouns' => array('Artists', 'Creators', 'Designers', 'Makers', 'Visionaries', 'Craftsmen', 'Painters', 'Sculptors', 'Creatives', 'Artisans'),
                'activities' => array('creating art', 'sharing techniques', 'showcasing work', 'exploring creativity', 'collaborating on projects'),
                'purposes' => array('express our creativity', 'inspire each other', 'develop artistic skills', 'showcase our talents', 'build an artistic community')
            ),
            'sports' => array(
                'prefixes' => array('Athletic', 'Active', 'Fit', 'Strong', 'Champion'),
                'keywords' => array('Sports', 'Fitness', 'Athletic', 'Training', 'Exercise', 'Workout', 'Game', 'Team', 'Competition', 'Performance'),
                'nouns' => array('Athletes', 'Players', 'Champions', 'Competitors', 'Trainers', 'Enthusiasts', 'Warriors', 'Legends', 'Squad', 'Team'),
                'activities' => array('training together', 'competing', 'staying fit', 'playing sports', 'achieving goals'),
                'purposes' => array('improve our performance', 'stay motivated', 'reach fitness goals', 'compete and have fun', 'build team spirit')
            ),
            'music' => array(
                'prefixes' => array('Melodic', 'Harmonic', 'Rhythmic', 'Musical', 'Acoustic'),
                'keywords' => array('Music', 'Sound', 'Melody', 'Rhythm', 'Beat', 'Harmony', 'Audio', 'Song', 'Tune', 'Jamming'),
                'nouns' => array('Musicians', 'Artists', 'Composers', 'Performers', 'Producers', 'Listeners', 'Band', 'Ensemble', 'Collective', 'Crew'),
                'activities' => array('making music', 'sharing songs', 'jamming together', 'discussing music', 'performing'),
                'purposes' => array('create beautiful music', 'share our passion', 'collaborate on projects', 'discover new sounds', 'grow as musicians')
            ),
            'education' => array(
                'prefixes' => array('Academic', 'Scholarly', 'Learning', 'Educational', 'Knowledge'),
                'keywords' => array('Learning', 'Study', 'Education', 'Knowledge', 'Academic', 'Research', 'Teaching', 'Scholar', 'Wisdom', 'Discovery'),
                'nouns' => array('Scholars', 'Students', 'Learners', 'Educators', 'Researchers', 'Academics', 'Teachers', 'Mentors', 'Thinkers', 'Minds'),
                'activities' => array('learning together', 'sharing knowledge', 'studying', 'researching topics', 'teaching each other'),
                'purposes' => array('expand our knowledge', 'achieve academic success', 'support each other\'s learning', 'explore new subjects', 'grow intellectually')
            ),
            'business' => array(
                'prefixes' => array('Professional', 'Entrepreneurial', 'Strategic', 'Corporate', 'Business'),
                'keywords' => array('Business', 'Entrepreneur', 'Startup', 'Professional', 'Corporate', 'Marketing', 'Finance', 'Strategy', 'Innovation', 'Growth'),
                'nouns' => array('Entrepreneurs', 'Professionals', 'Leaders', 'Innovators', 'Executives', 'Founders', 'Partners', 'Networkers', 'Strategists', 'Moguls'),
                'activities' => array('networking', 'sharing strategies', 'building businesses', 'discussing trends', 'collaborating'),
                'purposes' => array('grow our businesses', 'share expertise', 'build professional networks', 'achieve success', 'innovate together')
            ),
            'health' => array(
                'prefixes' => array('Healthy', 'Wellness', 'Holistic', 'Mindful', 'Balanced'),
                'keywords' => array('Health', 'Wellness', 'Fitness', 'Nutrition', 'Mindfulness', 'Healing', 'Vitality', 'Balance', 'Lifestyle', 'Wellbeing'),
                'nouns' => array('Warriors', 'Advocates', 'Enthusiasts', 'Practitioners', 'Coaches', 'Healers', 'Champions', 'Supporters', 'Community', 'Tribe'),
                'activities' => array('living healthy', 'sharing tips', 'supporting wellness', 'practicing mindfulness', 'improving health'),
                'purposes' => array('achieve optimal health', 'support each other\'s journey', 'share wellness wisdom', 'live balanced lives', 'inspire healthy habits')
            ),
            'gaming' => array(
                'prefixes' => array('Epic', 'Legendary', 'Ultimate', 'Master', 'Elite'),
                'keywords' => array('Gaming', 'Gamer', 'Play', 'Quest', 'Battle', 'Adventure', 'Strategy', 'Victory', 'Challenge', 'Level'),
                'nouns' => array('Gamers', 'Players', 'Champions', 'Warriors', 'Legends', 'Squad', 'Guild', 'Clan', 'Alliance', 'Team'),
                'activities' => array('gaming together', 'conquering challenges', 'sharing strategies', 'competing', 'leveling up'),
                'purposes' => array('dominate the game', 'have epic adventures', 'build our skills', 'create legendary moments', 'form unbeatable teams')
            ),
            'travel' => array(
                'prefixes' => array('Global', 'Wandering', 'Explorer', 'Adventure', 'Journey'),
                'keywords' => array('Travel', 'Adventure', 'Journey', 'Explore', 'Wanderlust', 'Discovery', 'Voyage', 'Trip', 'Destination', 'World'),
                'nouns' => array('Travelers', 'Explorers', 'Adventurers', 'Wanderers', 'Nomads', 'Voyagers', 'Tourists', 'Backpackers', 'Globetrotters', 'Pioneers'),
                'activities' => array('exploring destinations', 'sharing travel stories', 'planning adventures', 'discovering places', 'traveling together'),
                'purposes' => array('explore the world', 'share travel experiences', 'discover hidden gems', 'create memories', 'inspire wanderlust')
            ),
            'food' => array(
                'prefixes' => array('Culinary', 'Gourmet', 'Delicious', 'Savory', 'Tasty'),
                'keywords' => array('Food', 'Cooking', 'Culinary', 'Recipe', 'Cuisine', 'Gourmet', 'Flavor', 'Dish', 'Kitchen', 'Chef'),
                'nouns' => array('Foodies', 'Chefs', 'Cooks', 'Gourmets', 'Enthusiasts', 'Connoisseurs', 'Bakers', 'Masters', 'Artists', 'Lovers'),
                'activities' => array('cooking together', 'sharing recipes', 'exploring cuisines', 'tasting dishes', 'creating meals'),
                'purposes' => array('master culinary arts', 'share delicious recipes', 'explore world cuisines', 'create amazing dishes', 'celebrate food culture')
            ),
            'nature' => array(
                'prefixes' => array('Green', 'Eco', 'Natural', 'Wild', 'Environmental'),
                'keywords' => array('Nature', 'Environment', 'Outdoor', 'Wildlife', 'Eco', 'Green', 'Conservation', 'Earth', 'Forest', 'Ocean'),
                'nouns' => array('Conservationists', 'Naturalists', 'Explorers', 'Advocates', 'Rangers', 'Guardians', 'Warriors', 'Protectors', 'Enthusiasts', 'Stewards'),
                'activities' => array('protecting nature', 'exploring outdoors', 'conservation efforts', 'hiking together', 'studying wildlife'),
                'purposes' => array('protect our planet', 'explore natural wonders', 'promote sustainability', 'connect with nature', 'make a difference')
            ),
            'social' => array(
                'prefixes' => array('Community', 'Social', 'Connected', 'Friendly', 'United'),
                'keywords' => array('Community', 'Social', 'Connection', 'Friendship', 'Network', 'Together', 'Unity', 'Support', 'Bond', 'Circle'),
                'nouns' => array('Friends', 'Community', 'Network', 'Circle', 'Collective', 'Group', 'Society', 'Family', 'Tribe', 'Squad'),
                'activities' => array('connecting people', 'building friendships', 'supporting each other', 'organizing events', 'creating bonds'),
                'purposes' => array('build strong connections', 'create lasting friendships', 'support our community', 'make a difference together', 'foster unity')
            ),
            'hobbies' => array(
                'prefixes' => array('Passionate', 'Dedicated', 'Enthusiastic', 'Hobby', 'Interest'),
                'keywords' => array('Hobby', 'Interest', 'Passion', 'Activity', 'Craft', 'Collection', 'DIY', 'Project', 'Fun', 'Leisure'),
                'nouns' => array('Enthusiasts', 'Hobbyists', 'Collectors', 'Makers', 'Crafters', 'Fans', 'Devotees', 'Aficionados', 'Practitioners', 'Lovers'),
                'activities' => array('pursuing hobbies', 'sharing interests', 'working on projects', 'collecting items', 'crafting together'),
                'purposes' => array('enjoy our hobbies', 'share our passion', 'learn new skills', 'connect with others', 'have fun together')
            ),
            'random' => array(
                'prefixes' => array('Amazing', 'Creative', 'Dynamic', 'Fantastic', 'United'),
                'keywords' => array('Adventure', 'Discovery', 'Connection', 'Innovation', 'Community', 'Experience', 'Journey', 'Vision', 'Dream', 'Future'),
                'nouns' => array('Explorers', 'Creators', 'Dreamers', 'Builders', 'Pioneers', 'Visionaries', 'Champions', 'Leaders', 'Innovators', 'Friends'),
                'activities' => array('exploring together', 'sharing experiences', 'building community', 'creating magic', 'making connections'),
                'purposes' => array('achieve great things', 'build something amazing', 'connect and grow', 'make dreams reality', 'create positive change')
            )
        );
        
        return isset($topics[$topic]) ? $topics[$topic] : $topics['random'];
    }
}

// Initialize the plugin
new BP_Group_Name_Suggester();