jQuery(document).ready(function($) {
    
    var selectedTopic = null;
    
    // Handle topic selection
    $('.topic-btn').on('click', function() {
        var button = $(this);
        selectedTopic = button.data('topic');
        
        // Visual feedback
        $('.topic-btn').removeClass('active');
        button.addClass('active');
        
        // Show loading
        $('.suggestions-container').show();
        $('.suggestions-grid').html('<div class="loading">Generating suggestions...</div>');
        $('#get-more-suggestions').hide();
        
        // Get suggestions
        getSuggestions(selectedTopic, true);
    });
    
    // Function to get suggestions
    function getSuggestions(topic, initial) {
        $.ajax({
            url: bpGroupNameSuggester.ajax_url,
            type: 'POST',
            data: {
                action: 'suggest_group_names',
                topic: topic,
                count: initial ? 20 : 10,
                nonce: bpGroupNameSuggester.nonce
            },
            success: function(response) {
                if (response.success) {
                    var grid = $('.suggestions-grid');
                    
                    if (initial) {
                        grid.empty();
                    }
                    
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
                    
                    $('#get-more-suggestions').show();
                }
            },
            error: function() {
                $('.suggestions-grid').html('<div class="error">Error generating suggestions. Please try again.</div>');
            }
        });
    }
    
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
            var button = $(this);
            var spinner = button.next('.spinner');
            
            button.prop('disabled', true);
            spinner.show();
            
            getSuggestions(selectedTopic, false);
            
            setTimeout(function() {
                button.prop('disabled', false);
                spinner.hide();
            }, 1000);
        }
    });
    
});