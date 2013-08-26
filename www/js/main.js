/// <reference path="jquery.d.ts" />
var LiveTexyEditor;
(function (LiveTexyEditor) {
    var Panel = (function () {
        function Panel(name) {
            this.name = name;
            /** is panel visible? */
            this.visible = false;
            /** panel content */
            this.content = '';
            /** does panel content need to be updated? */
            this.outOfDate = false;
        }
        return Panel;
    })();

    var Model = (function () {
        function Model(processUrl) {
            this.processUrl = processUrl;
            this.handlers = {};
            this.initEvents();
            this.initPanels();
        }
        Object.defineProperty(Model.prototype, "Input", {
            get: function () {
                return this.panels['code'].content;
            },
            set: function (val) {
                var _this = this;
                if (val === this.panels['code'].content)
                    return;
                this.panels['code'].content = val;

                for (var name in this.panels) {
                    if (name === 'code')
                        continue;
                    var panel = this.panels[name];
                    if (panel.visible) {
                        clearTimeout(panel.timeoutId);
                        panel.timeoutId = setTimeout(function () {
                            _this.updatePanel(panel);
                        }, 800);
                    } else {
                        panel.outOfDate = true;
                    }
                }
            },
            enumerable: true,
            configurable: true
        });


        Object.defineProperty(Model.prototype, "Preview", {
            get: function () {
                return this.panels['preview'].content;
            },
            enumerable: true,
            configurable: true
        });

        Object.defineProperty(Model.prototype, "Diff", {
            get: function () {
                return this.panels['diff'].content;
            },
            enumerable: true,
            configurable: true
        });

        Object.defineProperty(Model.prototype, "VisiblePanels", {
            get: function () {
                var visiblePanels = [];
                for (var name in this.panels) {
                    if (this.panels[name].visible) {
                        visiblePanels.push(name);
                    }
                }
                return visiblePanels;
            },
            set: function (visiblePanels) {
                for (var name in this.panels) {
                    var panel = this.panels[name];
                    var visibility = (visiblePanels.indexOf(name) !== -1);
                    if (panel.visible === visibility)
                        continue;

                    panel.visible = visibility;
                    var eventName = 'panel:' + (visibility ? 'show' : 'hide');
                    this.trigger(eventName, {
                        'name': eventName,
                        'panel': panel
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
                if (e.panel.outOfDate) {
                    _this.updatePanel(e.panel);
                }
            });
        };

        Model.prototype.initPanels = function () {
            this.panels = {};
            this.panels['code'] = new Panel('code');
            this.panels['preview'] = new Panel('preview');
            this.panels['preview'].outOfDate = true;
        };

        Model.prototype.trigger = function (eventName, event) {
            console.log(eventName, event);
            if (typeof event === 'undefined')
                event = { name: eventName };

            if (eventName in this.handlers) {
                for (var i = 0; i < this.handlers[eventName].length; i++) {
                    this.handlers[eventName][i](event);
                }
            }
        };

        Model.prototype.updatePanel = function (panel) {
            var _this = this;
            panel.outOfDate = false;
            var xhr = $.post(this.processUrl, {
                "editor-texyContent": this.Input
            });

            xhr.done(function (payload) {
                panel.content = payload.htmlContent;
                _this.trigger(panel.name + ':change', {
                    'name': panel.name + ':change',
                    'panel': panel
                });
            });
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
            this.main = this.container.find('.main');
            this.textarea = this.main.find('.code textarea');
            this.preview = this.main.find('.preview iframe');
        };

        EditorView.prototype.initEvents = function () {
            var _this = this;
            this.container.find('select[name=panels]').on('change', function (e) {
                var input = e.target;
                _this.model.VisiblePanels = input.value.split(' ');
            });

            this.container.find('input[name=message]').on('keydown', function (e) {
                if (e.keyCode !== 13 || e.ctrlKey || e.altKey || e.shiftKey || e.metaKey)
                    return;
                e.preventDefault();
                _this.container.find('input[name=save]').trigger('click');
            });

            this.textarea.on('keydown', function (e) {
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

            this.textarea.on('keyup', function (e) {
                var textarea = e.target;
                _this.model.Input = textarea.value;
            });

            this.textarea.on('scroll', function () {
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
                _this.main.toggleClass(e.panel.name, e.panel.visible);
            });

            this.model.on('preview:change', function () {
                var iframe = _this.preview.get(0);
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

    $(function () {
        var container = $('.live-texy-editor');
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
