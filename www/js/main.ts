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
		private visiblePanels: string[];
		private previewOutOfDate: bool;

		private handlers: {
			[eventName: string]: EventCallback[];
		};

		constructor(private processUrl: string)
		{
			this.handlers = {};
			this.visiblePanels = [];
			this.previewOutOfDate = false;
			this.initEvents();
		}

		get Input(): string
		{
			return this.input;
		}

		set Input(val: string)
		{
			if (val !== this.input) {
				this.input = val;

				if (this.visiblePanels.indexOf('preview') !== -1) {
					clearTimeout(this.timeoutId);
					this.timeoutId = setTimeout(this.updatePreview.bind(this), 800);
				} else {
					this.previewOutOfDate = true;
				}
			}
		}

		get Output(): string
		{
			return this.output;
		}

		get VisiblePanels(): string[]
		{
			return this.visiblePanels;
		}

		set VisiblePanels(panels: string[])
		{
			var knowPanels = ['code', 'preview', 'diff'];
			for (var i = 0; i < knowPanels.length; i++) {
				var panel = knowPanels[i];
				var before = (this.visiblePanels.indexOf(panel) !== -1);
				var now = (panels.indexOf(panel) !== -1);
				if (!before && now) {
					this.trigger(panel + ':show');
				} else if (before && !now) {
					this.trigger(panel + ':hide');
				}
			}

			this.visiblePanels = panels;
		}

		on(eventName: string, callback: EventCallback)
		{
			var events = eventName.split(' ');
			for (var i = 0; i < events.length; i++) {
				var event = events[i];
				if (typeof this.handlers[event] === 'undefined') this.handlers[event] = [];
				this.handlers[event].push(callback);
			}
		}

		private initEvents()
		{
			this.on('preview:show', () => {
				if (this.previewOutOfDate) this.updatePreview();
			});
		}

		private trigger(eventName: string)
		{
			if (eventName in this.handlers) {
				for (var i = 0; i < this.handlers[eventName].length; i++) {
					this.handlers[eventName][i]( );
				}
			}
		}

		private updatePreview()
		{
			this.previewOutOfDate = false;
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
		private visiblePanels: string[];
		private main: JQuery;
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
			this.main = this.container.find('.main');
			this.textarea = this.container.find('textarea');
			this.output = this.container.find('.output');
		}

		private initEvents()
		{
			this.container.find('input[name=message]').on('change', (e) => {
				this.model.VisiblePanels = e.target.value.split(' ');
			});

			this.container.find('input[name=message]').on('keydown', (e) => {
				if (e.keyCode !== 13 /* enter */ || e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) return;
				e.preventDefault();
				this.container.find('input[name=save]').trigger('click');
			});

			this.textarea.on('keydown', (e) => {
				if (e.keyCode !== 9 && e.keyCode !== 13) return; // ignore everything but tab and enter
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

			this.model.on('preview:show diff:show', () => {
				this.main.removeClass('left-only');
			});

			this.model.on('preview:show diff:show', () => {
				this.main.removeClass('left-only');
			});
		}

		private initPanels()
		{
			this.visiblePanels = this.container.find('input[name=panels]').val().split(' ');
			this.model.Input = this.textarea.val();
		}
	}

	$(() => {
		var container = $('.live-texy-editor');
		var model = new Model(processUrl);
		var view = new EditorView(container, model);

		var backupAlert = localStorage.getItem('backupAlert');
		if (!backupAlert) {
			alert('You are responsible for backing up what you\'ve written, because I haven\'t implemented it yet. Your text may be lost at unexpected moments.');
			localStorage.setItem('backupAlert', 'true');
		}
	});
}

