/// <reference path="jquery.d.ts" />
/// <reference path="diff_match_patch.d.ts" />
var LiveTexyEditor;
(function (LiveTexyEditor) {
    var Panel = (function () {
        /**
        * @param name      panel name
        * @param outOfDate does panel content need to be updated?
        */
        function Panel(name, outOfDate) {
            if (typeof outOfDate === "undefined") { outOfDate = false; }
            this.name = name;
            this.outOfDate = outOfDate;
            /** is panel visible? */
            this.visible = false;
            /** panel content */
            this.content = '';
        }
        return Panel;
    })();

    var DiffRenderer = (function () {
        function DiffRenderer(contextChars, contextLines) {
            this.contextChars = contextChars;
            this.contextLines = contextLines;
        }
        DiffRenderer.prototype.render = function (diffs) {
            var html = [];
            for (var i = 0; i < diffs.length; i++) {
                var op = diffs[i][0];
                var data = diffs[i][1];
                var text = this.escapeHtml(data);
                switch (op) {
                    case DIFF_INSERT:
                        html[i] = '<ins>' + this.visualizeWhitespaces(text) + '</ins>';
                        break;

                    case DIFF_DELETE:
                        html[i] = '<del>' + this.visualizeWhitespaces(text) + '</del>';
                        break;

                    case DIFF_EQUAL:
                        if (i === 0) {
                            text = this.reduceStringLeft(text, this.contextChars, this.contextLines);
                        } else if (i === diffs.length - 1) {
                            text = this.reduceStringRight(text, this.contextChars, this.contextLines);
                        } else if (text.length > 2 * this.contextChars) {
                            var after = this.reduceStringRight(text, this.contextChars, this.contextLines);
                            var before = this.reduceStringLeft(text, this.contextChars, this.contextLines);
                            text = after + '</div><div>' + before;
                        }

                        html[i] = text;
                        break;
                }
            }
            return '<div>' + html.join('') + '</div>';
        };

        DiffRenderer.prototype.escapeHtml = function (s) {
            return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        };

        DiffRenderer.prototype.visualizeWhitespaces = function (s) {
            s = s.replace(/\n/g, '<span class="whitespace para">&para;</span>\n');
            s = s.replace(/\t/g, '<span class="whitespace tab">\t</span>');
            return s;
        };

        DiffRenderer.prototype.reduceStringLeft = function (s, maxLen, maxLines) {
            s = s.substr(-maxLen);
            for (var i = 0, pos = s.length; i < maxLines; i++) {
                pos = s.lastIndexOf('\n', pos);
                if (pos === -1)
                    return s;
else
                    pos--;
            }
            s = s.substr(pos + 2);
            s = s.replace(/^\s+/, '');
            return s;
        };

        DiffRenderer.prototype.reduceStringRight = function (s, maxLen, maxLines) {
            s = s.substr(0, maxLen);
            for (var i = 0, pos = 0; i < maxLines; i++) {
                pos = s.indexOf('\n', pos);
                if (pos === -1)
                    return s;
else
                    pos++;
            }

            s = s.substr(0, pos - 1);
            s = s.replace(/\s+$/, '');
            return s;
        };
        return DiffRenderer;
    })();

    var Model = (function () {
        function Model(diffRenderer, processUrl, controlId) {
            this.diffRenderer = diffRenderer;
            this.processUrl = processUrl;
            this.controlId = controlId;
            this.handlers = {};
            this.initEvents();
            this.initPanels();
        }
        Object.defineProperty(Model.prototype, "Input", {
            get: function () {
                return this.panels['code'].content;
            },
            set: function (val) {
                if (val === this.panels['code'].content)
                    return;
                this.panels['code'].content = val;

                for (var name in this.panels) {
                    if (name === 'code')
                        continue;
                    var panel = this.panels[name];
                    if (panel.visible) {
                        this.scheduleForUpdate(panel);
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
            this.panels = {
                code: new Panel('code'),
                preview: new Panel('preview', true),
                diff: new Panel('diff', true)
            };
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

        Model.prototype.scheduleForUpdate = function (panel) {
            var _this = this;
            clearTimeout(panel.timeoutId);
            panel.timeoutId = setTimeout(function () {
                _this.updatePanel(panel);
            }, 800);
        };

        Model.prototype.updatePanel = function (panel) {
            var _this = this;
            panel.outOfDate = false;

            if (panel.name === 'preview') {
                var data = {};
                data[this.controlId + '-texyContent'] = this.Input;

                $.post(this.processUrl, data, function (payload) {
                    panel.content = payload.htmlContent;
                    _this.trigger(panel.name + ':change', {
                        'name': panel.name + ':change',
                        'panel': panel
                    });
                });
            } else if (panel.name === 'diff') {
                var dmp = new diff_match_patch();
                var diffs = dmp.diff_main(this.OriginalContent, this.Input);
                dmp.diff_cleanupSemantic(diffs);
                panel.content = this.diffRenderer.render(diffs);
                this.trigger(panel.name + ':change', {
                    'name': panel.name + ':change',
                    'panel': panel
                });
            }
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
            this.diff = this.main.find('.diff .content');
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

            this.model.on('diff:change', function () {
                _this.diff.html(_this.model.Diff);
            });
        };

        EditorView.prototype.initPanels = function () {
            this.model.OriginalContent = this.textarea.data('original');
            this.model.Input = this.textarea.val();
            this.model.VisiblePanels = this.container.find('select[name=panels]').val().split(' ');

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
        var diffRenderer = new DiffRenderer(300, 4);
        var model = new Model(diffRenderer, processUrl, controlId);
        var view = new EditorView(container, model);

        var backupAlert = localStorage.getItem('backupAlert');
        if (!backupAlert) {
            alert('You are responsible for backing up what you\'ve written, because I haven\'t implemented it yet. Your text may be lost at unexpected moments.');
            localStorage.setItem('backupAlert', 'true');
        }
    });
})(LiveTexyEditor || (LiveTexyEditor = {}));
//# sourceMappingURL=main.js.map
