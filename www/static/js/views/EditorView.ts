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

			$(window).on('resize', () => {
				this.updateLineLength();
				this.updateScrollSyncPoints(this.preview.get(0).contentDocument);
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
				var TAB_KEY_CODE = 9;
				var ENTER_KEY_CODE = 13;
				var ARROW_UP_KEY_CODE = 38;
				var ARROW_DOWN_KEY_CODE = 40;

				if (e.keyCode === TAB_KEY_CODE || e.keyCode === ENTER_KEY_CODE) {
					if (e.ctrlKey || e.altKey || e.metaKey) return;

					// based on code by David Grudl, http://editor.texy.info
					e.preventDefault();
					var textarea = <HTMLTextAreaElement> e.target;
					var top = textarea.scrollTop;
					var start = textarea.selectionStart, end = textarea.selectionEnd;
					var lineStart = textarea.value.lastIndexOf('\n', start - 1) + 1;
					var lines = textarea.value.substring(lineStart, end);
					var startMove = 0, endMove = 0;

					if (e.keyCode === TAB_KEY_CODE) {
						if (e.shiftKey) {
							startMove = -1;
							lines = lines.replace(/^\t/gm, '');

						} else {
							startMove = 1;
							if (start !== end) lines = lines.replace(/^/gm, '\t');
							else lines += '\t';
						}

					} else if (e.keyCode === ENTER_KEY_CODE) {
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

				} else if (e.keyCode === ARROW_UP_KEY_CODE || e.keyCode === ARROW_DOWN_KEY_CODE) {
					if (!e.ctrlKey || e.altKey || e.metaKey) return;
					e.preventDefault();
					var step = (e.keyCode === ARROW_UP_KEY_CODE ? -20 : +20);
					var textarea = <HTMLTextAreaElement> e.target;
					textarea.scrollTop += step;
				}
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

				var iframe2 = <HTMLIFrameElement> iframe.parentNode.insertBefore(document.createElement('iframe'), iframe);
				var iframeDoc2 = iframe2.contentDocument;
				iframeDoc2.open('text/html', 'replace');
				iframeDoc2.addEventListener('readystatechange', (e) => {
					if (iframeDoc2.readyState === 'complete') {
						this.preview = $(iframe2);
						this.updateScrollSyncPoints(iframeDoc2);
						this.syncIframeScrollPosition();
						iframe.parentNode.removeChild(iframe);
					}
				});
				iframeDoc2.addEventListener('click', (e:Event) => {
					this.closeDropdown();

					// custom anchor handling due to FF
					var link = <HTMLAnchorElement> e.target;
					if (link.nodeName === 'A' && link.hash && !link.target) {
						e.preventDefault();
						var el = <HTMLElement> iframeDoc2.querySelector(link.hash);
						if (el) {
							el.scrollIntoView();
							this.syncTextareaScrollPosition();
						}
					}
				});
				iframeDoc2.write(this.model.Preview);
				iframeDoc2.close();
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

			this.updateLineLength();
		}

		private getOriginalContent(): string
		{
			var orig = this.textarea.data('original');
			return (orig !== undefined ? orig : this.textarea.val());
		}

		private textareaSyncPoints = <number[]> [0, 1];
		private previewSyncPoints = <number[]> [0, 1];
		private lineLength: number;

		private updateLineLength()
		{
			var padding = 5;
			var ta = <HTMLDivElement> document.querySelector('.textarea-shadow');
			var charCount = 1000;
			ta.firstChild.textContent = Array(charCount + 1).join('=');
			this.lineLength = Math.floor((ta.clientWidth - 2 * padding) / (ta.scrollWidth - padding) * charCount);
		}

		private updateScrollSyncPoints(doc: Document)
		{
			var input = this.textarea.val();
			var linesMap = <number[]> []; // lineNumber => offset
			var currentPos = 0, previousPos, found;

			do {
				linesMap.push(currentPos);
				previousPos = currentPos;
				currentPos = input.indexOf('\n', previousPos);
				found = (currentPos !== -1);
				currentPos = (found ? currentPos + 1 : input.length);
				while ((currentPos - previousPos - 1) > this.lineLength) {
					var reversedLine = input.substr(previousPos, this.lineLength).split('').reverse().join('');
					previousPos = Math.min(previousPos + this.lineLength, previousPos + this.lineLength - reversedLine.search(/[ \t]/));
					linesMap.push(previousPos);
				}
			} while (found);

			this.textareaSyncPoints = [0, 1];
			this.previewSyncPoints = [0, 1];
			var headings = doc.querySelectorAll('h2, h3');
			for (var i = 0; i < headings.length; i++) {
				var heading = <HTMLHeadingElement> headings[i];
				if (!heading.firstChild) continue;

				var headingText = heading.firstChild.textContent.replace(' ', ' '); // nbsp -> normal space
				var headingEscaped = headingText.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'); // http://stackoverflow.com/a/3561711
				var re = new RegExp('^`?' + headingEscaped + '`?\n(?:\\-\\-\\-+|===+|\\*\\*\\*+|###+)$', 'm');
				var match = re.exec(input);

				if (match) {
					for (var line = 0; linesMap[line] < match.index; line++);
					this.textareaSyncPoints.push(line / linesMap.length);
					this.previewSyncPoints.push(heading.getBoundingClientRect().top / doc.body.scrollHeight);
				}
			}

			console.log('LinesMap', linesMap.length, this.textarea.get(0).scrollHeight / linesMap.length);
		}

		private syncIframeScrollPosition()
		{
			var source = <HTMLElement> this.textarea.get(0);
			var target = <HTMLElement> this.preview.get(0).contentDocument.body;

			// fix FF-Chrome incompatibility, FF requires <html>, Chrome <body>
			var temp = target.scrollTop++;
			if (temp === target.scrollTop && target.scrollTop === 0) target = target.parentElement;

			this.syncScrollPosition(source, this.textareaSyncPoints, target, this.previewSyncPoints);
		}

		private syncTextareaScrollPosition()
		{
			var source = <HTMLElement> this.preview.get(0).contentDocument.body;
			var target = <HTMLElement> this.textarea.get(0);

			// fix FF-Chrome incompatibility, FF requires <html>, Chrome <body>
			var temp = source.scrollTop++;
			if (temp === source.scrollTop && source.scrollTop === 0) source = source.parentElement;

			this.syncScrollPosition(source, this.previewSyncPoints, target, this.textareaSyncPoints);
		}

		private syncScrollPosition(source: Element, sourceSyncPoints: number[], target: Element, targetSyncPoints: number[])
		{
			var sourcePos = source.scrollTop / source.scrollHeight;

			var lowerBound = <number> null, upperBound = <number> null;
			for (var i = 0; i < sourceSyncPoints.length; i++) {
				var pos = sourceSyncPoints[i];
				if (pos <= sourcePos) {
					if (lowerBound === null || pos > sourceSyncPoints[lowerBound]) {
						lowerBound = i;
					}
				} else {
					if (upperBound === null || pos < sourceSyncPoints[upperBound]) {
						upperBound = i;
					}
				}
			}

			// BP = ((AP - AL) / (AU - AL) * (BU - BL) + BL)
			var sourcePosNormalized = sourcePos - sourceSyncPoints[lowerBound];
			var sourceDistance = sourceSyncPoints[upperBound] - sourceSyncPoints[lowerBound];
			var targetDistance = targetSyncPoints[upperBound] - targetSyncPoints[lowerBound];
			var targetPosNormalized = sourcePosNormalized / sourceDistance * targetDistance;
			var targetPos = targetPosNormalized + targetSyncPoints[lowerBound];
			target.scrollTop = targetPos * target.scrollHeight;
		}

		private closeDropdown()
		{
			this.header.find('.dropdown.open').removeClass('open');
		}
	}
}
