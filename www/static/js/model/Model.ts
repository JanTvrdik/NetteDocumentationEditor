/// <reference path="../deps.ts" />

module LiveTexyEditor
{
	export interface PanelEvent
	{
		panel: Panel;
	}

	export class Panel
	{
		/** is panel visible? */
		visible: boolean = false;

		/** panel content */
		content: string = '';

		/** update timeout identifer */
		timeoutId: number;

		/**
		 * @param name           panel name
		 * @param updateWaitTime how long (in milliseconds) after last input change should the panel be updated?
		 * @param outOfDate      does panel content need to be updated?
		 */
		constructor(public name: string, public updateWaitTime: number, public outOfDate: boolean = false)
		{

		}
	}

	export class Model extends EventEmitter
	{
		/** original content in Texy! formatting */
		public OriginalContent: string;

		/** registered panels */
		private panels: {
			[name: string]: Panel;
		};

		constructor(private diffRenderer: DiffRenderer, private processUrl: string)
		{
			super();
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
				this.trigger(eventName, [{'panel': panel}]);
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
				$.post(this.processUrl, {texyContent: this.Input}, (payload: {htmlContent: string}) => {
					panel.content = payload.htmlContent;
					this.trigger(panel.name + ':change', [{'panel': panel}]);
				});

			} else if (panel.name === 'diff') {
				var input = this.Input.trim().replace(/[ \t]+\n/g, '\n') + '\n';
				var dmp = new diff_match_patch();
				var diffs = dmp.diff_main(this.OriginalContent, input);
				dmp.diff_cleanupSemantic(diffs);
				panel.content = this.diffRenderer.render(diffs);
				this.trigger(panel.name + ':change', [{'panel': panel}]);

			} else {
				console.warn('Unable to update panel %s.', panel.name);
			}
		}
	}
}
