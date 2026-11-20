=== BuddyPress Group Name Suggester ===
Contributors: wbcomdesigns
Donate link: https://wbcomdesigns.com/donate/
Tags: buddypress, group names, randomizer, generator, names, random name generator, group name ideas, name suggester, buddypress groups, community
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced randomizer generator names for BuddyPress groups. Generate creative, topic-based group names instantly with our intelligent suggestion system.

== Description ==

The **BuddyPress Group Name Suggester** is a powerful randomizer generator names plugin that helps users create unique and meaningful group names for their BuddyPress communities. This intelligent name randomizer generator provides topic-based suggestions, making it easier than ever to find the perfect name for any group.

= Key Features =

* **Topic-Based Suggestions**: Choose from 14 different categories including Technology, Arts, Sports, Music, Education, Business, and more
* **Instant Name Generation**: Get 20+ creative group name suggestions with matching descriptions instantly
* **Smart Randomizer**: Our advanced randomizer generator names algorithm ensures unique and relevant suggestions every time
* **One-Click Usage**: Simply click on any suggestion to auto-fill your group creation form
* **Load More Options**: Not satisfied? Load additional suggestions with a single click
* **Beautiful UI**: Clean, modern interface that integrates seamlessly with BuddyPress
* **Mobile Responsive**: Works perfectly on all devices and screen sizes
* **No Configuration Required**: Works out of the box with zero setup

= Perfect For =

* Community administrators looking for a group name generator
* Users who need inspiration for creative group names
* BuddyPress sites wanting to enhance their group creation process
* Anyone seeking a reliable randomizer generator names solution

= How It Works =

1. Navigate to the BuddyPress group creation page
2. Select your group's topic from the available categories
3. Instantly receive 20+ tailored name suggestions
4. Click "Use This Name" to auto-fill the form
5. Get more suggestions anytime with the "Get More Suggestions" button

= Why Choose Our Randomizer Generator Names Plugin? =

Unlike basic name generators, our plugin uses intelligent algorithms to create contextually relevant suggestions based on your selected topic. Each suggestion includes both a creative name and a matching description, saving you time and inspiring better community engagement.

= Support =

For support, feature requests, or bug reports, please visit our [support forum](https://wbcomdesigns.com/support/) or create an issue on [GitHub](https://github.com/wbcomdesigns/bp-group-name-suggester).

= Credits =

Developed by [Wbcom Designs](https://wbcomdesigns.com/), experts in BuddyPress solutions and community plugins.

== Installation ==

= From WordPress Dashboard =

1. Navigate to 'Plugins > Add New'
2. Search for 'BuddyPress Group Name Suggester'
3. Click 'Install Now' and then 'Activate'

= Manual Installation =

1. Download the plugin zip file
2. Navigate to 'Plugins > Add New > Upload Plugin'
3. Select the downloaded file and click 'Install Now'
4. Activate the plugin through the 'Plugins' menu

= Requirements =

* WordPress 5.0 or higher
* BuddyPress 5.0 or higher
* PHP 8.1 or higher

== Frequently Asked Questions ==

= Does this plugin require BuddyPress? =

Yes, BuddyPress must be installed and activated for this plugin to work.

= How many name suggestions does it generate? =

The plugin initially generates 20 suggestions based on your selected topic. You can load 10 more suggestions at a time by clicking the "Get More Suggestions" button.

= Can I customize the suggestion categories? =

The current version includes 14 predefined categories. We're planning to add custom category support in future updates.

= Is this plugin compatible with the latest WordPress? =

Yes, the plugin is tested with WordPress 6.5 and is regularly updated for compatibility.

= Does it work with BuddyPress theme? =

Yes, it works with any BuddyPress-compatible theme including BP Nouveau and BP Legacy templates.

= Can I use the generated names for private groups? =

Absolutely! The name suggester works for all group types - public, private, and hidden groups.

= Is there a limit to how many suggestions I can generate? =

Each topic has about 2,000 name combinations. "Get More Suggestions" keeps adding names you have not seen yet, and when a topic runs out the panel says so and suggests another topic.

== Screenshots ==

1. Topic selection interface - Choose from 14 different categories
2. Generated name suggestions grid - View multiple suggestions at once
3. Selected suggestion auto-filling the group creation form
4. Mobile responsive view of the name suggester
5. Integration with BuddyPress group creation page

== Changelog ==

= 1.0.1 - November 2026 =

* Improve  - Screen readers hear how many suggestions loaded, how many were added and when a name is filled in.
* Improve  - Each Use This Name button is tied to its suggestion, so a screen reader reads which name it applies.
* Improve  - Choosing a name moves keyboard focus to the Group Name field.
* Improve  - Suggestions and scrolling skip animation when the visitor prefers reduced motion.
* Improve  - The number of suggestions per load follows the bp_gns_settings filter.
* Improve  - Use This Name, Get More and Try again are styled by the plugin, so they look the same on BuddyPress and BuddyBoss.
* Fix      - Use This Name and Try again show a visible keyboard focus ring under BuddyPress and Reign.
* Fix      - A keyboard-focused topic chip no longer looks selected.
* Fix      - Keyboard focus stays on Get More Suggestions after more names load.
* Fix      - After a session-expired error the Try again button is hidden, since only a page reload can recover.
* Fix      - The BuddyPress required notice shows to administrators only.
* Fix      - Topic chips keep their outlined look and a visible selected state under the BuddyPress theme button styles.
* Fix      - A slow response for a previous topic no longer replaces the suggestions for the topic just chosen.
* Fix      - Keyboard focus stays on the topic list after Try again instead of being lost.
* Fix      - The session-expired message shows when a suggestion request fails.
* Fix      - Get More Suggestions never repeats a name already on screen.
* Fix      - When a topic runs out of new names the panel says so and hides Get More Suggestions.
* Fix      - Logging out in another tab shows the session-expired message and hides Try again and Get More Suggestions.
* Fix      - Removed AI, provider and caching settings that never did anything.
* Security - Unknown topics are rejected instead of being echoed into every suggestion.
* Dev      - Passes PHP lint on 8.1 to 8.5 with no deprecations from the plugin.
* Dev      - Removed bp_gns_register_ai_provider(), the bp_gns_suggestion_providers filter, the bp_gns_custom_suggestions AJAX action with its bp_gns_custom_ajax_handler hook, and the enable_ai, ai_provider, cache_suggestions and cache_duration keys of bp_gns_settings. None of them changed the suggestions.
* Dev      - The fourth argument of bp_gns_generate_suggestions is now the list of names already shown, replacing the unused provider key; the AJAX response data is now an object with suggestions and exhausted.

= 1.0.0 =
* Initial release
* 14 topic categories for targeted suggestions
* Intelligent randomizer generator names algorithm
* 20+ instant suggestions per topic
* One-click name selection
* Load more suggestions feature
* Mobile responsive design
* BuddyPress 12.0 compatibility

== Upgrade Notice ==

= 1.0.1 =
Accessibility and styling fixes for the group name suggester.

= 1.0.0 =
Initial release of BuddyPress Group Name Suggester. Install to enhance your group creation experience with intelligent name suggestions.

== Additional Information ==

= Contributing =

We welcome contributions! Visit our [GitHub repository](https://github.com/wbcomdesigns/bp-group-name-suggester) to submit pull requests or report issues.

= Privacy =

This plugin does not collect or store any personal data. All name generation happens locally on your server.

= Languages =

Currently available in English. Translation-ready with full internationalization support.