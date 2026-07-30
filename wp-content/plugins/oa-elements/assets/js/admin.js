/**
 * Open Agency Elements - Admin JavaScript
 * Version: 1.2.2
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Copy button functionality
        $(document).on('click', '.oa-copy-btn', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $input = $button.prev('input');
            var originalText = $button.text();
            
            // Select and copy text
            $input.select();
            document.execCommand('copy');
            
            // Show feedback
            $button.text('Copied!');
            $button.addClass('copied');
            
            // Reset after 2 seconds
            setTimeout(function() {
                $button.text(originalText);
                $button.removeClass('copied');
            }, 2000);
        });
        
        // Dynamic settings fields
        $('.oa-feature-toggle').on('change', function() {
            var feature = $(this).data('feature');
            var $dependentFields = $('[data-depends-on="' + feature + '"]');
            
            if ($(this).is(':checked')) {
                $dependentFields.slideDown();
            } else {
                $dependentFields.slideUp();
            }
        });
        
        // Initialize tooltips
        $('.oa-help-tooltip').tooltip({
            position: { my: 'left+5 center', at: 'right center' }
        });
        
        // Form validation
        $('form').on('submit', function(e) {
            var isValid = true;
            
            // Check required fields
            $('.oa-required').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                    isValid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
        
        // URL validation for social links
        $('input[type="url"]').on('blur', function() {
            var url = $(this).val();
            if (url && !isValidUrl(url)) {
                $(this).addClass('error');
                $(this).after('<span class="error-message">Please enter a valid URL</span>');
            } else {
                $(this).removeClass('error');
                $(this).next('.error-message').remove();
            }
        });
        
        // Helper function to validate URLs
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
        
        // Auto-save functionality
        var autoSaveTimer;
        $('input, select, textarea').on('change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                // Show auto-save indicator
                $('.oa-auto-save-indicator').fadeIn().delay(2000).fadeOut();
            }, 1000);
        });
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
                e.preventDefault();
                $('input[type="submit"]').click();
            }
        });
        
        // Responsive table handling
        function handleResponsiveTables() {
            if ($(window).width() < 768) {
                $('.oa-responsive-table').each(function() {
                    var $table = $(this);
                    var $header = $table.find('thead');
                    var $body = $table.find('tbody');
                    
                    if ($header.length && $body.length) {
                        $body.find('tr').each(function() {
                            var $row = $(this);
                            $row.find('td').each(function(index) {
                                var $cell = $(this);
                                var headerText = $header.find('th').eq(index).text();
                                $cell.attr('data-label', headerText);
                            });
                        });
                    }
                });
            }
        }
        
        // Initialize responsive tables
        handleResponsiveTables();
        $(window).on('resize', handleResponsiveTables);
        
        // Color field functionality
        $('.oa-color-picker').on('change', function() {
            var color = $(this).val();
            var textInput = $(this).siblings('.oa-color-text');
            var preview = $(this).closest('.oa-color-field').find('.oa-color-preview');
            
            textInput.val(color.toUpperCase());
            preview.css('background-color', color);
            preview.text(color.toUpperCase());
        });
        
        $('.oa-color-text').on('input', function() {
            var color = $(this).val();
            var picker = $(this).siblings('.oa-color-picker');
            var preview = $(this).closest('.oa-color-field').find('.oa-color-preview');
            
            // Validate hex color format
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                $(this).removeClass('invalid');
                picker.val(color);
                preview.css('background-color', color);
                preview.text(color.toUpperCase());
            } else {
                $(this).addClass('invalid');
                preview.text('Invalid Color');
                preview.css('background-color', '#f7f7f7');
            }
        });
        
        $('.oa-color-reset').on('click', function() {
            var defaultColor = $(this).data('default');
            var colorField = $(this).closest('.oa-color-field');
            var picker = colorField.find('.oa-color-picker');
            var textInput = colorField.find('.oa-color-text');
            var preview = colorField.find('.oa-color-preview');
            
            picker.val(defaultColor);
            textInput.val(defaultColor.toUpperCase());
            preview.css('background-color', defaultColor);
            preview.text(defaultColor.toUpperCase());
            textInput.removeClass('invalid');
        });
        
        $('.oa-color-preset').on('click', function() {
            var color = $(this).data('color');
            var colorField = $(this).closest('.oa-color-field');
            var picker = colorField.find('.oa-color-picker');
            var textInput = colorField.find('.oa-color-text');
            var preview = colorField.find('.oa-color-preview');
            
            picker.val(color);
            textInput.val(color.toUpperCase());
            preview.css('background-color', color);
            preview.text(color.toUpperCase());
            textInput.removeClass('invalid');
        });
        
    });
    
})(jQuery); 