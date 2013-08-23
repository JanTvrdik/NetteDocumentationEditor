/// <reference path="jquery.d.ts" />

module LiveTexyEditor
{
	declare var processUrl: string;
	declare var controlId: string;

	interface EventCallback
	{
		(): void;
	}

	class Model
	{
		private input: string;
		private output: string;
		private timeoutId: number;

		private handlers: {
			[eventName: string]: EventCallback[];
		};

		constructor(private processUrl: string)
		{
			this.handlers = {};
		}

		get Input(): string
		{
			return this.input;
		}

		set Input(val: string)
		{
			if (val !== this.input) {
				this.input = val;

				clearTimeout(this.timeoutId);
				this.timeoutId = setTimeout(this.updateOutput.bind(this), 800);
			}
		}

		get Output(): string
		{
			return this.output;
		}

		on(eventName: string, callback: EventCallback)
		{
			if (typeof this.handlers[eventName] === 'undefined') this.handlers[eventName] = [];
			this.handlers[eventName].push(callback);
		}

		private trigger(eventName: string)
		{
			if (eventName in this.handlers) {
				for (var i = 0; i < this.handlers[eventName].length; i++) {
					this.handlers[eventName][i]( );
				}
			}
		}

		private updateOutput()
		{
			var xhr = $.post(this.processUrl, {
				"editor-texyContent": this.input
			});

			xhr.done((payload) => {
				this.output = payload.htmlContent;
				this.trigger('output:change');
			});
		}

	}

	class EditorView
	{
		private panels: JQuery;
		private flexContainer: JQuery;
		private textarea: JQuery;
		private output: JQuery;

		constructor(private container: JQuery, private model: Model)
		{
			this.initElements();
			this.initEvents();
			this.initPanels();
		}

		private initElements()
		{
			this.panels = this.container.find('select[name=panels]');
			this.flexContainer = this.container.find('.main');
			this.textarea = this.container.find('textarea');
			this.output = this.container.find('.output');
		}

		private initEvents()
		{
			this.panels.on('change', (e) => {
				console.log('X');
				var panels = this.panels.val().split(' ');
				this.flexContainer.removeClass('left-only right-only');
				if (panels.length === 1) {
					var className = (panels[0] === 'code' ? 'left-only' : 'right-only');
					this.flexContainer.addClass(className);
				}
			});

			this.textarea.on('keydown', (e) => {
				if (e.keyCode !== 9 && e.keyCode !== 13) return;
				if (e.ctrlKey || e.altKey || e.metaKey) return;

				// based on code by David Grudl, http://editor.texy.info
				e.preventDefault();
				var textarea = e.target;
				var top = textarea.scrollTop;
				var start = textarea.selectionStart, end = textarea.selectionEnd;
				var lineStart = textarea.value.lastIndexOf('\n', start - 1) + 1;
				var lines = textarea.value.substring(lineStart, end);
				var startMove = 0, endMove = 0;

				// tab
				if (e.keyCode === 9) {
					if (e.shiftKey) {
						startMove = -1;
						lines = lines.replace(/^\t/gm, '');

					} else {
						startMove = 1;
						if (start !== end) lines = lines.replace(/^/gm, '\t');
						else lines += '\t';
					}

				// enter
				} else if (e.keyCode === 13) {
					if (start !== end) return; // ignore enter when text is selected

					var m, indentation;
					if (m = lines.match(/^(\t*)\/\*\*/)) { // PHPDoc / JSDoc start
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

				if (start !== lineStart || start === end) start += startMove;
				end = lineStart + lines.length + endMove;
				textarea.setSelectionRange(start, end);
				textarea.focus();
				textarea.scrollTop = top; // Firefox
			});

			this.textarea.on('keyup', () => {
				this.model.Input = this.textarea.val();
			});

			this.model.on('output:change', () => {
				var iframe = this.output.get(0);
				var iframeWin = iframe.contentWindow;
				var iframeDoc = iframe.contentDocument;
				var scrollY = iframeWin.scrollY;
				iframeDoc.open('text/html', 'replace');
				iframeDoc.write(this.model.Output);
				iframeDoc.close();
				iframeWin.scrollTo(0, scrollY);
			});
		}

		private initPanels()
		{
			this.model.Input = this.textarea.val();
		}
	}

	$(() => {
		var container = $('.live-texy-editor');
		var model = new Model(processUrl);
		var view = new EditorView(container, model);
	});
}

