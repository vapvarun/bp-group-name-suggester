jQuery(document).ready(function($) {
    
    var selectedTopic = null;
    var i18n = bpGroupNameSuggester.i18n;
    
    // Handle topic selection
    $('.topic-btn').on('click', function() {
        var button = $(this);
        selectedTopic = button.data('topic');
        
        // Visual feedback
        $('.topic-btn').removeClass('active');
        button.addClass('active');
        
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
    
    // Function to get suggestions
    function getSuggestions(topic, initial) {
        var moreButton = $('#get-more-suggestions');
        var spinner = moreButton.next('.spinner');
        
        if (initial) {
            setStatus(i18n.loading);
        } else {
            moreButton.prop('disabled', true);
            spinner.show();
        }
        
        $.ajax({
            url: bpGroupNameSuggester.ajax_url,
            type: 'POST',
            data: {
                action: 'suggest_group_names',
                topic: topic,
                count: initial ? 20 : 10,
                nonce: bpGroupNameSuggester.nonce
            }
        }).done(function(response) {
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
                    moreButton.hide();
                }
                return;
            }
            
            setStatus('');
            
            $.each(response.data, function(index, suggestion) {
                // Create card with data attributes
                var card = $('<div class="suggestion-card" ' +
                    'data-name="' + suggestion.name.replace(/"/g, '&quot;') + '" ' +
                    'data-description="' + suggestion.description.replace(/"/g, '&quot;') + '">' +
                    '<h4>' + suggestion.name + '</h4>' +
                    '<p>' + suggestion.description + '</p>' +
                    '<button type="button" class="use-suggestion-btn">Use This Name</button>' +
                    '</div>');
                
                if (initial) {
                    card.appendTo(grid);
                } else {
                    card.hide().appendTo(grid).fadeIn(300);
                }
            });
            
            moreButton.show();
        }).fail(function() {
            showError();
        }).always(function() {
            moreButton.prop('disabled', false);
            spinner.hide();
        });
        
        function showError(message) {
            setStatus(message || i18n.error, true, true);
            
            // Keep "Get More" usable when earlier suggestions are still on screen.
            moreButton.toggle(!initial);
        }
    }
    
    // Retry the last request after a failure
    $(document).on('click', '.bp-gns-retry', function() {
        if (selectedTopic) {
            getSuggestions(selectedTopic, !$('.suggestions-grid').children().length);
        }
    });
    
    // Make entire card clickable
    $(document).on('click', '.suggestion-card', function(e) {
        var card = $(this);
        var name = card.attr('data-name');
        var description = card.attr('data-description');
        
        fillFormFields(name, description);
        
        // Visual feedback
        $('.suggestion-card').removeClass('selected');
        card.addClass('selected');
    });
    
    // Handle button click specifically
    $(document).on('click', '.use-suggestion-btn', function(e) {
        e.stopPropagation();
        
        var card = $(this).closest('.suggestion-card');
        var name = card.attr('data-name');
        var description = card.attr('data-description');
        
        fillFormFields(name, description);
        
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
        
        // Visual feedback - flash green
        nameField.add(descField).css({
            'background-color': '#e8f5e9',
            'transition': 'background-color 0.3s'
        });
        
        setTimeout(function() {
            nameField.add(descField).css('background-color', '');
        }, 1000);
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: nameField.offset().top - 100
        }, 500);
    }
    
    // Handle get more suggestions
    $('#get-more-suggestions').on('click', function() {
        if (selectedTopic) {
            getSuggestions(selectedTopic, false);
        }
    });
    
});