/// <reference path="jquery.d.ts" />

module LiveTexyEditor
{
	declare var processUrl: string;
	declare var controlId: string;


	interface Event
	{
		/** event name */
		name: string;
	}

	interface PanelVisibilityChangeEvent extends Event
	{
		/** name of panel whose visibility has changed */
		panelName: string;

		/** new visibility */
		panelVisibility: bool;
	}

	interface EventCallback
	{
		(e: Event): void;
	}

	class Model
	{
		/** content in Texy! formatting */
		private input: string;

		/** preview in HTML */
		private preview: string;

		/** true if preview does not reflect current input value, false otherwise */
		private previewOutOfDate: bool;

		/** timeout identifier for updating preview */
		private previewTimeoutId: number;

		/** whether panels is visible or not */
		private panelsVisiblity: {
			code: bool;
			preview: bool;
		};

		/** list of registered event handlers */
		private handlers: {
			[eventName: string]: EventCallback[];
		};

		constructor(private processUrl: string)
		{
			this.panelsVisiblity = {
				code: false,
				preview: false
			};
			this.previewOutOfDate = false;
			this.handlers = {};
			this.initEvents();
		}

		get Input(): string
		{
			return this.input;
		}

		set Input(val: string)
		{
			if (val === this.input) return;
			this.input = val;

			if (this.panelsVisiblity.preview) {
				clearTimeout(this.previewTimeoutId);
				this.previewTimeoutId = setTimeout(this.updatePreview.bind(this), 800);

			} else {
				this.previewOutOfDate = true;
			}
		}

		get Preview(): string
		{
			return this.preview;
		}

		get VisiblePanels(): string[]
		{
			var visiblePanels = [];
			for (var panel in this.panelsVisiblity) {
				if (this.panelsVisiblity[panel]) {
					visiblePanels.push(panel);
				}
			}
			return visiblePanels;
		}

		set VisiblePanels(panels: string[])
		{
			for (var panel in this.panelsVisiblity) {
				var visibility = (panels.indexOf(panel) !== -1);
				if (this.panelsVisiblity[panel] === visibility) continue;

				this.panelsVisiblity[panel] = visibility;
				var eventName = 'panel:' + (visibility ? 'show' : 'hide');
				this.trigger(eventName, {
					'name': eventName,
					'panelName': panel,
					'panelVisibility': visibility
				});
			}
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
			this.on('panel:show', (e: PanelVisibilityChangeEvent) => {
				if (e.panelName === 'preview' && this.previewOutOfDate) {
					this.updatePreview();
				}
			});
		}

		private trigger(eventName: string, event?: Event)
		{
			if (typeof event === 'undefined') event = {name: eventName};

			if (eventName in this.handlers) {
				for (var i = 0; i < this.handlers[eventName].length; i++) {
					this.handlers[eventName][i](event);
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
				this.preview = payload.htmlContent;
				this.trigger('preview:change');
			});
		}
	}

	class EditorView
	{
		private main: JQuery;
		private textarea: JQuery;
		private preview: JQuery;

		constructor(private container: JQuery, private model: Model)
		{
			this.initElements();
			this.initEvents();
			this.initPanels();
		}

		private initElements()
		{
			this.main = this.container.find('.main');
			this.textarea = this.container.find('.code textarea');
			this.preview = this.container.find('.preview iframe');
		}

		private initEvents()
		{
			this.container.find('select[name=panels]').on('change', (e) => {
				var input = <HTMLInputElement> e.target;
				this.model.VisiblePanels = input.value.split(' ');
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
				var textarea = <HTMLTextAreaElement> e.target;
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

			this.textarea.on('keyup', (e) => {
				var textarea = <HTMLTextAreaElement> e.target;
				this.model.Input = textarea.value;
			});

			this.textarea.on('scroll', () => {
				var iframe = <HTMLIFrameElement> this.preview.get(0);
				var iframeWin = iframe.contentWindow;
				var iframeBody = iframe.contentDocument.body;

				var textareaMaximumScrollTop = this.textarea.prop('scrollHeight') - this.textarea.height();
				var iframeMaximumScrollTop = iframeBody.scrollHeight - this.preview.height();

				var percent = this.textarea.scrollTop() / textareaMaximumScrollTop;
				var iframePos = iframeMaximumScrollTop * percent;

				iframeWin.scrollTo(0, iframePos);
			});

			this.model.on('panel:show panel:hide', (e: PanelVisibilityChangeEvent) => {
				this.main.toggleClass(e.panelName, e.panelVisibility);
			});

			this.model.on('preview:change', () => {
				var iframe = <HTMLIFrameElement> this.preview.get(0);
				var iframeWin = iframe.contentWindow;
				var iframeDoc = iframe.contentDocument;
				var scrollY = iframeWin.pageYOffset;
				iframeDoc.open('text/html', 'replace');
				iframeDoc.write(this.model.Preview);
				iframeDoc.close();
				iframeWin.scrollTo(0, scrollY);
			});
		}

		private initPanels()
		{
			this.model.VisiblePanels = this.container.find('select[name=panels]').val().split(' ');
			this.model.Input = this.textarea.val();

			// IE preview height hotfix
			var expectedPreviewHeight = this.main.find('.right').innerHeight();
			if (this.preview.height() !== expectedPreviewHeight) {
				this.preview.css('height', expectedPreviewHeight + 'px');
			}
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

