var LiveTexyEditor;
(function (LiveTexyEditor) {
    var Model = (function () {
        function Model(processUrl) {
            this.processUrl = processUrl;
            this.handlers = {
            };
        }
        Object.defineProperty(Model.prototype, "Input", {
            get: function () {
                return this.input;
            },
            set: function (val) {
                var _this = this;
                if(val !== this.input) {
                    var xhr = $.post(this.processUrl, {
                        "editor-texyContent": val
                    });
                    xhr.done(function (payload) {
                        _this.input = val;
                        _this.output = payload.htmlContent;
                        _this.trigger('output:change');
                    });
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
            if(typeof this.handlers[eventName] === 'undefined') {
                this.handlers[eventName] = [];
            }
            this.handlers[eventName].push(callback);
        };
        Model.prototype.trigger = function (eventName) {
            if(eventName in this.handlers) {
                for(var i = 0; i < this.handlers[eventName].length; i++) {
                    this.handlers[eventName][i]();
                }
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
        }
        EditorView.prototype.initElements = function () {
            this.textarea = this.container.find('textarea.input');
            this.output = this.container.find('div.output');
        };
        EditorView.prototype.initEvents = function () {
            var _this = this;
            this.textarea.on('keyup', function () {
                _this.model.Input = _this.textarea.val();
            });
            this.model.on('output:change', function () {
                _this.output.html(_this.model.Output);
            });
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
