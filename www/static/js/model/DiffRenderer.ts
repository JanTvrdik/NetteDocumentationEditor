/// <reference path="../deps.ts" />

module LiveTexyEditor
{
	export class DiffRenderer
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

		private escapeHtml(s: string)
		{
			return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		private visualizeWhitespaces(s: string)
		{
			s = s.replace(/\n/g, '<span class="whitespace para">&para;</span>\n');
			s = s.replace(/\t/g, '<span class="whitespace tab">\t</span>');
			return s;
		}

		private reduceStringLeft(s: string, maxLen: number, maxLines: number)
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

		private reduceStringRight(s: string, maxLen: number, maxLines: number)
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
}
