jQuery(document).ready(function($) {
    
    var selectedTopic = null;
    var cardCount = 0;
    var i18n = bpGroupNameSuggester.i18n;
    var settings = bpGroupNameSuggester.settings || {};
    var initialCount = parseInt(settings.suggestions_per_load, 10) || 20;
    var moreCount = parseInt(settings.more_suggestions_count, 10) || 10;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    // Handle topic selection
    $('.topic-btn').on('click', function() {
        var button = $(this);
        selectedTopic = button.data('topic');
        
        // Visual feedback
        $('.topic-btn').removeClass('active').attr('aria-pressed', 'false');
        button.addClass('active').attr('aria-pressed', 'true');
        
        // Show loading
        $('.suggestions-container').show();
        $('.suggestions-grid').empty();
        $('#get-more-suggestions').hide();
        
        // Get suggestions
        getSuggestions(selectedTopic, true);
    });
    
    // Announce a state (loading, error, empty) in the live region; an empty message clears it.
    function setStatus(message, isError, showRetry) {
        var status = $('.bp-gns-status');
        status.empty().toggleClass('is-error', !!isError);
        
        if (message) {
            status.append($('<span>').text(message));
        }
        
        if (showRetry) {
            $('<button type="button" class="button bp-gns-retry">')
                .text(i18n.retry)
                .appendTo(status);
        }
    }
    
    // Speak a result to screen readers without showing it; an empty message clears the region.
    function announce(message) {
        $('.bp-gns-announce').text(message || '');
    }
    
    // Fill the %d placeholder of a translated string.
    function withCount(template, count) {
        return template.replace('%d', count);
    }
    
    // Function to get suggestions
    function getSuggestions(topic, initial) {
        var moreButton = $('#get-more-suggestions');
        var spinner = moreButton.next('.spinner');
        
        // A disabled button drops keyboard focus, so remember it and give it back afterwards.
        var refocusMore = !initial && document.activeElement === moreButton[0];
        
        if (initial) {
            setStatus(i18n.loading);
        } else {
            moreButton.prop('disabled', true);
            spinner.show();
        }
        
        announce('');
        
        $.ajax({
            url: bpGroupNameSuggester.ajax_url,
            type: 'POST',
            data: {
                action: 'suggest_group_names',
                topic: topic,
                count: initial ? initialCount : moreCount,
                nonce: bpGroupNameSuggester.nonce
            }
        }).done(function(response) {
            // A faster click on another topic already replaced this request.
            if (topic !== selectedTopic) {
                return;
            }
            
            if (!response || !response.success) {
                showError(response && response.data && response.data.message);
                return;
            }
            
            var grid = $('.suggestions-grid');
            
            if (initial) {
                grid.empty();
            }
            
            if (!response.data || !response.data.length) {
                if (initial) {
                    setStatus(i18n.empty);
                    announce(i18n.empty);
                    moreButton.hide();
                }
                return;
            }
            
            setStatus('');
            
            $.each(response.data, function(index, suggestion) {
                // Build the card from text nodes: provider output is never parsed as HTML.
                // Each button names its card, so a screen reader hears which name it applies.
                var titleId = 'bp-gns-name-' + (++cardCount);
                var card = $('<div class="suggestion-card">')
                    .attr({ 'data-name': suggestion.name, 'data-description': suggestion.description })
                    .append(
                        $('<h4>').attr('id', titleId).text(suggestion.name),
                        $('<p>').text(suggestion.description),
                        $('<button type="button" class="use-suggestion-btn">').attr('aria-describedby', titleId).text(i18n.useName)
                    );
                
                if (initial) {
                    card.appendTo(grid);
                } else {
                    card.hide().appendTo(grid).fadeIn(reduceMotion ? 0 : 300);
                }
            });
            
            announce(withCount(initial ? i18n.loaded : i18n.added, response.data.length));
            moreButton.show();
        }).fail(function(jqXHR) {
            if (topic !== selectedTopic) {
                return;
            }
            
            // A 403 nonce failure carries the server's message; network errors and non-JSON bodies have none.
            var json = jqXHR && jqXHR.responseJSON;
            // 403 means the page nonce expired: retrying would resend it, so the member must reload.
            showError(json && json.data && json.data.message, jqXHR && jqXHR.status === 403);
        }).always(function() {
            moreButton.prop('disabled', false);
            spinner.hide();
            
            if (refocusMore) {
                // Fall back to the active topic chip when an error hid the button.
                (moreButton.is(':visible') ? moreButton : $('.topic-btn.active')).trigger('focus');
            }
        });
        
        function showError(message, noRetry) {
            setStatus(message || i18n.error, true, !noRetry);
            
            // Keep "Get More" usable when earlier suggestions are still on screen, unless it cannot succeed.
            moreButton.toggle(!initial && !noRetry);
        }
    }
    
    // Retry the last request after a failure
    $(document).on('click', '.bp-gns-retry', function() {
        if (selectedTopic) {
            getSuggestions(selectedTopic, !$('.suggestions-grid').children().length);
            // The retry button is replaced by the loading message; keep keyboard focus in the widget.
            $('.topic-btn.active').trigger('focus');
        }
    });
    
    // One handler: the whole card is the click target, and the button inside bubbles up to it.
    $(document).on('click', '.suggestion-card', function() {
        var card = $(this);
        
        fillFormFields(card.attr('data-name'), card.attr('data-description'));
        
        // Visual feedback
        $('.suggestion-card').removeClass('selected');
        card.addClass('selected');
    });
    
    // Function to fill form fields
    function fillFormFields(name, description) {
        var nameField = $('#group-name');
        var descField = $('#group-desc');
        
        // Fill the fields
        nameField.val(name);
        descField.val(description);
        
        // Trigger events
        nameField.trigger('change').trigger('input');
        descField.trigger('change').trigger('input');
        
        // Visual feedback: a short highlight styled by the stylesheet, so it follows the theme colours.
        nameField.add(descField).addClass('bp-gns-filled');
        
        setTimeout(function() {
            nameField.add(descField).removeClass('bp-gns-filled');
        }, 1000);
        
        // Move focus to the name field so a keyboard user can edit or continue from here.
        nameField.trigger('focus');
        announce(i18n.applied);
        
        $('html, body').animate({
            scrollTop: nameField.offset().top - 100
        }, reduceMotion ? 0 : 500);
    }
    
    // Handle get more suggestions
    $('#get-more-suggestions').on('click', function() {
        if (selectedTopic) {
            getSuggestions(selectedTopic, false);
        }
    });
    
});