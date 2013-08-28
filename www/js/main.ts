/// <reference path="jquery.d.ts" />
/// <reference path="diff_match_patch.d.ts" />

module LiveTexyEditor
{
	declare var processUrl: string;
	declare var controlId: string;


	interface Event
	{
		/** event name */
		name: string;
	}

	interface PanelEvent extends Event
	{
		panel: Panel;
	}

	interface EventCallback
	{
		(e: Event): void;
	}

	class Panel
	{
		/** is panel visible? */
		visible: bool = false;

		/** panel content */
		content: string = '';

		/** update timeout identifer */
		timeoutId: number;

		/**
		 * @param name      panel name
		 * @param updateWaitTime how long (in milliseconds) after last input change should the panel be updated?
		 * @param outOfDate does panel content need to be updated?
		 */
		constructor(public name: string, public updateWaitTime: number, public outOfDate: bool = false)
		{

		}
	}

	class DiffRenderer
	{
		constructor(public contextChars: number, public contextLines: number)
		{

		}

		render(diffs: Diff[]): string
		{
			var html = [];
			for (var i = 0; i < diffs.length; i++) {
				var op = diffs[i][0];    // Operation (insert, delete, equal)
				var data = diffs[i][1];  // Text of change.
				var text = this.escapeHtml(data);
				switch (op) {
					case DIFF_INSERT:
					case DIFF_DELETE:
						var tag = (op === DIFF_INSERT ? 'ins' : 'del');
						var multiline = text.indexOf('\n') !== -1;
						html[i] = '<' + tag + (multiline ? ' class="multiline"' : '') + '>' + this.visualizeWhitespaces(text) + '</' + tag + '>';
						break;

					case DIFF_EQUAL:
						if (diffs.length === 1) {
							text = '<em>No difference</em>';

						} else if (i === 0) {
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
		}

		private escapeHtml(s)
		{
			return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		private visualizeWhitespaces(s)
		{
			s = s.replace(/\n/g, '<span class="whitespace para">&para;</span>\n');
			s = s.replace(/\t/g, '<span class="whitespace tab">\t</span>');
			return s;
		}

		private reduceStringLeft(s, maxLen, maxLines)
		{
			s = s.substr(-maxLen);
			for (var i = 0, pos = s.length; i < maxLines; i++) {
				pos = s.lastIndexOf('\n', pos);
				if (pos === -1) return s;
				else pos--;

			}
			s = s.substr(pos + 2);
			s = s.replace(/^\n+/, '');
			return s;
		}

		private reduceStringRight(s, maxLen, maxLines)
		{
			s = s.substr(0, maxLen);
			for (var i = 0, pos = 0; i < maxLines; i++) {
				pos = s.indexOf('\n', pos);
				if (pos === -1) return s;
				else pos++;
			}

			s = s.substr(0, pos - 1);
			s = s.replace(/\n+$/, '');
			return s;
		}
	}


	class Model
	{
		/** original content in Texy! formatting */
		public OriginalContent: string;

		/** registered panels */
		private panels: {
			[name: string]: Panel;
		};

		/** list of registered event handlers */
		private handlers: {
			[eventName: string]: EventCallback[];
		};

		constructor(private diffRenderer: DiffRenderer, private processUrl: string, private controlId: string)
		{
			this.handlers = {};
			this.initEvents();
			this.initPanels();
		}

		get Input(): string
		{
			return this.panels['code'].content;
		}

		set Input(val: string)
		{
			if (val === this.panels['code'].content) return;
			this.panels['code'].content = val;

			for (var name in this.panels) {
				if (name === 'code') continue;
				var panel = this.panels[name];
				if (panel.visible) {
					this.scheduleForUpdate(panel);

				} else {
					panel.outOfDate = true;
				}
			}
		}

		get Preview(): string
		{
			return this.panels['preview'].content;
		}

		get Diff(): string
		{
			return this.panels['diff'].content;
		}

		get VisiblePanels(): string[]
		{
			var visiblePanels = [];
			for (var name in this.panels) {
				if (this.panels[name].visible) {
					visiblePanels.push(name);
				}
			}
			return visiblePanels;
		}

		set VisiblePanels(visiblePanels: string[])
		{
			for (var name in this.panels) {
				var panel = this.panels[name];
				var visibility = (visiblePanels.indexOf(name) !== -1);
				if (panel.visible === visibility) continue;

				panel.visible = visibility;
				var eventName = 'panel:' + (visibility ? 'show' : 'hide');
				this.trigger(eventName, {
					'name': eventName,
					'panel': panel
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
			this.on('panel:show', (e: PanelEvent) => {
				if (e.panel.outOfDate) {
					this.updatePanel(e.panel);
				}
			});
		}

		private initPanels()
		{
			this.panels = {
				code: new Panel('code', 0),
				preview: new Panel('preview', 800, true),
				diff: new Panel('diff', 200, true)
			};
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

		private scheduleForUpdate(panel: Panel)
		{
			clearTimeout(panel.timeoutId);
			panel.timeoutId = setTimeout(() => {
				this.updatePanel(panel);
			}, panel.updateWaitTime);
		}

		private updatePanel(panel: Panel)
		{
			panel.outOfDate = false;

			if (panel.name === 'preview') {
				var data = {};
				data[this.controlId + '-texyContent'] = this.Input;

				$.post(this.processUrl, data, (payload) => {
					panel.content = payload.htmlContent;
					this.trigger(panel.name + ':change', {
						'name': panel.name + ':change',
						'panel': panel
					});
				});

			} else if (panel.name === 'diff') {
				var input = this.Input.trim().replace(/[ \t]+\n/g, '\n') + '\n';
				var dmp = new diff_match_patch();
				var diffs = dmp.diff_main(this.OriginalContent, input);
				dmp.diff_cleanupSemantic(diffs);
				panel.content = this.diffRenderer.render(diffs);
				this.trigger(panel.name + ':change', {
					'name': panel.name + ':change',
					'panel': panel
				});
			}
		}
	}

	class EditorView
	{
		private main: JQuery;
		private textarea: JQuery;
		private preview: JQuery;
		private diff: JQuery;

		constructor(private container: JQuery, private model: Model)
		{
			this.initElements();
			this.initEvents();
			this.initPanels();
		}

		private initElements()
		{
			this.main = this.container.find('.main');
			this.textarea = this.main.find('.code textarea');
			this.preview = this.main.find('.preview iframe');
			this.diff = this.main.find('.diff .content');
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

			this.model.on('panel:show panel:hide', (e: PanelEvent) => {
				this.main.toggleClass(e.panel.name, e.panel.visible);
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

			this.model.on('diff:change', () => {
				this.diff.html(this.model.Diff);
			});
		}

		private initPanels()
		{
			this.model.OriginalContent = this.textarea.data('original');
			this.model.Input = this.textarea.val();
			this.model.VisiblePanels = this.container.find('select[name=panels]').val().split(' ');

			// IE preview height hotfix
			var expectedPreviewHeight = this.main.find('.right').innerHeight();
			if (this.preview.height() !== expectedPreviewHeight) {
				this.preview.css('height', expectedPreviewHeight + 'px');
			}
		}
	}

	$(() => {
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
}

