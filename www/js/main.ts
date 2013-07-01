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
				var xhr = $.post(this.processUrl, {
					"editor-texyContent": val // TODO: fix
				});

				xhr.done((payload) => {
					this.input = val;
					this.output = payload.htmlContent;
					this.trigger('output:change');
				});
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
					this.handlers[eventName][i]();
				}
			}
		}
	}

	class EditorView
	{
		private textarea: JQuery;
		private output: JQuery;

		constructor(private container: JQuery, private model: Model)
		{
			this.initElements();
			this.initEvents();
		}

		private initElements()
		{
			this.textarea = this.container.find('textarea.input');
			this.output = this.container.find('div.output');
		}

		private initEvents()
		{
			this.textarea.on('keyup', () => {
				this.model.Input = this.textarea.val();
			});

			this.model.on('output:change', () => {
				this.output.html(this.model.Output);
			});
		}
	}

	$(() => {
		var container = $('.live-texy-editor');
		var model = new Model(processUrl);
		var view = new EditorView(container, model);
	});
}

