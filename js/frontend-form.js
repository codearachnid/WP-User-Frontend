;(function($) {
    var WP_User_Frontend = {
        init: function() {
            // clone and remove repeated field
            $('.wpuf-form').on('click', 'img.wpuf-clone-field', this.cloneField);
            $('.wpuf-form').on('click', 'img.wpuf-remove-field', this.removeField);

            $('#wpuf-form-add').on('submit', this.handleForm);
        },

        cloneField: function(e) {
            e.preventDefault();

            var $div = $(this).closest('tr');
            var $clone = $div.clone();
            // console.log($clone);

            //clear the inputs
            $clone.find('input').val('');
            $clone.find(':checked').attr('checked', '');
            $div.after($clone);
        },

        removeField: function() {
            //check if it's the only item
            var $parent = $(this).closest('tr');
            var items = $parent.siblings().andSelf().length;

            if( items > 1 ) {
                $parent.remove();
            }
        },

        handleForm: function(e) {
            e.preventDefault();

            var self = $(this),
                temp,
                temp_val = '',
                error = false,
                error_items = [];

            // remove all initial errors if any
            WP_User_Frontend.removeErrors(self);
            WP_User_Frontend.removeErrorNotice(self);

            // ===== Validate: Text and Textarea ========
            var required = self.find('[data-required="yes"]');

            required.each(function(i, item) {
                // temp_val = $.trim($(item).val());

                // console.log( $(item).data('type') );
                var data_type = $(item).data('type')
                    val = '';

                switch(data_type) {
                    case 'rich':
                        var name = $(item).data('id')
                        val = $.trim( tinyMCE.get(name).getContent() );

                        if ( val === '') {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'textarea':
                    case 'text':
                        val = $.trim( $(item).val() );

                        if ( val === '') {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'select':
                        val = $(item).val();

                        // console.log(val);
                        if ( !val || val === '-1' ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'multiselect':
                        val = $(item).val();

                        if ( val === null || val.length === 0 ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'tax-checkbox':
                        var length = $(item).children().find('input:checked').length;

                        if ( !length ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'radio':
                        var length = $(item).parent().find('input:checked').length;

                        if ( !length ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'file':
                        var length = $(item).next('ul').children().length;

                        if ( !length ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;
                };

            });

            // check Email and URL
            self.find('input.url').each(function(i, item) {
                if ( $(item).val() !== '' ) {
                    //run the validation
                    if( !WP_User_Frontend.isValidURL( $(item).val() ) ) {
                        error = true;

                        WP_User_Frontend.markError(item);
                    }
                }
            });

            self.find('input.email').each(function(i, item) {
                if ( $(item).val() !== '' ) {
                    //run the validation
                    if( !WP_User_Frontend.isValidEmail( $(item).val() ) ) {
                        error = true;

                        WP_User_Frontend.markError(item);
                    }
                }
            });

            // if already some error found, bail out
            if (error) {
                // add error notice
                WP_User_Frontend.addErrorNotice(self);

                return false;
            }

            // send the request
            self.find('li.wpuf-submit').append('<span class="wpuf-loading"></span>');
            self.find('input[type=submit]').addClass('button-primary-disabled');
            $.post(wpuf_frontend.ajaxurl, $(this).serialize(), function(res) {

                if ( res.success) {
                    window.location = res.redirect_to;
                } else {
                    alert( res.error );
                }

                self.find('input[type=submit]').removeClass('button-primary-disabled');
                self.find('span.wpuf-loading').remove();
            });
        },

        addErrorNotice: function(form) {
            $(form).find('li.wpuf-submit').append('<div class="wpuf-errors">' + wpuf_frontend.error_message + '</div>');
        },

        removeErrorNotice: function(form) {
            $(form).find('.wpuf-errors').remove();
        },

        markError: function(item) {
            $(item).closest('li').addClass('has-error');
            $(item).focus();
        },

        removeErrors: function(item) {
            $(item).find('.has-error').removeClass('has-error');
        },

        isValidEmail: function( email ) {
            var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
            return pattern.test(email);
        },

        isValidURL: function(url) {
            var urlregex = new RegExp("^(http:\/\/www.|https:\/\/www.|ftp:\/\/www.|www.|http:\/\/|https:\/\/){1}([0-9A-Za-z]+\.)");
            return urlregex.test(url);
        }
    };

    $(function() {
        WP_User_Frontend.init();
    });

})(jQuery);