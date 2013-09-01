/// <reference path="../deps.ts" />

module LiveTexyEditor
{
	export class EditorView
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
			this.container.find('select[name=panels]').on('change', (e: JQueryEventObject) => {
				var input = <HTMLInputElement> e.target;
				var panels = input.value.split('+');
				window.location.hash = '#' + input.value;
				this.model.VisiblePanels = panels;
			});

			$(window).on('hashchange', () => {
				var value = location.hash.substr(1);
				var panels = value.split('+');
				var select = this.container.find('select');
				if (/^[a-z+]+$/.test(value) && select.find('option[value="' + value + '"]').length) {
					select.val(value);
					this.model.VisiblePanels = panels;
				}
			});

			this.container.find('input[name=message]').on('keydown', (e: JQueryKeyEventObject) => {
				if (e.keyCode !== 13 /* enter */ || e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) return;
				e.preventDefault();
				this.container.find('input[name=save]').trigger('click');
			});

			this.container.find('.dropdown button').on('click', (e: JQueryEventObject) => {
				e.preventDefault();
				e.stopImmediatePropagation();
				$(e.target).closest('.dropdown').toggleClass('open');
			});

			this.container.on('click', this.closeDropdown.bind(this));

			this.container.find('.status button.close').on('click', (e: JQueryEventObject) => {
				e.preventDefault();
				$(e.target).closest('.status').remove();
			});

			this.textarea.on('keydown', (e: JQueryKeyEventObject) => {
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

			this.textarea.on('keyup', (e: JQueryKeyEventObject) => {
				var textarea = <HTMLTextAreaElement> e.target;
				this.model.Input = textarea.value;
			});

			this.textarea.on('scroll', () => {
				this.syncIframeScrollPosition();
			});

			this.model.on('panel:show panel:hide', (e: PanelEvent) => {
				this.main.toggleClass(e.panel.name, e.panel.visible);
			});

			this.model.on('preview:change', () => {
				var iframe = <HTMLIFrameElement> this.preview.get(0);
				var iframeDoc = iframe.contentDocument;
				iframeDoc.open('text/html', 'replace');
				iframeDoc.write(this.model.Preview);
				iframeDoc.addEventListener('click', this.closeDropdown.bind(this));
				iframeDoc.close();
				this.syncIframeScrollPosition();
			});

			this.model.on('diff:change', () => {
				this.diff.html(this.model.Diff);
			});
		}

		private initPanels()
		{
			var orig = this.textarea.data('original');
			if (orig === undefined) orig = this.textarea.val();

			this.model.OriginalContent = orig;
			this.model.Input = this.textarea.val();

			// init visible panels
			if (location.hash.length > 1) $(window).trigger('hashchange');
			this.container.find('select[name=panels]').trigger('change');

			// IE preview height hotfix
			var expectedPreviewHeight = this.main.find('.right').innerHeight();
			if (this.preview.height() !== expectedPreviewHeight) {
				this.preview.css('height', expectedPreviewHeight + 'px');
			}
		}

		private syncIframeScrollPosition()
		{
			var iframe = <HTMLIFrameElement> this.preview.get(0);
			var iframeWin = iframe.contentWindow;
			var iframeBody = iframe.contentDocument.body;

			var textareaMaximumScrollTop = this.textarea.prop('scrollHeight') - this.textarea.height();
			var iframeMaximumScrollTop = iframeBody.scrollHeight - this.preview.height();

			var percent = this.textarea.scrollTop() / textareaMaximumScrollTop;
			var iframePos = iframeMaximumScrollTop * percent;

			iframeWin.scrollTo(0, iframePos);
		}

		private closeDropdown()
		{
			this.container.find('.dropdown.open').removeClass('open');
		}
	}
}
