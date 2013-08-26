var LiveTexyEditor;
(function (LiveTexyEditor) {
    var Model = (function () {
        function Model(processUrl) {
            this.processUrl = processUrl;
            this.panelsVisiblity = {
                code: false,
                preview: false
            };
            this.previewOutOfDate = false;
            this.handlers = {};
            this.initEvents();
        }
        Object.defineProperty(Model.prototype, "Input", {
            get: function () {
                return this.input;
            },
            set: function (val) {
                if (val === this.input)
                    return;
                this.input = val;

                if (this.panelsVisiblity.preview) {
                    clearTimeout(this.previewTimeoutId);
                    this.previewTimeoutId = setTimeout(this.updatePreview.bind(this), 800);
                } else {
                    this.previewOutOfDate = true;
                }
            },
            enumerable: true,
            configurable: true
        });


        Object.defineProperty(Model.prototype, "Preview", {
            get: function () {
                return this.preview;
            },
            enumerable: true,
            configurable: true
        });

        Object.defineProperty(Model.prototype, "VisiblePanels", {
            get: function () {
                var visiblePanels = [];
                for (var panel in this.panelsVisiblity) {
                    if (this.panelsVisiblity[panel]) {
                        visiblePanels.push(panel);
                    }
                }
                return visiblePanels;
            },
            set: function (panels) {
                for (var panel in this.panelsVisiblity) {
                    var visibility = (panels.indexOf(panel) !== -1);
                    if (this.panelsVisiblity[panel] === visibility)
                        continue;

                    this.panelsVisiblity[panel] = visibility;
                    var eventName = 'panel:' + (visibility ? 'show' : 'hide');
                    this.trigger(eventName, {
                        'name': eventName,
                        'panelName': panel,
                        'panelVisibility': visibility
                    });
                }
            },
            enumerable: true,
            configurable: true
        });


        Model.prototype.on = function (eventName, callback) {
            var events = eventName.split(' ');
            for (var i = 0; i < events.length; i++) {
                var event = events[i];
                if (typeof this.handlers[event] === 'undefined')
                    this.handlers[event] = [];
                this.handlers[event].push(callback);
            }
        };

        Model.prototype.initEvents = function () {
            var _this = this;
            this.on('panel:show', function (e) {
                if (e.panelName === 'preview' && _this.previewOutOfDate) {
                    _this.updatePreview();
                }
            });
        };

        Model.prototype.trigger = function (eventName, event) {
            if (typeof event === 'undefined')
                event = { name: eventName };

            if (eventName in this.handlers) {
                for (var i = 0; i < this.handlers[eventName].length; i++) {
                    this.handlers[eventName][i](event);
                }
            }
        };

        Model.prototype.updatePreview = function () {
            this.previewOutOfDate = false;
            /*var xhr = $.post(this.processUrl, {
            "editor-texyContent": this.input
            });
            
            xhr.done((payload) => {
            this.preview = payload.htmlContent;
            this.trigger('preview:change');
            });*/
        };
        return Model;
    })();

    var EditorView = (function () {
        function EditorView(container, model) {
            this.container = container;
            this.model = model;
            this.initElements();
            this.initEvents();
            this.initPanels();
        }
        EditorView.prototype.initElements = function () {
            this.main = this.container.querySelector('.main');
            this.textarea = this.container.querySelector('.code textarea');
            this.preview = this.container.querySelector('.preview iframe');
        };

        EditorView.prototype.initEvents = function () {
            var _this = this;
            this.container.querySelector('select[name=panels]').addEventListener('change', function (e) {
                var input = e.target;
                _this.model.VisiblePanels = input.value.split(' ');
            });

            this.container.querySelector('input[name=message]').addEventListener('keydown', function (e) {
                if (e.keyCode !== 13 || e.ctrlKey || e.altKey || e.shiftKey || e.metaKey)
                    return;
                e.preventDefault();
                _this.container.querySelector('input[name=save]').trigger('click');
            });

            this.textarea.addEventListener('keydown', function (e) {
                if (e.keyCode !== 9 && e.keyCode !== 13)
                    return;
                if (e.ctrlKey || e.altKey || e.metaKey)
                    return;

                // based on code by David Grudl, http://editor.texy.info
                e.preventDefault();
                var textarea = e.target;
                var top = textarea.scrollTop;
                var start = textarea.selectionStart, end = textarea.selectionEnd;
                var lineStart = textarea.value.lastIndexOf('\n', start - 1) + 1;
                var lines = textarea.value.substring(lineStart, end);
                var startMove = 0, endMove = 0;

                if (e.keyCode === 9) {
                    if (e.shiftKey) {
                        startMove = -1;
                        lines = lines.replace(/^\t/gm, '');
                    } else {
                        startMove = 1;
                        if (start !== end)
                            lines = lines.replace(/^/gm, '\t');
else
                            lines += '\t';
                    }
                    // enter
                } else if (e.keyCode === 13) {
                    if (start !== end)
                        return;

                    var m, indentation;
                    if (m = lines.match(/^(\t*)\/\*\*/)) {
                        indentation = m[1];
                        startMove = 4 + indentation.length;
                        endMove = -4 - indentation.length;
                        lines += '\n' + indentation + ' * \n' + indentation + ' */';
                    } else {
                        m = lines.match(/^\t*( \*(?: |$))?/);
                        indentation = m[0] + (m[1] === ' *' ? ' ' : '');
                        startMove = 1 + indentation.length;
                        lines += '\n' + indentation;
                    }
                }

                textarea.value = textarea.value.substring(0, lineStart) + lines + textarea.value.substr(end);

                if (start !== lineStart || start === end)
                    start += startMove;
                end = lineStart + lines.length + endMove;
                textarea.setSelectionRange(start, end);
                textarea.focus();
                textarea.scrollTop = top;
            });

            this.textarea.addEventListener('keyup', function (e) {
                var textarea = e.target;
                _this.model.Input = textarea.value;
            });

            this.textarea.addEventListener('scroll', function () {
                var iframe = _this.preview.get(0);
                var iframeWin = iframe.contentWindow;
                var iframeBody = iframe.contentDocument.body;

                var textareaMaximumScrollTop = _this.textarea.prop('scrollHeight') - _this.textarea.height();
                var iframeMaximumScrollTop = iframeBody.scrollHeight - _this.preview.height();

                var percent = _this.textarea.scrollTop() / textareaMaximumScrollTop;
                var iframePos = iframeMaximumScrollTop * percent;

                iframeWin.scrollTo(0, iframePos);
            });

            this.model.on('panel:show panel:hide', function (e) {
                _this.main.toggleClass(e.panelName, e.panelVisibility);
            });

            this.model.on('preview:change', function () {
                var iframe = _this.preview;
                var iframeWin = iframe.contentWindow;
                var iframeDoc = iframe.contentDocument;
                var scrollY = iframeWin.pageYOffset;
                iframeDoc.open('text/html', 'replace');
                iframeDoc.write(_this.model.Preview);
                iframeDoc.close();
                iframeWin.scrollTo(0, scrollY);
            });
        };

        EditorView.prototype.initPanels = function () {
            this.model.VisiblePanels = this.container.find('select[name=panels]').val().split(' ');
            this.model.Input = this.textarea.val();

            // IE preview height hotfix
            var expectedPreviewHeight = this.main.find('.right').innerHeight();
            if (this.preview.height() !== expectedPreviewHeight) {
                this.preview.css('height', expectedPreviewHeight + 'px');
            }
        };
        return EditorView;
    })();

    document.addEventListener('DOMContentLoaded', function () {
        var container = document.querySelector('.live-texy-editor');
        var model = new Model(processUrl);
        var view = new EditorView(container, model);

        var backupAlert = localStorage.getItem('backupAlert');
        if (!backupAlert) {
            alert('You are responsible for backing up what you\'ve written, because I haven\'t implemented it yet. Your text may be lost at unexpected moments.');
            localStorage.setItem('backupAlert', 'true');
        }
    });
})(LiveTexyEditor || (LiveTexyEditor = {}));
//# sourceMappingURL=main.js.map
