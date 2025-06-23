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
                        var card = $('<div class="suggestion-card" data-name="' + escapeHtml(suggestion.name) + '" data-description="' + escapeHtml(suggestion.description) + '">' +
                            '<h4>' + escapeHtml(suggestion.name) + '</h4>' +
                            '<p>' + escapeHtml(suggestion.description) + '</p>' +
                            '<button type="button" class="use-suggestion button-small">Use This Name</button>' +
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
    
    // Handle suggestion selection
    $(document).on('click', '.use-suggestion', function() {
        var card = $(this).closest('.suggestion-card');
        var name = card.data('name');
        var description = card.data('description');
        
        // Fill in the form fields
        $('#group-name').val(name);
        $('#group-desc').val(description);
        
        // Trigger change events
        $('#group-name').trigger('change');
        $('#group-desc').trigger('change');
        
        // Visual feedback
        $('.suggestion-card').removeClass('selected');
        card.addClass('selected');
        
        // Smooth scroll to form
        $('html, body').animate({
            scrollTop: $('#group-name').offset().top - 100
        }, 500);
    });
    
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
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});