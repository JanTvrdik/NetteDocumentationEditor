/// <reference path="../deps.ts" />

module LiveTexyEditor
{
	export interface Event
	{
		/** event name */
		name: string;
	}

	export interface PanelEvent extends Event
	{
		panel: Panel;
	}

	export interface EventCallback
	{
		(e: Event): void;
	}

	export class Panel
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

	export class Model
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
}
