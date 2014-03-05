/// <reference path="../deps.ts" />

module LiveTexyEditor
{
	export class EditorView
	{
		private header: JQuery;
		private main: JQuery;
		private textarea: JQuery;
		private preview: JQuery;
		private diff: JQuery;

		constructor(private container: JQuery, private model: Model)
		{
			this.initElements();
			this.initEvents();
			this.initModel();
			this.initPanels();
		}

		private initElements()
		{
			this.header = this.container.find('.header');
			this.main = this.container.find('.main');
			this.textarea = this.main.find('.column.code textarea');
			this.preview = this.main.find('.column.preview iframe');
			this.diff = this.main.find('.column.diff .content');
		}

		private initEvents()
		{
			this.initHeaderEvents();
			this.initPanelsEvents();
			this.initDropdownEvents();
			this.initTextareaEvents();
			this.initModelEvents();
		}

		private initHeaderEvents()
		{
			this.header.find('input[name=message]').on('keydown', (e: JQueryKeyEventObject) => {
				if (e.keyCode !== 13 /* enter */ || e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) return;
				e.preventDefault();
				this.header.find('input[name=save]').trigger('click');
			});

			this.header.find('.status button.close').on('click', (e: JQueryEventObject) => {
				e.preventDefault();
				$(e.target).closest('.status').remove();
			});
		}

		private initPanelsEvents()
		{
			var select = this.header.find('select[name=panels]');

			select.on('change', () => {
				var value = select.val();
				window.location.hash = '#' + value;
				this.model.VisiblePanels = value.split('+');
			});

			$(window).on('hashchange', () => {
				var value = location.hash.substr(1);
				if (/^[a-z+]+$/.test(value) && select.find('option[value="' + value + '"]').length) {
					select.val(value).trigger('change');
				}
			});
		}

		private initDropdownEvents()
		{
			var dropdown = this.header.find('.dropdown');

			dropdown.find('button').on('click', (e: JQueryEventObject) => {
				e.preventDefault();
				e.stopImmediatePropagation();
				dropdown.toggleClass('open');
			});

			dropdown.find('li a.fullscreen').on('click', (e: JQueryEventObject) => {
				e.preventDefault();
				screenfull.toggle(this.container.get(0));
			});

			this.container.on('click', this.closeDropdown.bind(this));
		}

		private initTextareaEvents()
		{
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
				this.model.Input = this.textarea.val();
			});

			this.textarea.on('scroll', this.syncIframeScrollPosition.bind(this));
		}

		private initModelEvents()
		{
			this.model.on('panel:show panel:hide', (e: PanelEvent) => {
				this.main.toggleClass(e.panel.name, e.panel.visible);
			});

			this.model.on('preview:change', () => {
				var iframe = <HTMLIFrameElement> this.preview.get(0);
				var iframeDoc = iframe.contentDocument;
				var iframeWin = iframe.contentWindow;

				iframeDoc.open('text/html', 'replace');
				iframeDoc.write(this.model.Preview);
				iframeDoc.close();

				iframeWin.addEventListener('load', () => {
					this.updateScrollSyncPoints(iframeDoc, this.model.Preview);
					this.syncIframeScrollPosition();
				});
				iframeDoc.addEventListener('click', (e:Event) => {
					this.closeDropdown();

					// custom anchor handling due to FF
					var link = <HTMLAnchorElement> e.target;
					if (link.nodeName === 'A' && link.hash && !link.target) {
						e.preventDefault();
						var el = <HTMLElement> iframeDoc.querySelector(link.hash);
						if (el) el.scrollIntoView();
					}
				});
			});

			this.model.on('diff:change', () => {
				this.diff.html(this.model.Diff);
			});
		}

		private initModel()
		{
			this.model.OriginalContent = this.getOriginalContent();
			this.model.Input = this.textarea.val();
		}

		private initPanels()
		{
			// init visible panels
			if (location.hash.length > 1) $(window).trigger('hashchange');
			this.header.find('select[name=panels]').trigger('change');

			// IE preview height hotfix
			var expectedPreviewHeight = this.main.find('.right').innerHeight();
			if (this.preview.height() !== expectedPreviewHeight) {
				this.preview.css('height', expectedPreviewHeight + 'px');
			}

			var ta = <HTMLTextAreaElement> this.textarea.get(0);
			var sh, width;
			for (sh = ta.scrollHeight; sh === ta.scrollHeight; ta.value += ' ');
			for (width = 0, sh = ta.scrollHeight; sh === ta.scrollHeight; width++, ta.value += ' ');
			alert(width);


		}

		private getOriginalContent(): string
		{
			var orig = this.textarea.data('original');
			return (orig !== undefined ? orig : this.textarea.val());
		}

		private updateScrollSyncPoints(doc: Document, input: string)
		{
			var syncPoints = {};

			var linesMap = <number[]> [0]; // lineNumber => offset
			var lineWidth = 101; // todo!
			var prevPos = 0, currentPos;
			while ((currentPos = input.indexOf('\n', prevPos + 1) + 1) > prevPos) {
				while ((currentPos - prevPos) > lineWidth) {
					prevPos += lineWidth;
					linesMap.push(prevPos);
				}
				linesMap.push(currentPos);
				prevPos = currentPos;
			}

			var headings = doc.querySelectorAll('h1, h2, h3');
			for (var i = 0; i < headings.length; i++) {
				var heading = <HTMLHeadingElement> headings[i];
				var headingText = heading.firstChild.textContent;
				var headingEscaped = headingText.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'); // http://stackoverflow.com/a/3561711
				var re = new RegExp('^' + headingEscaped + '\n(?:\\-\\-\\-+|===+|\\*\\*\\*+|###+)$', 'm');
				var match = re.exec(input);
				if (match) {
					for (var line = 0; linesMap[line] < match.index; line++);
					syncPoints[line] = heading.scrollTop;
				}
			}
		}

		private syncIframeScrollPosition()
		{
			var iframe = <HTMLIFrameElement> this.preview.get(0);
			var iframeWin = iframe.contentWindow;
			var iframeBody = iframe.contentDocument.body;
			if (iframeBody === null) return;

			var textareaMaximumScrollTop = this.textarea.prop('scrollHeight') - this.textarea.height();
			var iframeMaximumScrollTop = iframeBody.scrollHeight - this.preview.height();

			var percent = this.textarea.scrollTop() / textareaMaximumScrollTop;
			var iframePos = iframeMaximumScrollTop * percent;

			iframeWin.scrollTo(0, iframePos);
		}

		private closeDropdown()
		{
			this.header.find('.dropdown.open').removeClass('open');
		}
	}
}
