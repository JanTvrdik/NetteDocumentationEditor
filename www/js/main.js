var LiveTexyEditor;
(function (LiveTexyEditor) {
    var Model = (function () {
        function Model(processUrl) {
            this.processUrl = processUrl;
            this.handlers = {};
        }
        Object.defineProperty(Model.prototype, "Input", {
            get: function () {
                return this.input;
            },
            set: function (val) {
                if (val !== this.input) {
                    this.input = val;

                    clearTimeout(this.timeoutId);
                    this.timeoutId = setTimeout(this.updateOutput.bind(this), 800);
                }
            },
            enumerable: true,
            configurable: true
        });


        Object.defineProperty(Model.prototype, "Output", {
            get: function () {
                return this.output;
            },
            enumerable: true,
            configurable: true
        });

        Model.prototype.on = function (eventName, callback) {
            if (typeof this.handlers[eventName] === 'undefined')
                this.handlers[eventName] = [];
            this.handlers[eventName].push(callback);
        };

        Model.prototype.trigger = function (eventName) {
            if (eventName in this.handlers) {
                for (var i = 0; i < this.handlers[eventName].length; i++) {
                    this.handlers[eventName][i]();
                }
            }
        };

        Model.prototype.updateOutput = function () {
            var _this = this;
            var xhr = $.post(this.processUrl, {
                "editor-texyContent": this.input
            });

            xhr.done(function (payload) {
                _this.output = payload.htmlContent;
                _this.trigger('output:change');
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
            this.panels = this.container.find('select[name=panels]');
            this.flexContainer = this.container.find('.main');
            this.textarea = this.container.find('textarea');
            this.output = this.container.find('.output');
        };

        EditorView.prototype.initEvents = function () {
            var _this = this;
            this.panels.on('change', function (e) {
                console.log('X');
                var panels = _this.panels.val().split(' ');
                _this.flexContainer.removeClass('left-only right-only');
                if (panels.length === 1) {
                    var className = (panels[0] === 'code' ? 'left-only' : 'right-only');
                    _this.flexContainer.addClass(className);
                }
            });

            this.textarea.on('keydown', function (e) {
                if (e.keyCode !== 9 && e.keyCode !== 13)
                    return;
                if (e.ctrlKey || e.altKey || e.metaKey)
                    return;

                e.preventDefault();
                var textarea = e.target;

                if (e.keyCode === 9) {
                    var start = textarea.selectionStart, end = textarea.selectionEnd;
                    var top = textarea.scrollTop;
                    if (start !== end) {
                        start = textarea.value.lastIndexOf("\n", start) + 1;
                    }
                    var sel = textarea.value.substring(start, end);
                    if (e.shiftKey) {
                        sel = sel.replace(/^\t/gm, '');
                    } else {
                        sel = sel.replace(/^/gm, "\t");
                    }
                    textarea.value = textarea.value.substring(0, start) + sel + textarea.value.substr(end);
                    textarea.setSelectionRange(start === end ? start + 1 : start, start + sel.length);
                    textarea.focus();
                    textarea.scrollTop = top;
                } else if (e.keyCode === 13) {
                    if (textarea.selectionStart !== textarea.selectionEnd)
                        return;
                    var cursor = textarea.selectionStart;
                    var lineStart = textarea.value.lastIndexOf("\n", cursor - 1) + 1;
                    var line = textarea.value.substring(lineStart, cursor);
                    var indentation = line.match(/^\t*/)[0];
                    textarea.value = textarea.value.substring(0, cursor) + "\n" + indentation + textarea.value.substr(cursor);
                    textarea.setSelectionRange(cursor + indentation.length + 1, cursor + indentation.length + 1);
                    textarea.focus();
                }
            });

            this.textarea.on('keyup', function () {
                _this.model.Input = _this.textarea.val();
            });

            this.model.on('output:change', function () {
                var iframe = _this.output.get(0);
                var iframeWin = iframe.contentWindow;
                var iframeDoc = iframe.contentDocument;
                var scrollY = iframeWin.scrollY;
                iframeDoc.open('text/html', 'replace');
                iframeDoc.write(_this.model.Output);
                iframeDoc.close();
                iframeWin.scrollTo(0, scrollY);
            });
        };

        EditorView.prototype.initPanels = function () {
            this.model.Input = this.textarea.val();
        };
        return EditorView;
    })();

    $(function () {
        var container = $('.live-texy-editor');
        var model = new Model(processUrl);
        var view = new EditorView(container, model);
    });
})(LiveTexyEditor || (LiveTexyEditor = {}));
//@ sourceMappingURL=main.js.map
