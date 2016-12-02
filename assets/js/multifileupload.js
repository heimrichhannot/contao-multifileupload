(function () {

        var MultiFileUpload,
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
                        }
                    }).on('success', function (file, response) {
                        if(typeof response.result == 'undefined')
                        {
                            dropzone.emit("error", file, dropzone.options.dictResponseError.replace("{{statusCode}}", ': Empty response'), response);
                            return;
                        }

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
                                    if(file.url){
                                        dropzone.createThumbnailFromUrl(file, file.url);
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
                                if(file.url){
                                    dropzone.createThumbnailFromUrl(file, file.url);
                                }
                                __registerOnClick(file, file.info);
                            }
                        }

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

                            if (response.filenameOrig == file.name && response.uuid != 'undefined') {
                                file.serverFileName = response.filename;
                                file.uuid = response.uuid;
                                file.url = response.url;
                                file.info = response.info;
                                return file;
                            }

                            return false;
                        }
                    }).on('error', function(file, message, xhr){

                        // remove dz-error-show from other preview elements
                        var siblings = file.previewElement.parentNode.querySelectorAll('.dz-error-show');

                        if(siblings)
                        {
                            for (var i = 0, len = siblings.length; i < len; i++) {
                                var sibling = siblings[i];
                                sibling.classList.remove('dz-error-show');
                            }
                        }

                        file.previewElement.classList.remove("dz-success");
                        file.previewElement.classList.add("dz-error-show");

                        file.previewElement.addEventListener("mouseleave", function(){
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
                            if(mock.url){
                                this.createThumbnailFromUrl(mock, mock.url);
                            }
                            __registerOnClick(mock, mock.info);
                            this.emit('complete', mock);
                        }
                    }
                }
            }

        MultiFileUpload = {
            init: function () {
                // Disabling autoDiscover, otherwise Dropzone will try to attach twice.
                Dropzone.autoDiscover = false;
                this.registerFields();
            },
            registerFields: function () {

                var fields = document.getElementsByClassName('multifileupload');

                for (var i = 0, len = fields.length; i < len; i++) {
                    var field = fields[i];

                    // do not attach Dropzone again
                    if (typeof field.dropzone != 'undefined') continue;

                    data = field.dataset;

                    var config = __extends(data, __defaults);

                    config.url = history.state != null ? history.state.url : location.href;

                    if (config.uploadActionParams) {
                        config.url = config.url + '?' + config.uploadActionParams;
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
