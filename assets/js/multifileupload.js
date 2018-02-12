(function () {

        var MultiFileUpload,
            rawurlencode = function (str) {
                //       discuss at: http://locutus.io/php/rawurlencode/
                //      original by: Brett Zamir (http://brett-zamir.me)
                //         input by: travc
                //         input by: Brett Zamir (http://brett-zamir.me)
                //         input by: Michael Grier
                //         input by: Ratheous
                //      bugfixed by: Kevin van Zonneveld (http://kvz.io)
                //      bugfixed by: Brett Zamir (http://brett-zamir.me)
                //      bugfixed by: Joris
                // reimplemented by: Brett Zamir (http://brett-zamir.me)
                // reimplemented by: Brett Zamir (http://brett-zamir.me)
                //           note 1: This reflects PHP 5.3/6.0+ behavior
                //           note 1: Please be aware that this function expects \
                //           note 1: to encode into UTF-8 encoded strings, as found on
                //           note 1: pages served as UTF-8
                //        example 1: rawurlencode('Kevin van Zonneveld!')
                //        returns 1: 'Kevin%20van%20Zonneveld%21'
                //        example 2: rawurlencode('http://kvz.io/')
                //        returns 2: 'http%3A%2F%2Fkvz.io%2F'
                //        example 3: rawurlencode('http://www.google.nl/search?q=Locutus&ie=utf-8')
                //        returns 3: 'http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3DLocutus%26ie%3Dutf-8'
                str = (str + '');

                // Tilde should be allowed unescaped in future versions of PHP (as reflected below),
                // but if you want to reflect current
                // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
                return encodeURIComponent(str)
                    .replace(/!/g, '%21')
                    .replace(/'/g, '%27')
                    .replace(/\(/g, '%28')
                    .replace(/\)/g, '%29')
                    .replace(/\*/g, '%2A')
            },
            __extends = function (child, parent) {
                for (var i in child) {
                    if (child.hasOwnProperty(i)) {
                        parent[i] = child[i];
                    }
                }
                return parent;
            },
            __getField = function (dropzone, name) {
                var fields = dropzone.element.querySelectorAll('input[name="' + (typeof name !== 'undefined' ? name + '_' : '') + dropzone.options.paramName + '"]');

                if (typeof fields != 'undefined') {
                    return fields[0];
                }

                return 'undefined';
            },
            __registerOnClick = function (file, action) {
                if (typeof action == 'undefined') return false;
                file.previewElement.setAttribute('onclick', action);
                file.previewElement.className = "" + file.previewElement.className + " has-info";
            },
            camelize = function (str) {
                return str.replace(/[\-_](\w)/g, function (match) {
                    return match.charAt(1).toUpperCase();
                });
            },
            __submitOnChange = function (dropzone, callback) {
                if (callback) {

                    if (callback == 'this.form.submit()') {
                        document.createElement('form').submit.call(__getField(dropzone).form);
                        return;
                    }

                    var fn = Function(callback);
                    fn();
                }
            },
            __defaults = {
                init: function () {
                    // listeners
                    this.on('thumbnail', function (file, dataUrl) {
                        if (file.width < this.options.minImageWidth || file.height < this.options.minImageHeight) {
                            if (typeof file.rejectDimensions === 'function')
                                file.rejectDimensions();
                        }
                        else {
                            if (typeof file.acceptDimensions === 'function')
                                file.acceptDimensions();
                        }
                    }).on('removedfile', function (file) {
                        // remove the file from the server on form submit (store deleted files in hidden _deleted field)
                        if (file.accepted) {
                            var uploaded = __getField(this, 'uploaded'),
                                deleted = __getField(this, 'deleted'),
                                filesToSave = __getField(this);

                            if (typeof uploaded !== 'undefined' && typeof file.uuid != 'undefined') {
                                var arrUploaded = JSON.parse(uploaded.value);
                                uploaded.value = JSON.stringify(HASTE_PLUS.removeFromArray(file.uuid, arrUploaded));
                            }

                            if (typeof filesToSave !== 'undefined' && typeof file.uuid != 'undefined') {
                                var arrFilesToSave = JSON.parse(filesToSave.value);
                                filesToSave.value = JSON.stringify(HASTE_PLUS.removeFromArray(file.uuid, arrFilesToSave));
                            }

                            if (typeof deleted !== 'undefined' && typeof file.uuid != 'undefined') {
                                var arrDeleted = JSON.parse(deleted.value);
                                arrDeleted.push(file.uuid);
                                deleted.value = JSON.stringify(arrDeleted);
                            }

                            // remove dz-has-files css class
                            if (this.files.length < 1) {
                                this.element.classList.remove('dz-has-files');
                            }

                            // submitOnChange support for multiple files only
                            if (this.options.maxFiles != 1) {
                                __submitOnChange(this, this.options.onchange);
                            }
                        }

                    }).on('success', function (file, response) {
                        if (typeof response.result == 'undefined') {
                            dropzone.emit("error", file, dropzone.options.dictResponseError.replace("{{statusCode}}", ': Empty response'), response);
                            return;
                        }

                        // update request token
                        dropzone.options.url = HASTE_PLUS.addParameterToUri(dropzone.options.url, 'ato', response.token);

                        // each file is handled here
                        response = response.result.data;

                        if (response.result == 'undefined') {
                            return false;
                        }

                        var uploaded = __getField(this, 'uploaded'),
                            filesToSave = __getField(this);

                        if (response instanceof Array) {
                            for (var i = 0, len = response.length; i < len; i++) {
                                if ((objHandler = handleResponse(file, response[i])) !== false) {
                                    file = objHandler;
                                    persistFile(file, uploaded, filesToSave);
                                    if (file.dataURL) {
                                        dropzone.emit('thumbnail', file, file.dataURL);
                                    }
                                    __registerOnClick(file, file.info);
                                    break; // if file found break
                                }
                            }
                        }
                        else {
                            if ((objHandler = handleResponse(file, response)) !== false) {
                                file = objHandler;
                                persistFile(file, uploaded, filesToSave);
                                if (file.dataURL) {
                                    dropzone.emit('thumbnail', file, file.dataURL);
                                }
                                __registerOnClick(file, file.info);
                            }
                        }

                        __submitOnChange(dropzone, dropzone.options.onchange);

                        function persistFile(file, uploaded, filesToSave) {
                            if (typeof uploaded != 'undefined') {
                                try {
                                    var arrUploaded = JSON.parse(uploaded.value);
                                } catch (e) {
                                    return false;
                                }
                                arrUploaded.push(file.uuid);
                                uploaded.value = JSON.stringify(arrUploaded);
                            }

                            if (typeof filesToSave != 'undefined') {
                                try {
                                    var arrFilesToSave = JSON.parse(filesToSave.value);
                                } catch (e) {
                                    return false;
                                }

                                arrFilesToSave.push(file.uuid);
                                filesToSave.value = JSON.stringify(arrFilesToSave);
                            }
                        }

                        function handleResponse(file, response) {
                            if (response.error) {
                                dropzone.emit("error", file, response.error, response);
                                return false;
                            }


                            // save comparison of the encoded file names
                            if (response.filenameOrigEncoded == rawurlencode(file.name) && response.uuid != 'undefined') {
                                file.serverFileName = response.filename;
                                file.uuid = response.uuid;
                                file.url = response.url;
                                file.info = response.info;
                                file.sanitizedName = response.filenameSanitized;

                                // do always use the sanitized filename as dropzone preview name
                                file.previewElement.querySelector('[data-dz-name]').innerHTML = response.filenameSanitized;

                                return file;
                            }

                            return false;
                        }
                    }).on('error', function (file, message, xhr) {

                        // remove dz-error-show from other preview elements
                        var siblings = file.previewElement.parentNode.querySelectorAll('.dz-error-show');

                        if (siblings) {
                            for (var i = 0, len = siblings.length; i < len; i++) {
                                var sibling = siblings[i];
                                sibling.classList.remove('dz-error-show');
                            }
                        }

                        file.previewElement.classList.remove("dz-success");
                        file.previewElement.classList.add("dz-error-show");

                        file.previewElement.addEventListener("mouseleave", function () {
                            this.classList.remove('dz-error-show');
                        });
                    }).on('sending', function (file, xhr, formData) {
                        // append the whole form data

                        var form = __getField(this).form;

                        formData.append('action', this.options.uploadAction);
                        formData.append('requestToken', this.options.requestToken);
                        formData.append('FORM_SUBMIT', form.id);
                        formData.append('field', this.options.paramName);

                        var inputs = form.querySelectorAll('input[name]:not([disabled]), textarea[name]:not([disabled]), select[name]:not([disabled]), button[name]:not([disabled])');

                        for (var i = 0, len = inputs.length; i < len; i++) {
                            var input = inputs[i];
                            formData.append(input.name, input.value);
                        }
                    }).on('addedfile', function (file) {
                        if (this.files.length > 0) {
                            this.element.classList.add('dz-has-files');
                        }
                    });

                    // add mock files
                    var initialFiles = __getField(this, 'formattedInitial').value,
                        dropzone = this;

                    if (typeof initialFiles !== 'undefined' && initialFiles != '') {
                        mocks = JSON.parse(initialFiles);

                        for (var i = 0; i < mocks.length; i++) {
                            var mock = mocks[i];
                            mock.accepted = true;

                            this.files.push(mock);
                            this.emit('addedfile', mock);
                            if (mock.dataURL) {
                                this.emit('thumbnail', mock, mock.dataURL);
                            }
                            __registerOnClick(mock, mock.info);
                            this.emit('complete', mock);
                        }

                        if (this.files.length > 0) {
                            this.element.classList.add('dz-has-files');
                        }
                    }
                }
            };

        MultiFileUpload = {
            init: function () {
                // Disabling autoDiscover, otherwise Dropzone will try to attach twice.
                Dropzone.autoDiscover = false;
                this.registerFields();
            },
            registerFields: function () {

                var fields = document.querySelectorAll('.multifileupload');

                for (var i = 0, len = fields.length; i < len; i++) {
                    var field = fields[i];

                    // do not attach Dropzone again
                    if (typeof field.dropzone != 'undefined') continue;

                    var attributes = field.attributes,
                        n = attributes.length,
                        data = field.dataset;

                    // ie 10 supports no dataset
                    if (typeof data == 'undefined') {
                        data = {};

                        for (; n--;) {
                            if (/^data-.*/.test(attributes[n].name)) {
                                var key = camelize(attributes[n].name.replace('data-', ''));
                                data[key] = attributes[n].value;
                            }
                        }
                    }

                    function replaceAll(subject, search, replacement) {
                        return subject.split(search).join(replacement);
                    }

                    var localizations = ['dictFileTooBig', 'dictResponseError'];

                    for (var j = 0; j < localizations.length; j++) {
                        data[localizations[j]] = replaceAll(data[localizations[j]], '{.{', '{{');
                        data[localizations[j]] = replaceAll(data[localizations[j]], '}.}', '}}');
                    }

                    var config = __extends(data, __defaults);

                    config.url = location.href;

                    if (HASTE_PLUS.isTruthy(history.state) && HASTE_PLUS.isTruthy(history.state.url)) {
                        config.url = history.state.url;
                    }

                    if (config.uploadActionParams) {
                        var params = HASTE_PLUS.parseQueryString(config.uploadActionParams);
                        config.url = HASTE_PLUS.addParametersToUri(config.url, params);
                    }

                    new Dropzone(field, config);
                }
            }
        };

        // jquery support
        if (window.jQuery) {
            jQuery(document).ready(function () {
                MultiFileUpload.init();
            });

            jQuery(document).ajaxComplete(function () {
                MultiFileUpload.init();
            });
        }

        // mootools support
        if (window.MooTools) {

            window.addEvent('domready', function () {
                MultiFileUpload.init();
            });

            window.addEvent('ajax_change', function () {
                MultiFileUpload.init();
            });
        }

    }
).call(this);

