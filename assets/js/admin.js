/*global jQuery, document, console, zodan_change_username_vars*/
jQuery(document.body).ready(function ($) {
    'use strict';

    var Zodan_Change_Username_Settings, Zodan_Change_Username_Profile, profileForm, mount, message, link, currentUsernameInput, newUsernameInput, submitButton, cancelButton, minimumLength;

    var Zodan_Change_Usernames_Bulk;

    /**
     * Settings
     */
    Zodan_Change_Username_Settings = {
        init : function () {
            this.general();
        },

        general : function () {

        }
    };
    Zodan_Change_Username_Settings.init();


    /**
     * Profile page
     */
    Zodan_Change_Username_Profile = {
        init : function () {
            this.general();
        },

        general : function () {
            if((zodan_change_username_vars.current_screen === 'profile' || zodan_change_username_vars.current_screen === 'user-edit') && zodan_change_username_vars.can_change_username === '1') {
                currentUsernameInput = document.getElementById('user_login');
                mount = document.querySelector('.user-user-login-wrap td .description');

                link = this.createElement('a', {
                    id: 'zodan-change-username-link',
                    href: '#',
                    onclick: this.toggle
                }, zodan_change_username_vars.change_button_label );

                newUsernameInput = this.createElement('input', {
                    id: 'zodan-change-username-input',
                    type: 'text',
                    name: 'new_user_login',
                    value: currentUsernameInput.value,
                    className: 'regular-text',
                    style: { 'min-height': '28px' },
                    autocomplete: 'off'
                });

                cancelButton = this.createElement('input', {
                    id: 'zodan-change-username-cancel',
                    type: 'button',
                    value: zodan_change_username_vars.cancel_button_label,
                    className: 'button',
                    style: { 'margin-left': '5px' },
                    onclick: this.toggle
                });

                submitButton = this.createElement('input', {
                    id: 'zodan-change-username-submit',
                    type: 'button',
                    value: zodan_change_username_vars.save_button_label,
                    className: 'button',
                    style: { 'margin-left': '5px' },
                    onclick: this.onSubmit
                });

                profileForm = this.createElement('form', {
                    id: 'zodan-change-username-form',
                    method: 'POST',
                    onsubmit: this.onSubmit,
                    style: { 'display': 'none' }
                }, [
                    newUsernameInput,
                    submitButton,
                    cancelButton
                ]);

                message = this.createElement('p', {
                    id: 'zodan-change-username-message',
                    style: { 'display': 'none' }
                });

                mount.parentNode.replaceChild(link, mount);
                link.parentNode.appendChild(this.createElement('div', [profileForm, message]));

                minimumLength = parseInt(zodan_change_username_vars.minimum_length);

                $('#zodan-change-username-input').on('input', function () {
                    if($(this).val().length < minimumLength) {
                        message.style.color = 'red';
                        message.textContent = zodan_change_username_vars.error_short_username;
                        message.style.display = '';

                        submitButton.disabled = true;
                    } else {
                        if(submitButton.disabled === true) {
                            message.style.display = 'none';
                            message.textContent = '';
                            submitButton.disabled = false;
                            cancelButton.disabled = false;
                        }
                    }
                });
            }
        },

        createElement : function (name, attrs, children) {
            var e = document.createElement(name);

            if(!children && (Array.isArray(attrs) || typeof(attrs) === 'string')) {
                children = attrs;
                attrs = null;
            }

            if(attrs) {
                this.setAttribute(e, attrs);
            }

            if(children) {
                if(typeof(children) === 'string') {
                    e.textContent = children;
                } else {
                    for(var i=0; i<children.length; i++) {
                        e.appendChild(children[i]);
                    }
                }
            }

            return e;
        },

        setAttribute : function(object, attrs) {
            for(var key in attrs) {
                if(typeof(attrs[key]) === 'object') {
                    this.setAttribute(object[key], attrs[key]);
                } else {
                    object[key] = attrs[key];
                }
            }
        },

        toggle : function(e) {
            e.preventDefault();

            if(profileForm.style.display === 'none') {
                profileForm.style.display = '';
                link.style.display = 'none';
                currentUsernameInput.style.display = 'none';
                newUsernameInput.focus();
            } else {
                profileForm.style.display = 'none';
                link.style.display = 'inline';
                currentUsernameInput.style.display = '';
                message.style.display = 'none';
                message.textContent = '';
                newUsernameInput.value = currentUsernameInput.value;
                submitButton.disabled = false;
                cancelButton.disabled = false;
            }
        },

        onSubmit : function(e) {
            e.preventDefault();

            var newUsername, currentUsername, postData, error = true;

            newUsername = profileForm.new_user_login.value;
            currentUsername = currentUsernameInput.value;
            minimumLength = parseInt(zodan_change_username_vars.minimum_length);

            if(newUsername === currentUsername) {
                cancelButton.click();
                return;
            }

            submitButton.value = zodan_change_username_vars.please_wait_message;
            submitButton.disabled = true;
            cancelButton.disabled = true;

            if(newUsername.length < minimumLength) {
                message.style.color = 'red';
                message.textContent = zodan_change_username_vars.error_short_username;
                message.style.display = '';

                submitButton.value = zodan_change_username_vars.save_button_label;
                cancelButton.disabled = false;

                return;
            }

            postData = {
                action: 'change_username',
                old_username: currentUsername,
                new_username: newUsername,
                security: zodan_change_username_vars.nonce
            };

            $.ajax({
                type: 'POST',
                data: postData,
                dataType: 'json',
                url: zodan_change_username_vars.ajaxurl,
                success: function (response) {
                    if(response !== null) {
                        try {
                            zodan_change_username_vars.nonce = response.new_nonce;
                            message.style.color = response.success ? 'green' : 'red';
                            message.innerHTML = response.message;

                            if(response.success) {
                                currentUsernameInput.value = newUsername;
                                error = false;
                            }
                        } catch(e) {
                            message.style.color = 'red';
                            message.textContent = zodan_change_username_vars.error_unknown;
                        }
                    } else {
                        message.style.color = 'red';
                        message.textContent = zodan_change_username_vars.error_unknown;
                    }

                    message.style.display = '';

                    cancelButton.disabled = false;
                    submitButton.value = zodan_change_username_vars.save_button_label;

                    if(error === false) {
                        currentUsernameInput.value = newUsername;

                        if($('input[name="nickname"]').val() === currentUsername) {
                            $('input[name="nickname"]').val(newUsername);
                        }

                        if($('select[name="display_name"] option:selected').text() === currentUsername) {
                            $('select[name="display_name"] option:selected').text(newUsername);
                        }

                        profileForm.style.display = 'none';
                        link.style.display = 'inline';
                        currentUsernameInput.style.display = '';

                        return;
                    }
                }
            }).fail(function (data) {
                if(window.console && window.console.log) {
                    console.log(data);
                }
            });
        }
    };
    Zodan_Change_Username_Profile.init();







    /**
     * Bulk Update page
     */
    Zodan_Change_Usernames_Bulk = {
        init : function () {
            this.general();
        },

        general : function () {
            if( zodan_change_username_vars.can_change_username === '1' ) {

                // When the form is submitted
                const submitButton = document.getElementById('zcu-bulk-update-btn');
                if(submitButton) {
                    submitButton.addEventListener('click', (event) => {
                        const btn = event.target;
                        btn.innerHTML = zodan_change_username_vars.please_wait_message;
                        btn.disabled = true;

                        this.onSubmit(event); 
                    });
                }

                // All checkboxes checked at once
                const selectAllCheckbox = document.getElementById('zcu-select-all');
                if(selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('click', (event) => {
                        let selectAllCheckboxBtn = event.target;

                        let all_checkboxes = document.querySelectorAll('.zcu-user-check');
                        all_checkboxes.forEach( checkbox => {
                            checkbox.checked =  selectAllCheckboxBtn.checked ? true : false;
                        });
                    });
                }
            }
        },

        createElement : function (name, attrs, children) {
            var e = document.createElement(name);

            if(!children && (Array.isArray(attrs) || typeof(attrs) === 'string')) {
                children = attrs;
                attrs = null;
            }

            if(attrs) {
                this.setAttribute(e, attrs);
            }

            if(children) {
                if(typeof(children) === 'string') {
                    e.textContent = children;
                } else {
                    for(var i=0; i<children.length; i++) {
                        e.appendChild(children[i]);
                    }
                }
            }

            return e;
        },

        setAttribute : function(object, attrs) {
            for(var key in attrs) {
                if(typeof(attrs[key]) === 'object') {
                    this.setAttribute(object[key], attrs[key]);
                } else {
                    object[key] = attrs[key];
                }
            }
        },

        toggle : function(e) {
            e.preventDefault();

            if(profileForm.style.display === 'none') {
                profileForm.style.display = '';
                link.style.display = 'none';
                currentUsernameInput.style.display = 'none';
                newUsernameInput.focus();
            } else {
                profileForm.style.display = 'none';
                link.style.display = 'inline';
                currentUsernameInput.style.display = '';
                message.style.display = 'none';
                message.textContent = '';
                newUsernameInput.value = currentUsernameInput.value;
                submitButton.disabled = false;
                cancelButton.disabled = false;
            }
        },

        onSubmit : function(e) {
            e.preventDefault();

            var minimumLength = parseInt(zodan_change_username_vars.minimum_length);

            const uc_bulk_update_form = document.getElementById('zcu-bulk-update-form');
            const bulk_nonce_el = document.getElementById('zodan-change-username-bulk-nonce');
            const all_uc_bulk_table_rows = uc_bulk_update_form.querySelectorAll('table.zcu-bulk-table tbody tr');
            const submitButton = document.getElementById('zcu-bulk-update-btn');
            let allFormData = [];

            all_uc_bulk_table_rows.forEach( row => {
                let checkbox = row.querySelector('.zcu-user-check');
                if( ! checkbox.checked ) {
                    return;
                }

                var newUsername, currentUsername, postData, errors = false;
                newUsername = row.querySelector('.zcu-new-username').value;
                currentUsername = row.getAttribute('data-user-login');
                message = row.querySelector('.zcu-row-status');

                // no input, reset item
                if( newUsername === '' || newUsername.length < 1 ) {
                    checkbox.checked = false;
                    return;
                }

                // not enough input, set warning
                if(newUsername.length < minimumLength) {
                    message.style.color = 'red';
                    message.textContent = zodan_change_username_vars.error_short_username;
                    errors = true;
                    return;
                }
                // same input, reset item
                if(newUsername === currentUsername) {
                    message.style.color = 'orange';
                    message.textContent = zodan_change_username_vars.warning_same_username;
                    errors = false;
                    return;
                }
                allFormData.push({ currentUsername: currentUsername, newUsername: newUsername });
            });

            if( allFormData.length < 1 ) {
                submitButton.innerHTML = submitButton.getAttribute('data-text-default');
                submitButton.disabled = false;
                return;
            }

            let postData = {
                action: 'zodan_user_names_bulk_update',
                updates: allFormData,
                security: bulk_nonce_el.value
            };

            $.ajax({
                type: 'POST',
                data: postData,
                dataType: 'json',
                url: zodan_change_username_vars.ajaxurl,
                success: function (response) {
                    if(response !== null) {
                        bulk_nonce_el.value = response.data.new_nonce;
                        let result_data = response.data.results;
                        try {
                            all_uc_bulk_table_rows.forEach( row => {
                                let checkbox = row.querySelector('.zcu-user-check');
                                let input_username = row.querySelector('.zcu-new-username');
                                let oldname = input_username.getAttribute('data-old');
                                let text_label = row.querySelector('.zcu-current-username');
                                let display_name_label = row.querySelector('.zcu-current-display-name');
                                message = row.querySelector('.zcu-row-status');

                                // Check if the old name matches with the results
                                let match = result_data.find(({ old }) => old === oldname);
                                if( match != undefined ) {
                                    // 1. Set message 
                                    message.style.color = match.success ? 'green' : 'red';
                                    message.innerHTML = match.message;
                                    // 2. Check/uncheck checkbox and set input status
                                    checkbox.checked = match.success ? false : true;
                                    // 3. Set new values
                                    row.setAttribute('data-user-login', match.new);
                                    text_label.innerHTML = match.new;
                                    display_name_label.innerHTML = match.new;
                                    checkbox.value = match.new;
                                    input_username.setAttribute('data-old', match.new);
                                    input_username.placeholder = match.new;
                                } else {
                                    checkbox.checked = false;
                                }

                            });
                        } catch(error) {
                            console.error(error);
                        }
                    } else {
                        console.log('No results');
                    }
                }
            }).fail(function (data) {
                if(window.console && window.console.log) {
                    console.error(data);
                }
            }).always(function(){
                const submitButton = document.getElementById('zcu-bulk-update-btn');
                    submitButton.innerHTML = submitButton.getAttribute('data-text-default');
                    submitButton.disabled = false;
            });
        }
    };
    Zodan_Change_Usernames_Bulk.init();

});

const zodanChangeUsernameOptionsHeaderRow = document.querySelector('.zodan-change-username-options .form-table th:has(h1)');
if( zodanChangeUsernameOptionsHeaderRow ) {
    zodanChangeUsernameOptionsHeaderRow.setAttribute('colspan', 2);
}
